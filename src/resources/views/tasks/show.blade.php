@extends('layouts.app')

@section('title', $task->title)

@section('content')
    <div class="panel">
        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
            <h2 style="margin:0; font-size:19px;">{{ $task->title }}</h2>
            <span class="btn btn-sm" style="cursor:default;">
                {{ $task->is_done ? '✅ 完了' : '⏳ 未完了' }}
            </span>
        </div>

        <p class="task-meta" style="margin-top:6px;">
            @if ($task->due_date)
                <span class="{{ $task->is_overdue ? 'overdue' : '' }}">
                    期限: {{ $task->due_date->format('Y年n月j日') }}{{ $task->is_overdue ? '（期限切れ）' : '' }}
                </span>
            @else
                期限なし
            @endif
            ／ 作成: {{ $task->created_at->format('Y/m/d H:i') }}
            ／ 更新: {{ $task->updated_at->format('Y/m/d H:i') }}
        </p>

        <hr style="border:none; border-top:1px solid var(--border); margin:16px 0;">

        {{-- {{ }} は自動でHTMLエスケープされる＝XSS対策が標準で効いている --}}
        <div class="desc">{{ $task->description ?: '（詳細メモはありません）' }}</div>

        <div style="display:flex; gap:8px; margin-top:24px; flex-wrap:wrap;">
            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-primary">編集</a>

            <form method="POST" action="{{ route('tasks.toggle', $task) }}" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn">{{ $task->is_done ? '未完了に戻す' : '完了にする' }}</button>
            </form>

            <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="inline"
                  onsubmit="return confirm('このタスクを削除します。よろしいですか？');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">削除</button>
            </form>

            <a href="{{ route('tasks.index') }}" class="btn" style="margin-left:auto;">← 一覧へ戻る</a>
        </div>
    </div>
@endsection
