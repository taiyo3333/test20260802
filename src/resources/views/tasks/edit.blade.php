@extends('layouts.app')

@section('title', 'タスクを編集')

@section('content')
    <div class="panel">
        <strong>タスクを編集</strong>

        <form method="POST" action="{{ route('tasks.update', $task) }}" style="margin-top:16px;">
            @csrf
            {{-- HTMLのフォームは GET と POST しか送れない。
                 @method('PUT') が隠しフィールドを出力し、Laravel側がPUTとして扱ってくれる --}}
            @method('PUT')

            @include('tasks.partials.form', ['task' => $task])

            <div style="display:flex; gap:8px; margin-top:20px;">
                <button type="submit" class="btn btn-primary">更新する</button>
                <a href="{{ route('tasks.index') }}" class="btn">キャンセル</a>
            </div>
        </form>
    </div>
@endsection
