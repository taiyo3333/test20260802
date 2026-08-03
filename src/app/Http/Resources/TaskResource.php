<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API リソース ＝「モデルを JSON にする時の変換ルール」。
 *
 * ■ なぜ必要？
 * コントローラーで return $task; と書くだけでも JSON にはなる。
 * ただしそれは「テーブルの列がそのまま出るだけ」なので、
 *
 *   ・アクセサ（$task->is_overdue）は出てこない
 *   ・due_date が "2026-08-10T00:00:00.000000Z" という扱いにくい形で出る
 *   ・将来 password のような列が増えたら、うっかり全部外に出てしまう
 *
 * という問題がある。
 * このクラスを1枚かませば「外に出す形」を自分で決められる。
 * ビューが HTML の見た目を決めるように、リソースは JSON の形を決める係。
 *
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * この配列がそのまま JSON になる。
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,

            // モデルの casts() で boolean 指定しているので、DBの 0/1 ではなく true/false で出る
            'is_done' => $this->is_done,

            // Carbon 型なので好きな形に整形できる。?-> は null の時に落ちないようにする書き方
            'due_date' => $this->due_date?->format('Y-m-d'),

            // ★ここが重要★
            // is_overdue は DB の列ではなく Task モデルのアクセサ（計算で求まる値）。
            // ここに書かない限り JSON には絶対に出てこない。
            'is_overdue' => $this->is_overdue,

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
