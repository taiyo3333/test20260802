<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // API ルートを有効化する1行。
        // これだけで routes/api.php の中身に自動で
        //   ・URL の先頭に /api が付く
        //   ・api ミドルウェアグループが適用される（セッションもCSRFも無い）
        // が効くようになる。web と api で「通る道」が違うのがポイント。
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 「/api/～ へのリクエストで例外が起きたら、HTMLのエラーページではなく
        //  　JSON で返す」という設定（Laravel 標準で最初から入っている）。
        //
        // これがあるおかげで API 側では
        //   ・存在しないIDを指定 → 404 が JSON で返る
        //   ・バリデーション失敗 → 422 + {"message":..., "errors":{...}} が返る
        // となり、コントローラーに try-catch を1つも書かなくて済む。
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
