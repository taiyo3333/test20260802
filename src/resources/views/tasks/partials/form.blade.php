{{--
    新規作成と編集で共通の入力項目。
    @include で読み込むことで同じHTMLを2度書かずに済む（部分ビュー）。

    $task … 編集時はTaskモデル、新規作成時は null
--}}

{{-- old() は「バリデーション失敗で戻ってきた時に入力値を復元する」関数。
     第2引数は初期値（編集時は現在の値、新規時は空）。 --}}
<div class="field">
    <label for="title">タスク名 <span style="color:#d64545;">*</span></label>
    <input type="text" id="title" name="title"
           value="{{ old('title', $task?->title) }}"
           class="{{ $errors->has('title') ? 'is-invalid' : '' }}"
           required maxlength="255" autofocus>
    {{-- @error はその項目のエラーがある時だけ中身を表示する --}}
    @error('title')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

<div class="field">
    <label for="description">詳細（任意）</label>
    <textarea id="description" name="description"
              class="{{ $errors->has('description') ? 'is-invalid' : '' }}"
              maxlength="1000">{{ old('description', $task?->description) }}</textarea>
    @error('description')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

<div class="field">
    <label for="due_date">期限（任意）</label>
    <input type="date" id="due_date" name="due_date"
           value="{{ old('due_date', $task?->due_date?->format('Y-m-d')) }}"
           class="{{ $errors->has('due_date') ? 'is-invalid' : '' }}">
    @error('due_date')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

@if ($task)
    <div class="field">
        <label class="checkbox">
            {{-- value="1" を送る。チェックを外すと何も送信されないため、
                 UpdateTaskRequest の prepareForValidation() で false に補正している --}}
            <input type="checkbox" name="is_done" value="1"
                   {{ old('is_done', $task->is_done) ? 'checked' : '' }}>
            このタスクは完了済み
        </label>
    </div>
@endif
