<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * MVC の「M（Model）」。
 *
 * 1つのモデルクラスが1つのテーブル（tasks）に対応する。
 * DBへの読み書きは基本的にこのクラスを通して行う。
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property bool $is_done
 * @property \Illuminate\Support\Carbon|null $due_date
 */
class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    /**
     * 一括代入（Task::create($request->validated()) など）を許可するカラム。
     * ここに書いていないカラムはフォームから送られても無視される＝安全装置。
     */
    protected $fillable = [
        'title',
        'description',
        'is_done',
        'due_date',
    ];

    /**
     * DBの値をPHPの型へ変換するルール。
     * is_done は 0/1 で保存されるが、PHP側では true/false として扱える。
     */
    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'due_date' => 'date',
        ];
    }

    /**
     * ローカルスコープ：Task::notDone()->get() のように呼べる絞り込み条件。
     * 同じ WHERE 句をコントローラーに何度も書かずに済む。
     */
    public function scopeDone(Builder $query): Builder
    {
        return $query->where('is_done', true);
    }

    public function scopeNotDone(Builder $query): Builder
    {
        return $query->where('is_done', false);
    }

    /**
     * アクセサ：期限切れかどうか。
     * ビューからは $task->is_overdue とプロパティのように書ける。
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->is_done || $this->due_date === null) {
            return false;
        }

        return $this->due_date->isPast();
    }
}
