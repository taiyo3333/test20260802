<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API 版のタスクコントローラー。
 *
 * ■ Web 版（App\Http\Controllers\TaskController）と見比べてほしい
 * やっていること（Model に仕事をさせる部分）は ほぼ同じ。
 * 違うのは「返し方」だけ。
 *
 *   Web 版                          API 版
 *   ------------------------------  ------------------------------
 *   view('tasks.index', [...])      TaskResource::collection(...)
 *   redirect()->route(...)          201 / 204 などのステータスコード
 *   戻り値の型 View / Redirect      戻り値の型 JsonResponse / Resource
 *   create() と edit() がある       無い（HTMLフォームを返さないので不要）
 *
 * 「M（Model）と DB は共通で、出口だけが2つある」という関係になっている。
 * 実際 /tasks（Web版）と /tasks-api（API版）で同じデータが見える。
 */
class TaskController extends Controller
{
    /**
     * 一覧： GET /api/tasks
     *
     * 絞り込みと並び順は Web 版の index() と全く同じ。
     * paginate() の結果をリソースに渡すと、JSON が自動でこうなる：
     *
     *   {
     *     "data": [ {...}, {...} ],        ← タスク本体
     *     "links": { "first":..., "next":... },
     *     "meta":  { "current_page":1, "last_page":3, "total":25, ... }
     *   }
     *
     * フロント側はこの meta を見てページ送りのボタンを作れる。
     */
    public function index(Request $request): AnonymousResourceCollection
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

        return TaskResource::collection($tasks)
            // meta に自前の情報を足すこともできる。
            // 画面のフィルタボタンに件数を出すために使う。
            ->additional([
                'counts' => [
                    'todo' => Task::notDone()->count(),
                    'done' => Task::done()->count(),
                ],
            ]);
    }

    /**
     * 保存： POST /api/tasks
     *
     * バリデーションは Web 版と全く同じでよいので StoreTaskRequest をそのまま再利用。
     * （更新の方だけは事情があって API 用を別に作っている。
     *   理由は App\Http\Requests\Api\UpdateTaskRequest のコメント参照）
     *
     * Web 版は保存後にリダイレクトしていた（PRGパターン）が、
     * API にリダイレクトは不要。代わりに「201 Created ＝ 作ったで」を返す。
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = Task::create($request->validated());

        // create() の直後、モデルは「渡された項目 + id + timestamps」しか持っていない。
        // is_done はテーブル側の初期値（false）で入るため、このままだと JSON に
        // "is_done": null と出てしまう。refresh() で DB から読み直して埋める。
        $task->refresh();

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED); // 201
    }

    /**
     * 詳細： GET /api/tasks/{task}
     *
     * 引数の Task 型による自動検索（ルートモデルバインディング）は Web 版と同じ。
     * 見つからない時も同じく自動で 404 になるが、
     * API では HTML のエラーページではなく JSON で 404 が返る。
     */
    public function show(Task $task): TaskResource
    {
        return new TaskResource($task);
    }

    /**
     * 更新： PUT / PATCH /api/tasks/{task}
     *
     * validated() は「ルールに書いてあって、かつ実際に送られてきた項目」だけを返す。
     * sometimes と組み合わせているので、
     *   PATCH {"title":"新しい名前"}  → title だけ更新（is_done は触らない）
     * という部分更新ができる。
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

        return new TaskResource($task);
    }

    /**
     * 削除： DELETE /api/tasks/{task}
     *
     * 204 No Content ＝「成功したけど返す中身は無い」。
     * 消したものの情報を返しても使い道がないので、これが定番。
     * フロント側は「204 が返ってきたら、その行を画面から消す」と書けばよい。
     */
    public function destroy(Task $task): Response
    {
        $task->delete();

        return response()->noContent(); // 204
    }

    /**
     * 完了 / 未完了の切り替え： PATCH /api/tasks/{task}/toggle
     *
     * Web 版は back() で元のページに戻していたが、
     * API は「更新後の姿」を JSON で返す。
     * フロントはそれを受け取って、その行だけを描き直せばよい（画面全体の再読込は不要）。
     */
    public function toggle(Task $task): TaskResource
    {
        $task->update(['is_done' => ! $task->is_done]);

        return new TaskResource($task);
    }
}
