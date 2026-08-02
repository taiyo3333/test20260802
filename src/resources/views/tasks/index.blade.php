{{--
    MVC の「V（View）」。一覧画面。
    コントローラーの index() から渡された $tasks / $filter などを表示するだけ。
    ビューでDBを触らないのが原則。
--}}
@extends('layouts.app')

@section('title', 'タスク一覧')

@section('content')
    <div class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px;">
            <strong>タスク一覧</strong>
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">＋ 新規追加</a>
        </div>

        <div class="filters">
            <a href="{{ route('tasks.index') }}" class="{{ $filter === 'all' ? 'active' : '' }}">
                すべて（{{ $todoCount + $doneCount }}）
            </a>
            <a href="{{ route('tasks.index', ['filter' => 'todo']) }}" class="{{ $filter === 'todo' ? 'active' : '' }}">
                未完了（{{ $todoCount }}）
            </a>
            <a href="{{ route('tasks.index', ['filter' => 'done']) }}" class="{{ $filter === 'done' ? 'active' : '' }}">
                完了（{{ $doneCount }}）
            </a>
        </div>

        <ul class="tasks">
            {{-- @forelse は「データがある時」と「空の時」を1つで書ける便利構文 --}}
            @forelse ($tasks as $task)
                <li class="{{ $task->is_done ? 'done' : '' }}">
                    {{-- 完了トグル。GETではなくPATCHで状態を変えるのがREST的に正しい --}}
                    <form method="POST" action="{{ route('tasks.toggle', $task) }}" style="margin-top:2px;">
                        @csrf
                        @method('PATCH')
                        <input type="checkbox"
                               onchange="this.form.submit()"
                               {{ $task->is_done ? 'checked' : '' }}
                               aria-label="完了状態を切り替える">
                    </form>

                    <div class="task-main">
                        <a href="{{ route('tasks.show', $task) }}" class="task-title">{{ $task->title }}</a>
                        <div class="task-meta">
                            @if ($task->due_date)
                                <span class="{{ $task->is_overdue ? 'overdue' : '' }}">
                                    期限: {{ $task->due_date->format('Y/m/d') }}{{ $task->is_overdue ? '（期限切れ）' : '' }}
                                </span>
                            @else
                                期限なし
                            @endif
                        </div>
                    </div>

                    <div class="task-actions">
                        <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm">編集</a>
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="inline"
                              onsubmit="return confirm('「{{ $task->title }}」を削除します。よろしいですか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">削除</button>
                        </form>
                    </div>
                </li>
            @empty
                <li><div class="empty">タスクはまだありません。「＋ 新規追加」から登録してみましょう。</div></li>
            @endforelse
        </ul>

        {{-- ページ送り。1ページ分しかない時は何も表示しない --}}
        @if ($tasks->hasPages())
            <div class="pager">
                <span>
                    @if ($tasks->previousPageUrl())
                        <a href="{{ $tasks->previousPageUrl() }}">← 前へ</a>
                    @endif
                </span>
                <span>{{ $tasks->currentPage() }} / {{ $tasks->lastPage() }} ページ</span>
                <span>
                    @if ($tasks->nextPageUrl())
                        <a href="{{ $tasks->nextPageUrl() }}">次へ →</a>
                    @endif
                </span>
            </div>
        @endif
    </div>
@endsection
