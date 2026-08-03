{{--
    API 学習用の画面。

    Blade版（tasks/index.blade.php）との一番の違いは、
    「サーバーが完成した HTML を返しているかどうか」。

    ・Blade版 … PHPが $tasks をループして HTML を組み立てて返す。
                 追加や削除のたびにページ全体が読み込み直される。
    ・API版  … サーバーは JSON を返すだけ。HTML を組み立てるのは
                 ブラウザ側の JavaScript。だから画面がリロードされない。

    このページ自体には PHP の変数が1つも渡ってきていない点に注目。
    routes/web.php で Route::view('tasks-api', 'tasks.api') と書いただけで、
    コントローラーすら通っていない。データは全部あとから fetch で取ってくる。
--}}
@extends('layouts.app')

@section('title', 'タスク一覧（API版）')

@section('content')
    <div class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px;">
            <strong>タスク一覧（API版）</strong>
            <span class="sub" style="font-size:12px; color:var(--muted);">GET /api/tasks</span>
        </div>

        {{-- Blade版はリンク遷移だったが、こちらは fetch でデータだけ取り直す --}}
        <div class="filters">
            <a href="#" data-filter="all" class="active">すべて（-）</a>
            <a href="#" data-filter="todo">未完了（-）</a>
            <a href="#" data-filter="done">完了（-）</a>
        </div>

        {{-- ここの中身は JavaScript が書き込む。最初は空っぽ --}}
        <ul class="tasks" id="task-list">
            <li><div class="empty">読み込み中…</div></li>
        </ul>

        <div class="pager" id="pager" style="display:none;">
            <span><a href="#" id="prev-page">← 前へ</a></span>
            <span id="page-info"></span>
            <span><a href="#" id="next-page">次へ →</a></span>
        </div>
    </div>

    <div class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px;">
            <strong>新規追加</strong>
            <span class="sub" style="font-size:12px; color:var(--muted);">POST /api/tasks</span>
        </div>

        {{--
            action も method も書いていないただの入れ物。
            @csrf も無い。送信は JavaScript が fetch で行う。
        --}}
        <form id="create-form">
            <div class="field">
                <label for="title">タスク名</label>
                <input type="text" id="title" name="title" placeholder="例：牛乳を買う" autocomplete="off">
            </div>
            <div class="field">
                <label for="due_date">期限（任意）</label>
                <input type="date" id="due_date" name="due_date">
            </div>

            {{-- サーバーが返した 422 のエラー内容をここに表示する --}}
            <div id="form-errors" class="alert-error" style="display:none;"></div>

            <button type="submit" class="btn btn-primary">追加する</button>
        </form>
    </div>

    <div class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px;">
            <strong>通信ログ</strong>
            <button type="button" class="btn btn-sm" id="clear-log">クリア</button>
        </div>
        <p style="font-size:12px; color:var(--muted); margin:0 0 10px;">
            ボタンを押すたびに、ブラウザとサーバーの間で何がやり取りされているかがここに出る。
            <br>→ が送ったリクエスト、← が返ってきたレスポンス。
        </p>
        <div class="log" id="log"><div class="row empty-log">まだ通信していません。</div></div>
    </div>

    {{--
        下の @@verbatim ～ @@endverbatim は「この中だけ Blade の記法を無効にする」印。
        JavaScript のテンプレートリテラルと Blade の記号がぶつかる事故を防げる。

        ※ ここで @@ と2つ重ねているのは、Blade に本物の命令だと勘違いされないためのエスケープ。
           verbatim ブロックの切り出しは Blade コメントの削除より先に行われるので、
           コメントの中でも1つ書きにすると、そこからブロックが始まってしまう。
    --}}
    @verbatim
    <script>
        // ===================================================================
        // 1. 設定と状態
        // ===================================================================

        // 叩き先。routes/api.php に 'tasks' と書いたものが /api/tasks になっている
        const API = '/api/tasks';

        // 今どの絞り込みで、何ページ目を見ているか。画面はこの状態をもとに描き直す
        const state = { filter: 'all', page: 1 };

        // ===================================================================
        // 2. API を叩く共通関数
        //    すべての通信をここ1本に通すことで、ログ出力もまとめて書ける
        // ===================================================================
        async function callApi(method, url, body = null) {
            logRequest(method, url, body);

            const headers = {
                // 「JSON で返してくれ」という意思表示。
                // これが無いと、エラー時に HTML のエラーページが返ってくることがある
                'Accept': 'application/json',
            };

            // ★ Blade版には必ず付いていた @csrf トークンを、ここでは一切送っていない ★
            //   /api/～ は web ミドルウェアグループの外にいるので CSRF 検証が無いため。
            //   （bootstrap/app.php の withRouting(api: ...) の効果）

            const options = { method, headers };

            if (body !== null) {
                // 「これから JSON を送るで」という宣言。これが無いとサーバーが中身を読めない
                headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(body);
            }

            const res = await fetch(url, options);

            // 204 No Content は本文が空なので .json() を呼ぶとエラーになる
            const json = res.status === 204 ? null : await res.json();

            logResponse(res.status, res.statusText, json);

            return { status: res.status, ok: res.ok, json };
        }

        // ===================================================================
        // 3. 各操作
        // ===================================================================

        // 一覧を取り直して描き直す
        async function loadTasks() {
            const url = `${API}?filter=${state.filter}&page=${state.page}`;
            const { ok, json } = await callApi('GET', url);
            if (!ok) return;

            // 最終ページのタスクを全部消した時など、範囲外のページに残ってしまう対策
            if (json.data.length === 0 && state.page > 1) {
                state.page = 1;
                return loadTasks();
            }

            renderTasks(json.data);
            renderCounts(json.counts);
            renderPager(json.meta);
        }

        // 追加
        async function createTask(title, dueDate) {
            const { status, ok, json } = await callApi('POST', API, {
                title: title,
                // 空文字ではなく null を送る（バリデーションの nullable に合わせる）
                due_date: dueDate || null,
            });

            if (status === 422) {
                // サーバーが返した {"errors": {"title": ["タスク名は必須です"]}} を画面に出す
                showErrors(json.errors);
                return false;
            }
            if (!ok) return false;

            clearErrors();
            return true;
        }

        // 完了 / 未完了の切り替え
        async function toggleTask(id) {
            await callApi('PATCH', `${API}/${id}/toggle`);
            await loadTasks();
        }

        // 削除
        async function deleteTask(id, title) {
            if (!confirm(`「${title}」を削除します。よろしいですか？`)) return;
            await callApi('DELETE', `${API}/${id}`);
            await loadTasks();
        }

        // ===================================================================
        // 4. 画面を組み立てる（Blade版で PHP がやっていた仕事を JS がやる）
        // ===================================================================

        function renderTasks(tasks) {
            const list = document.getElementById('task-list');

            if (tasks.length === 0) {
                list.innerHTML = '<li><div class="empty">タスクはまだありません。</div></li>';
                return;
            }

            list.innerHTML = tasks.map(task => {
                // is_overdue は DB の列ではなく、TaskResource が計算して入れてくれた値
                const meta = task.due_date
                    ? `<span class="${task.is_overdue ? 'overdue' : ''}">期限: ${task.due_date.replaceAll('-', '/')}${task.is_overdue ? '（期限切れ）' : ''}</span>`
                    : '期限なし';

                return `
                    <li class="${task.is_done ? 'done' : ''}">
                        <input type="checkbox" data-action="toggle" data-id="${task.id}"
                               ${task.is_done ? 'checked' : ''} style="margin-top:6px;"
                               aria-label="完了状態を切り替える">
                        <div class="task-main">
                            <span class="task-title">${escapeHtml(task.title)}</span>
                            <div class="task-meta">${meta}</div>
                        </div>
                        <div class="task-actions">
                            <button type="button" class="btn btn-sm btn-danger"
                                    data-action="delete" data-id="${task.id}"
                                    data-title="${escapeHtml(task.title)}">削除</button>
                        </div>
                    </li>
                `;
            }).join('');
        }

        // フィルタボタンの件数表示。index() の additional() で足した counts を使う
        function renderCounts(counts) {
            const labels = {
                all: `すべて（${counts.todo + counts.done}）`,
                todo: `未完了（${counts.todo}）`,
                done: `完了（${counts.done}）`,
            };
            document.querySelectorAll('[data-filter]').forEach(link => {
                const key = link.dataset.filter;
                link.textContent = labels[key];
                link.classList.toggle('active', key === state.filter);
            });
        }

        // ページ送り。paginate() が付けてくれた meta をそのまま使える
        function renderPager(meta) {
            const pager = document.getElementById('pager');
            if (meta.last_page <= 1) {
                pager.style.display = 'none';
                return;
            }
            pager.style.display = 'flex';
            document.getElementById('page-info').textContent = `${meta.current_page} / ${meta.last_page} ページ`;
            document.getElementById('prev-page').style.visibility = meta.current_page > 1 ? 'visible' : 'hidden';
            document.getElementById('next-page').style.visibility = meta.current_page < meta.last_page ? 'visible' : 'hidden';
        }

        function showErrors(errors) {
            const box = document.getElementById('form-errors');
            // errors は {"title": ["メッセージ1", "メッセージ2"]} という形
            const messages = Object.values(errors).flat();
            box.innerHTML = messages.map(escapeHtml).join('<br>');
            box.style.display = 'block';
        }

        function clearErrors() {
            const box = document.getElementById('form-errors');
            box.style.display = 'none';
            box.innerHTML = '';
        }

        // ユーザーが入力した文字をそのまま HTML に混ぜると危ないので無害化する
        // （Blade の {{ }} が自動でやってくれていたこと）
        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
            return String(value).replace(/[&<>"']/g, char => map[char]);
        }

        // ===================================================================
        // 5. 通信ログ
        // ===================================================================

        function appendLog(html) {
            const log = document.getElementById('log');
            const placeholder = log.querySelector('.empty-log');
            if (placeholder) placeholder.remove();

            const row = document.createElement('div');
            row.className = 'row';
            row.innerHTML = html;
            log.appendChild(row);
            log.scrollTop = log.scrollHeight; // 常に最新行を見せる
        }

        function logRequest(method, url, body) {
            let html = `<span class="req">→ ${method} ${escapeHtml(url)}</span>`;
            if (body !== null) {
                html += `\n<span class="body">   ${escapeHtml(JSON.stringify(body))}</span>`;
            }
            appendLog(html);
        }

        function logResponse(status, statusText, json) {
            // 200番台は成功、それ以外はエラー色で出す
            const cssClass = status >= 200 && status < 300 ? 'res' : 'err';
            let html = `<span class="${cssClass}">← ${status} ${escapeHtml(statusText)}</span>`;

            if (json === null) {
                html += `\n<span class="body">   （本文なし）</span>`;
            } else {
                // 長いと読みにくいので先頭300文字だけ
                const text = JSON.stringify(json);
                const shortened = text.length > 300 ? text.slice(0, 300) + ' …' : text;
                html += `\n<span class="body">   ${escapeHtml(shortened)}</span>`;
            }
            appendLog(html);
        }

        // ===================================================================
        // 6. イベント登録
        // ===================================================================

        // 絞り込み
        document.querySelectorAll('[data-filter]').forEach(link => {
            link.addEventListener('click', event => {
                event.preventDefault(); // ページ遷移させない
                state.filter = link.dataset.filter;
                state.page = 1;
                loadTasks();
            });
        });

        // 一覧の中身は後から作られるので、親要素側で click を受け取る（イベント委譲）
        document.getElementById('task-list').addEventListener('click', event => {
            const target = event.target;
            if (target.dataset.action === 'toggle') {
                toggleTask(target.dataset.id);
            }
            if (target.dataset.action === 'delete') {
                deleteTask(target.dataset.id, target.dataset.title);
            }
        });

        // 追加フォーム
        document.getElementById('create-form').addEventListener('submit', async event => {
            event.preventDefault(); // ブラウザ標準の送信（＝ページリロード）を止める

            const titleInput = document.getElementById('title');
            const dueInput = document.getElementById('due_date');

            const created = await createTask(titleInput.value, dueInput.value);
            if (!created) return;

            titleInput.value = '';
            dueInput.value = '';
            state.page = 1;
            await loadTasks();
        });

        // ページ送り
        document.getElementById('prev-page').addEventListener('click', event => {
            event.preventDefault();
            state.page -= 1;
            loadTasks();
        });
        document.getElementById('next-page').addEventListener('click', event => {
            event.preventDefault();
            state.page += 1;
            loadTasks();
        });

        // ログのクリア
        document.getElementById('clear-log').addEventListener('click', () => {
            document.getElementById('log').innerHTML = '<div class="row empty-log">まだ通信していません。</div>';
        });

        // 起動時に1回だけ読み込む
        loadTasks();
    </script>
    @endverbatim
@endsection
