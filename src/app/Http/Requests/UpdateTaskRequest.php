<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * タスク編集時の入力チェック。
 *
 * 今回は新規作成とルールがほぼ同じだが、「作成時だけ必須」「更新時は重複チェックから自分を除外」
 * のように条件が変わることが多いため、Laravelでは最初からファイルを分けておく。
 */
class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['nullable', 'date'],
            'is_done' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'タスク名',
            'description' => '詳細',
            'due_date' => '期限',
            'is_done' => '完了',
        ];
    }

    /**
     * バリデーション前に値を整える。
     * チェックボックスはオフのとき何も送信されないので、
     * ここで true / false に変換しておく。
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_done' => $this->boolean('is_done'),
        ]);
    }
}
