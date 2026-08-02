<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

/**
 * シーダー＝開発用の初期データ投入。
 * 実行： php artisan db:seed --class=TaskSeeder
 */
class TaskSeeder extends Seeder
{
    public function run(): void
    {
        // 動作イメージが掴めるよう、意味のあるタスクを手で用意する
        $samples = [
            ['title' => 'Laravelのルーティングを理解する', 'due_date' => now()->addDays(1)->toDateString()],
            ['title' => 'MVCの流れを図に書いてみる', 'due_date' => now()->addDays(3)->toDateString()],
            ['title' => 'マイグレーションを1本自分で書く', 'due_date' => null],
        ];

        foreach ($samples as $sample) {
            Task::create($sample + [
                'description' => null,
                'is_done' => false,
            ]);
        }

        Task::create([
            'title' => '開発環境をDockerで立ち上げる',
            'description' => 'docker compose up -d でapp / web / dbが起動することを確認した。',
            'is_done' => true,
        ]);

        // ファクトリでダミーも少し混ぜる（未完了5件・完了2件）
        Task::factory()->count(5)->create();
        Task::factory()->count(2)->done()->create();
    }
}
