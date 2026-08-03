<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API のフィーチャーテスト。
 *
 * TaskTest.php（Web版）が get / post を使っていたのに対し、
 * こちらは getJson / postJson を使う。
 * これらは自動で Accept: application/json を付けてくれるので、
 * 「JavaScript から叩かれた時」と同じ条件で検証できる。
 *
 * 実行： php artisan test
 */
class ApiTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_一覧をJSONで取得できる(): void
    {
        Task::factory()->count(3)->create();

        $this->getJson('/api/tasks')
            ->assertOk()
            // paginate() の結果なので、タスク本体は data の中に入る
            ->assertJsonCount(3, 'data')
            // additional() で足した件数も返っている
            ->assertJsonPath('counts.todo', 3)
            ->assertJsonPath('counts.done', 0);
    }

    public function test_JSONにis_overdueが含まれる(): void
    {
        // is_overdue は DB の列ではなくモデルのアクセサ。
        // TaskResource に書いて初めて JSON に出てくる、という確認。
        Task::factory()->create(['due_date' => now()->subDay(), 'is_done' => false]);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data.0.is_overdue', true)
            // 日付も Y-m-d に整形されている
            ->assertJsonPath('data.0.due_date', now()->subDay()->format('Y-m-d'));
    }

    public function test_タスクを登録すると201が返る(): void
    {
        $this->postJson('/api/tasks', [
            'title' => '牛乳を買う',
            'description' => '低脂肪のやつ',
            'due_date' => '2026-12-31',
        ])
            ->assertCreated() // 201
            ->assertJsonPath('data.title', '牛乳を買う')
            ->assertJsonPath('data.is_done', false);

        $this->assertDatabaseHas('tasks', ['title' => '牛乳を買う']);
    }

    public function test_タスク名が空だと422とエラー内容が返る(): void
    {
        // Web版はフォームへリダイレクトされたが、API は 422 + JSON が返る
        $this->postJson('/api/tasks', ['title' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_タイトルだけのPATCHでis_doneが巻き添えで変わらない(): void
    {
        // ★このテストが一番大事★
        //
        // Web版の UpdateTaskRequest は prepareForValidation() で
        // 「is_done が送られていなければ false」に変換している。
        // それを API で使い回すと、タイトルだけ直したつもりが
        // 完了済みタスクが未完了に戻ってしまう。
        //
        // API 用に別の FormRequest（sometimes 使用）を用意した理由がこれ。
        $task = Task::factory()->create(['title' => '古いタイトル', 'is_done' => true]);

        $this->patchJson("/api/tasks/{$task->id}", ['title' => '新しいタイトル'])
            ->assertOk()
            ->assertJsonPath('data.title', '新しいタイトル')
            ->assertJsonPath('data.is_done', true); // true のままであること

        $this->assertTrue($task->fresh()->is_done);
    }

    public function test_完了状態を切り替えられる(): void
    {
        $task = Task::factory()->create(['is_done' => false]);

        $this->patchJson("/api/tasks/{$task->id}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_done', true);
        $this->assertTrue($task->fresh()->is_done);

        $this->patchJson("/api/tasks/{$task->id}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_done', false);
        $this->assertFalse($task->fresh()->is_done);
    }

    public function test_削除すると204が返る(): void
    {
        $task = Task::factory()->create();

        // 204 No Content ＝ 成功したが返す本文は無い
        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_存在しないIDは404がJSONで返る(): void
    {
        // HTML のエラーページではなく JSON が返ること。
        // bootstrap/app.php の shouldRenderJsonWhen が効いている確認。
        // （assertJsonStructure が通る＝レスポンスが JSON として解釈できた、という意味）
        $this->getJson('/api/tasks/99999')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_未完了で絞り込める(): void
    {
        Task::factory()->create(['title' => 'まだのタスク']);
        Task::factory()->done()->create(['title' => 'おわったタスク']);

        $this->getJson('/api/tasks?filter=todo')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'まだのタスク');
    }

    public function test_CSRFトークン無しでも書き込みできる(): void
    {
        // Web版は @csrf が無いと 419 になるが、API ルートには CSRF 検証が無い。
        // ここでは Web 版のテストと違い、トークンを一切用意していない点に注目。
        $this->postJson('/api/tasks', ['title' => 'トークン無しで追加'])
            ->assertCreated();
    }
}
