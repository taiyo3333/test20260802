<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MVC の「C（Controller）」。
 *
 * 役割は「リクエストを受け取り → Model に仕事をさせ → View を返す」の交通整理。
 * ここにビジネスロジックを書きすぎないのがコツ。
 *
 * Route::resource('tasks', TaskController::class) と書くだけで
 * 下の7つのメソッドがURLと自動で結び付く。
 */
class TaskController extends Controller
{
    /**
     * 一覧表示： GET /tasks
     */
    public function index(Request $request): View
    {
        // ?filter=done / ?filter=todo で絞り込む
        $filter = $request->query('filter', 'all');

        $tasks = Task::query()
            ->when($filter === 'done', fn ($query) => $query->done())
            ->when($filter === 'todo', fn ($query) => $query->notDone())
            ->orderBy('is_done')          // 未完了を上に
            ->orderBy('due_date')         // 期限が近い順
            ->latest('id')                // 同条件なら新しい順
            ->paginate(10)
            ->withQueryString();          // ページ送りしても filter を維持する

        return view('tasks.index', [
            'tasks' => $tasks,
            'filter' => $filter,
            'todoCount' => Task::notDone()->count(),
            'doneCount' => Task::done()->count(),
        ]);
    }

    /**
     * 新規作成フォーム： GET /tasks/create
     */
    public function create(): View
    {
        return view('tasks.create');
    }

    /**
     * 保存処理： POST /tasks
     *
     * 引数を StoreTaskRequest にしておくと、このメソッドに入ってくる時点で
     * バリデーション済みであることが保証される。
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        Task::create($request->validated());

        // 保存後は必ずリダイレクト（PRGパターン）。
        // ブラウザの再読み込みで二重登録されるのを防ぐため。
        return redirect()
            ->route('tasks.index')
            ->with('status', 'タスクを追加しました。');
    }

    /**
     * 詳細表示： GET /tasks/{task}
     *
     * 引数に Task 型を書くと、URLの {task} をIDとして自動で検索してくれる
     * （ルートモデルバインディング）。見つからなければ自動で404。
     */
    public function show(Task $task): View
    {
        return view('tasks.show', ['task' => $task]);
    }

    /**
     * 編集フォーム： GET /tasks/{task}/edit
     */
    public function edit(Task $task): View
    {
        return view('tasks.edit', ['task' => $task]);
    }

    /**
     * 更新処理： PUT/PATCH /tasks/{task}
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return redirect()
            ->route('tasks.index')
            ->with('status', 'タスクを更新しました。');
    }

    /**
     * 削除処理： DELETE /tasks/{task}
     */
    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('status', 'タスクを削除しました。');
    }

    /**
     * 完了 / 未完了の切り替え： PATCH /tasks/{task}/toggle
     *
     * resource の7つには含まれない独自アクション。
     * routes/web.php に1行足すだけで追加できる。
     */
    public function toggle(Task $task): RedirectResponse
    {
        $task->update(['is_done' => ! $task->is_done]);

        return back()->with(
            'status',
            $task->is_done ? '「'.$task->title.'」を完了にしました。' : '「'.$task->title.'」を未完了に戻しました。'
        );
    }
}
