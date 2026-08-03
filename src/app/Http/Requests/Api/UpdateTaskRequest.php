<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * API のタスク更新用リクエスト。
 *
 * ■ なぜ Web 版（App\Http\Requests\UpdateTaskRequest）を使い回さないのか
 *
 * Web 版には prepareForValidation() で
 *     $this->merge(['is_done' => $this->boolean('is_done')]);
 * という処理が入っている。
 * これは「HTMLのチェックボックスは、チェックを外すと何も送信されない」という
 * ブラウザの仕様に対する対策で、Web のフォームでは正しい動き。
 *
 * ところが API でこれを使うと困ったことになる。
 * 「タイトルだけ直したい」つもりで
 *     PATCH /api/tasks/1  {"title": "新しい名前"}
 * を送ると、is_done が送られていない → false 扱い → 完了だったタスクが
 * 勝手に未完了に戻る、という事故が起きる。
 *
 * なので API 用は prepareForValidation() を持たせず、
 * 代わりに sometimes を使って「送られてきた項目だけチェックする」形にする。
 *
 * ■ sometimes とは
 * 「そのキーがリクエストに含まれている時だけ、続くルールを適用する」という指定。
 * これで部分更新（PATCH）が自然に書ける。
 */
class UpdateTaskRequest extends FormRequest
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
     * 失敗した時、Web 版はフォーム画面へリダイレクトされたが、
     * API では 422 と {"message":..., "errors":{...}} の JSON が自動で返る。
     * （bootstrap/app.php の shouldRenderJsonWhen が効いているため）
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 送ってきた場合は必須（空文字はNG）。送らなければ何もしない
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            // true/false, 1/0, "1"/"0" を受け付ける
            'is_done' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * エラーメッセージ内で使われる項目名の日本語訳。
     */
    public function attributes(): array
    {
        return [
            'title' => 'タスク名',
            'description' => '詳細',
            'due_date' => '期限',
            'is_done' => '完了状態',
        ];
    }
}
