<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web ルート
|--------------------------------------------------------------------------
| 「どのURLに来たら、どのコントローラーのどのメソッドを動かすか」を決める場所。
| MVCの入口にあたる。
*/

// トップページはタスク一覧へ飛ばす
Route::redirect('/', '/tasks');

// resource の7つには含まれない独自アクション。
// resource より先に書くのがポイント（後に書くと /tasks/{task} 側に解釈される）。
Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])
    ->name('tasks.toggle');

// API学習用の画面。JavaScript から /api/tasks を叩くだけのページなので、
// コントローラーを作らず Route::view でビューを直接返している。
//
// URLを /tasks/api にしていないのは、下の Route::resource が持つ
// /tasks/{task}（show）に「{task} = "api"」として吸い込まれてしまうため。
// toggle を resource より上に書いているのと同じ話。
Route::view('tasks-api', 'tasks.api')->name('tasks.api');

/*
| Route::resource は以下の7つを一気に定義してくれる。
|
| GET    /tasks             → index   （一覧）        route('tasks.index')
| GET    /tasks/create      → create  （作成フォーム） route('tasks.create')
| POST   /tasks             → store   （保存）        route('tasks.store')
| GET    /tasks/{task}      → show    （詳細）        route('tasks.show', $task)
| GET    /tasks/{task}/edit → edit    （編集フォーム） route('tasks.edit', $task)
| PUT    /tasks/{task}      → update  （更新）        route('tasks.update', $task)
| DELETE /tasks/{task}      → destroy （削除）        route('tasks.destroy', $task)
|
| 確認コマンド： php artisan route:list
*/
Route::resource('tasks', TaskController::class);
