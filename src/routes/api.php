<?php

use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API ルート
|--------------------------------------------------------------------------
| routes/web.php との違いは大きく3つ。
|
| 1. URL に自動で /api が付く
|    ここに 'tasks' と書くと、実際のURLは /api/tasks になる。
|    （bootstrap/app.php の withRouting(api: ...) が付けてくれる）
|
| 2. セッションと CSRF が無い
|    web ルートは「ブラウザのフォーム送信」が前提なので @csrf が必須だったが、
|    API ルートはトークンを送らなくても POST / PATCH / DELETE が通る。
|    その代わりログイン状態も覚えていない（毎回まっさらなリクエスト）。
|
| 3. 返すのは View ではなく JSON
|    なので画面をリロードせずに JavaScript から呼べる。
|
| 確認コマンド： php artisan route:list --path=api
*/

/*
| resource の7つ（apiResource なら5つ）に含まれない独自アクション。
| web.php と同じく resource より先に書く。
| 後に書くと /api/tasks/{task} 側に吸い込まれてしまうため。
*/
Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])
    ->name('api.tasks.toggle');

/*
| Route::apiResource は Route::resource から
| create（作成フォーム画面）と edit（編集フォーム画面）を抜いた5つを定義する。
| API は HTML のフォームを返さないので、この2つは不要という考え方。
|
| GET    /api/tasks        → index   （一覧）
| POST   /api/tasks        → store   （保存）  ステータス 201
| GET    /api/tasks/{task} → show    （詳細）
| PUT    /api/tasks/{task} → update  （更新）
| PATCH  /api/tasks/{task} → update  （部分更新）
| DELETE /api/tasks/{task} → destroy （削除）  ステータス 204
|
| ->names('api.tasks') はルート名の重複対策。
| web.php 側が既に tasks.index などを使っているので、
| こちらは api.tasks.index という名前にしてぶつからないようにする。
*/
Route::apiResource('tasks', TaskController::class)->names('api.tasks');
