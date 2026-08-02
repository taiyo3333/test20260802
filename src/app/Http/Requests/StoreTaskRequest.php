<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * タスク新規作成時の入力チェック（フォームリクエスト）。
 *
 * コントローラーの引数に書いておくと、アクションが実行される前に
 * 自動でバリデーションが走る。失敗したら元のフォームへ自動でリダイレクトされ、
 * エラーメッセージと入力値（old）がビューに渡される。
 */
class StoreTaskRequest extends FormRequest
{
    /**
     * このリクエストを実行してよいか（権限チェック）。
     * 今回はログイン機能なしの学習用なので全員に許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール。
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['nullable', 'date'],
        ];
    }

    /**
     * エラーメッセージ内で使われる項目名の日本語訳。
     * 「titleは必須です」→「タスク名は必須です」と表示される。
     */
    public function attributes(): array
    {
        return [
            'title' => 'タスク名',
            'description' => '詳細',
            'due_date' => '期限',
        ];
    }
}
