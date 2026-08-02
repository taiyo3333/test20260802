<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ファクトリ＝「それっぽいダミーデータの作り方」の定義。
 * テストやシーダーから Task::factory()->create() で使う。
 *
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // fake() はランダムなダミー値を作るヘルパー。
        // 日本語のサンプルが欲しいので、候補リストから選ぶ形にしている。
        $titles = [
            '買い物リストを作る', '洗濯物をたたむ', '請求書を確認する',
            '週次レポートを書く', '部屋の掃除をする', '本を1章読む',
            '歯医者の予約を取る', 'ジムに行く', 'PCのバックアップを取る',
        ];

        return [
            'title' => fake()->randomElement($titles),
            'description' => fake()->boolean(50) ? '後で詳細を追記する。' : null,
            'is_done' => false,
            'due_date' => fake()->boolean(60)
                ? fake()->dateTimeBetween('-5 days', '+2 weeks')->format('Y-m-d')
                : null,
        ];
    }

    /**
     * ステート：Task::factory()->done()->create() で完了済みタスクを作れる。
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_done' => true,
        ]);
    }
}
