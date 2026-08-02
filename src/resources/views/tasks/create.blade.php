@extends('layouts.app')

@section('title', 'タスクを追加')

@section('content')
    <div class="panel">
        <strong>タスクを追加</strong>

        {{-- 送信先は route('tasks.store')＝POST /tasks --}}
        <form method="POST" action="{{ route('tasks.store') }}" style="margin-top:16px;">
            {{-- @csrf は必須。これがないと419エラーになる（なりすまし投稿を防ぐ仕組み） --}}
            @csrf

            @include('tasks.partials.form', ['task' => null])

            <div style="display:flex; gap:8px; margin-top:20px;">
                <button type="submit" class="btn btn-primary">保存する</button>
                <a href="{{ route('tasks.index') }}" class="btn">キャンセル</a>
            </div>
        </form>
    </div>
@endsection
