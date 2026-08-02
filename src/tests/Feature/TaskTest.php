<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * フィーチャーテスト＝「URLを叩いて期待通り動くか」を確認するテスト。
 *
 * 実行： php artisan test
 *
 * RefreshDatabase を使うと、テストごとにDBを作り直してくれるので
 * テスト同士が影響し合わない（開発用DBも汚れない）。
 */
class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_一覧ページが表示できる(): void
    {
        $task = Task::factory()->create(['title' => 'テスト用のタスク']);

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('テスト用のタスク');
    }

    public function test_タスクを新規登録できる(): void
    {
        $response = $this->post(route('tasks.store'), [
            'title' => '牛乳を買う',
            'description' => '低脂肪のやつ',
            'due_date' => '2026-12-31',
        ]);

        $response->assertRedirect(route('tasks.index'));

        // DBに実際に入ったか確認
        $this->assertDatabaseHas('tasks', [
            'title' => '牛乳を買う',
            'is_done' => false,
        ]);
    }

    public function test_タスク名が空だとバリデーションエラーになる(): void
    {
        $this->post(route('tasks.store'), ['title' => ''])
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_タスクを更新できる(): void
    {
        $task = Task::factory()->create(['title' => '古いタイトル']);

        $this->put(route('tasks.update', $task), [
            'title' => '新しいタイトル',
            'description' => null,
            'due_date' => null,
            'is_done' => '1',
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => '新しいタイトル',
            'is_done' => true,
        ]);
    }

    public function test_完了状態を切り替えられる(): void
    {
        $task = Task::factory()->create(['is_done' => false]);

        $this->patch(route('tasks.toggle', $task));
        $this->assertTrue($task->fresh()->is_done);

        $this->patch(route('tasks.toggle', $task));
        $this->assertFalse($task->fresh()->is_done);
    }

    public function test_タスクを削除できる(): void
    {
        $task = Task::factory()->create();

        $this->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_未完了で絞り込める(): void
    {
        Task::factory()->create(['title' => 'まだのタスク']);
        Task::factory()->done()->create(['title' => 'おわったタスク']);

        $this->get(route('tasks.index', ['filter' => 'todo']))
            ->assertOk()
            ->assertSee('まだのタスク')
            ->assertDontSee('おわったタスク');
    }

    public function test_存在しないタスクは404になる(): void
    {
        $this->get(route('tasks.show', 999))->assertNotFound();
    }
}
