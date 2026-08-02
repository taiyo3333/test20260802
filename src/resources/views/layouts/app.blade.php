{{--
    共通レイアウト。
    各ページは @extends('layouts.app') でこれを継承し、
    @section('content') の中身だけを書けばよくなる。
--}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- @yield は「子ビューが差し込む場所」。第2引数は未指定時のデフォルト --}}
    <title>@yield('title', 'タスク管理') | MVC学習アプリ</title>
    <style>
        :root {
            --bg: #f5f6f8;
            --panel: #ffffff;
            --border: #e2e5ea;
            --text: #1f2933;
            --muted: #6b7684;
            --primary: #2f6feb;
            --danger: #d64545;
            --success: #2f9e63;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: system-ui, -apple-system, "Hiragino Sans", "Noto Sans JP", sans-serif;
            line-height: 1.7;
        }
        .wrap { max-width: 760px; margin: 0 auto; padding: 24px 16px 64px; }
        header.site { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
        header.site h1 { font-size: 22px; margin: 0; }
        header.site .sub { color: var(--muted); font-size: 13px; }
        a { color: var(--primary); }
        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: 10px; padding: 20px; }
        .panel + .panel { margin-top: 16px; }

        /* ボタン */
        .btn {
            display: inline-block; padding: 8px 14px; border-radius: 6px; border: 1px solid var(--border);
            background: #fff; color: var(--text); font-size: 14px; cursor: pointer;
            text-decoration: none; line-height: 1.4;
        }
        .btn:hover { background: #f0f2f5; }
        .btn-primary { background: var(--primary); border-color: var(--primary); color: #fff; }
        .btn-primary:hover { background: #2559c4; }
        .btn-danger { color: var(--danger); border-color: #f0c9c9; }
        .btn-danger:hover { background: #fdf2f2; }
        .btn-sm { padding: 4px 10px; font-size: 13px; }

        /* フォーム */
        .field { margin-bottom: 16px; }
        .field label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; }
        .field input[type="text"], .field input[type="date"], .field textarea {
            width: 100%; padding: 9px 11px; border: 1px solid var(--border);
            border-radius: 6px; font: inherit; background: #fff; color: inherit;
        }
        .field textarea { min-height: 110px; resize: vertical; }
        .field .error { color: var(--danger); font-size: 13px; margin-top: 4px; }
        .field input.is-invalid, .field textarea.is-invalid { border-color: var(--danger); }
        .checkbox { display: flex; align-items: center; gap: 8px; font-size: 14px; }

        /* 通知 */
        .flash {
            background: #e8f5ee; border: 1px solid #b8e0ca; color: #1d6b45;
            padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;
        }
        .alert-error {
            background: #fdf2f2; border: 1px solid #f3c9c9; color: #a13030;
            padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;
        }

        /* タスク一覧 */
        .filters { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .filters a { text-decoration: none; font-size: 13px; padding: 5px 12px; border-radius: 999px; border: 1px solid var(--border); background: #fff; color: var(--muted); }
        .filters a.active { background: var(--primary); border-color: var(--primary); color: #fff; }
        ul.tasks { list-style: none; margin: 0; padding: 0; }
        ul.tasks li { display: flex; align-items: flex-start; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--border); }
        ul.tasks li:last-child { border-bottom: none; }
        .task-main { flex: 1; min-width: 0; }
        .task-title { font-weight: 600; text-decoration: none; color: var(--text); }
        .task-title:hover { text-decoration: underline; }
        .task-meta { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .done .task-title { color: var(--muted); text-decoration: line-through; }
        .overdue { color: var(--danger); font-weight: 600; }
        .task-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .empty { color: var(--muted); text-align: center; padding: 32px 0; }
        .pager { display: flex; justify-content: space-between; margin-top: 16px; font-size: 14px; }
        .desc { white-space: pre-wrap; }
        form.inline { display: inline; margin: 0; }
    </style>
</head>
<body>
    <div class="wrap">
        <header class="site">
            <h1><a href="{{ route('tasks.index') }}" style="text-decoration:none;color:inherit;">📋 タスク管理</a></h1>
            <span class="sub">Laravel MVC 学習アプリ</span>
        </header>

        {{-- with('status', ...) で渡されたフラッシュメッセージ。次のリクエストで自動的に消える --}}
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @yield('content')
    </div>
</body>
</html>
