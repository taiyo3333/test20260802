# Laravel MVC 学習アプリ（タスク管理）

Laravel の MVC を「動くもの」で理解するための最小構成のタスク管理アプリです。

- 一覧・追加・詳細・編集・削除（CRUD）
- 完了 / 未完了の切り替え
- 未完了 / 完了での絞り込み、ページ送り
- 入力バリデーション（日本語メッセージ）
- **同じデータを JSON API 経由で操作する画面**（→ [4. API 編](#4-api-編)）

| 画面 | URL | 中身 |
|------|-----|------|
| Blade版 | http://localhost:8086/tasks | サーバーが HTML を組み立てて返す。操作のたびにページが再読み込みされる |
| API版 | http://localhost:8086/tasks-api | サーバーは JSON を返すだけ。画面は JavaScript が組み立てる（リロードなし） |
| React版 | http://localhost:5174/ | **別のサーバー**から配られたページが API だけ叩く（→ [`frontend/README.md`](../frontend/README.md)） |

**3つとも同じ `tasks` テーブルを見ています。** どれかで追加したタスクは他にも出ます。
「M（Model）と DB は共通で、出口だけが3つある」という関係を確かめてみてください。

React 版だけは別プロセスなので、起動にひと手間かかります。

```bash
docker compose up -d           # Laravel（API側）
cd frontend && npm run dev     # React（別ターミナル）
```

---

## 1. リクエストが処理される流れ

ブラウザで「＋ 新規追加」→ フォーム送信したときに、何がどの順で動くのか。

```
ブラウザ
  │  POST /tasks  （title=牛乳を買う）
  ▼
routes/web.php                    ← どのコントローラーへ渡すか決める
  │  Route::resource → TaskController@store
  ▼
app/Http/Requests/StoreTaskRequest.php   ← 【入力チェック】
  │  失敗したらここでフォームへ引き返す（エラーと入力値を持って）
  ▼
app/Http/Controllers/TaskController.php  ← 【C】交通整理
  │  Task::create(...) をモデルに依頼
  ▼
app/Models/Task.php                      ← 【M】DBとのやり取り
  │  INSERT INTO tasks ...
  ▼
redirect()->route('tasks.index')  ← 保存後はリダイレクト（PRGパターン）
  │
  ▼
resources/views/tasks/index.blade.php    ← 【V】HTMLを組み立てる
  │
  ▼
ブラウザに一覧が表示される
```

**ポイント**：View は「渡されたデータを表示するだけ」。View の中で DB を触らないのが原則です。

---

## 2. ファイルの役割

| 種類 | ファイル | 役割 |
|------|----------|------|
| ルート | `src/routes/web.php` | URL とコントローラーの対応表 |
| **C** | `src/app/Http/Controllers/TaskController.php` | 受け取る → Model に依頼 → View を返す |
| **M** | `src/app/Models/Task.php` | tasks テーブルに対応。取得条件や型変換もここ |
| **V** | `src/resources/views/layouts/app.blade.php` | 全ページ共通の枠（ヘッダー・CSS） |
| **V** | `src/resources/views/tasks/index.blade.php` | 一覧 |
| **V** | `src/resources/views/tasks/create.blade.php` | 新規作成フォーム |
| **V** | `src/resources/views/tasks/edit.blade.php` | 編集フォーム |
| **V** | `src/resources/views/tasks/show.blade.php` | 詳細 |
| **V** | `src/resources/views/tasks/partials/form.blade.php` | 作成と編集で共通の入力欄 |
| 検証 | `src/app/Http/Requests/StoreTaskRequest.php` | 新規作成時のバリデーション |
| 検証 | `src/app/Http/Requests/UpdateTaskRequest.php` | 更新時のバリデーション |
| DB | `src/database/migrations/*_create_tasks_table.php` | テーブル定義 |
| DB | `src/database/factories/TaskFactory.php` | ダミーデータの作り方 |
| DB | `src/database/seeders/TaskSeeder.php` | 初期データ投入 |
| テスト | `src/tests/Feature/TaskTest.php` | URL を叩いて動作を検証 |

API 編で追加したファイルは以下です（詳しくは [4. API 編](#4-api-編)）。

| 種類 | ファイル | 役割 |
|------|----------|------|
| ルート | `src/routes/api.php` | `/api/～` の対応表。CSRF もセッションも無い道 |
| **C** | `src/app/Http/Controllers/Api/TaskController.php` | View の代わりに JSON を返すコントローラー |
| 変換 | `src/app/Http/Resources/TaskResource.php` | モデル → JSON の変換ルール（JSON版の View） |
| 検証 | `src/app/Http/Requests/Api/UpdateTaskRequest.php` | API 用の更新バリデーション（部分更新に対応） |
| **V** | `src/resources/views/tasks/api.blade.php` | fetch で API を叩く画面。通信ログ付き |
| テスト | `src/tests/Feature/ApiTaskTest.php` | JSON で叩いて動作を検証 |

### `Route::resource` が作る7本

```
GET    /tasks             → index    一覧
GET    /tasks/create      → create   作成フォーム
POST   /tasks             → store    保存
GET    /tasks/{task}      → show     詳細
GET    /tasks/{task}/edit → edit     編集フォーム
PUT    /tasks/{task}      → update   更新
DELETE /tasks/{task}      → destroy  削除
```

これに加えて `PATCH /tasks/{task}/toggle`（完了切り替え）を独自に定義しています。

---

## 3. 覚えておきたい書き方

| 書き方 | 意味 |
|--------|------|
| `@csrf` | なりすまし投稿を防ぐ隠しトークン。**POSTフォームには必須**（無いと419エラー） |
| `@method('PUT')` | HTMLフォームは GET/POST しか送れないため、PUT/DELETE を伝える |
| `{{ $task->title }}` | 出力時に自動でHTMLエスケープ（XSS対策が標準で効く） |
| `route('tasks.edit', $task)` | URL を直書きせず名前で参照する。URL変更に強い |
| `old('title', $task?->title)` | エラーで戻ってきた時に入力値を復元する |
| `@error('title')` | その項目のエラーがある時だけ表示 |
| `public function show(Task $task)` | URLの `{task}` から自動でモデルを検索（見つからなければ404） |
| `$fillable` | フォームからの一括代入を許可するカラムの制限 |

---

## 4. API 編

ここまでは「サーバーが完成した HTML を返す」作りでした。
API 編では **サーバーは JSON を返すだけにして、画面は JavaScript が組み立てる** 形を作ります。

http://localhost:8086/tasks-api を開いて、追加・チェック・削除をしてみてください。
**ページが一度もリロードされない**のと、画面下の「通信ログ」に何が飛んでいるかが出ます。

### 4-1. リクエストの流れ（Blade版との違い）

```
【Blade版】ページごと作り直す
  ブラウザ ──POST /tasks──▶ Controller ──▶ Model ──▶ DB
                                              │
  ブラウザ ◀──HTML一式（リダイレクト後）───────┘   ← 画面全体が再読み込み


【API版】必要なデータだけやり取りする
  ブラウザのJS ──fetch POST /api/tasks──▶ Api\Controller ──▶ Model ──▶ DB
                     {"title":"牛乳を買う"}         │
  ブラウザのJS ◀──JSON {"data":{...}} 201 ─────────┘
       │
       └─▶ JSが受け取った JSON から <li> を作って画面に差し込む  ← リロードなし
```

Model（`Task.php`）と DB は**どちらも全く同じものを使っています**。変わったのは出口だけです。

### 4-2. web ルートと api ルートの違い

`bootstrap/app.php` に1行足すと `routes/api.php` が有効になります。

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',   // ← これ
    ...
)
```

| | `routes/web.php` | `routes/api.php` |
|---|---|---|
| URL | 書いたまま（`/tasks`） | 先頭に `/api` が付く（`/api/tasks`） |
| CSRF | **必要**（`@csrf` が無いと 419） | **不要**（トークン無しで POST できる） |
| セッション | あり（`session('status')` が使える） | なし（毎回まっさらなリクエスト） |
| 返すもの | View（HTML） | JSON |
| 想定する相手 | ブラウザのフォーム | JavaScript、スマホアプリ、他のサーバー |

CSRF が要らないのは、API ルートが `web` ミドルウェアグループの外を通るからです。
（`api` グループの中身はルートモデルバインディングだけ）

### 4-3. `resource` と `apiResource`

```php
Route::resource('tasks', TaskController::class);          // 7本
Route::apiResource('tasks', ApiTaskController::class);    // 5本
```

`apiResource` は `create`（作成フォーム画面）と `edit`（編集フォーム画面）を作りません。
API は HTML のフォームを返さないので不要、という考え方です。

```
GET    /api/tasks        → index    一覧
POST   /api/tasks        → store    保存
GET    /api/tasks/{task} → show     詳細
PUT    /api/tasks/{task} → update   更新
PATCH  /api/tasks/{task} → update   部分更新
DELETE /api/tasks/{task} → destroy  削除
```

これに加えて `PATCH /api/tasks/{task}/toggle` を独自に定義しています。

> ルート名が web 側の `tasks.index` とぶつかるため、
> `->names('api.tasks')` で `api.tasks.index` という名前にしています。

### 4-4. API リソース（JSON版の View）

コントローラーで `return $task;` と書くだけでも JSON にはなりますが、それだと

- アクセサ（`$task->is_overdue`）が出てこない
- `due_date` が `"2026-08-10T00:00:00.000000Z"` という扱いにくい形で出る
- 将来 `password` のような列が増えたら、うっかり全部外に出てしまう

という問題があります。`TaskResource` を1枚かませて「外に出す形」を自分で決めます。

```php
// src/app/Http/Resources/TaskResource.php
return [
    'id' => $this->id,
    'title' => $this->title,
    'due_date' => $this->due_date?->format('Y-m-d'),  // 好きな形に整形できる
    'is_overdue' => $this->is_overdue,                // ★ここに書かないとJSONに出ない
];
```

**ビューが HTML の見た目を決めるように、リソースは JSON の形を決める係**だと思ってください。

`paginate()` の結果を渡すと、ページ送りの情報が自動で付きます。

```json
{
  "data":  [ { "id": 1, "title": "牛乳を買う", ... } ],
  "links": { "first": "...", "next": "..." },
  "meta":  { "current_page": 1, "last_page": 3, "total": 25 },
  "counts": { "todo": 5, "done": 2 }
}
```

`counts` はコントローラーの `->additional([...])` で自前で足したものです。

### 4-5. ステータスコードで結果を伝える

Web版は「保存できたらリダイレクト」でしたが、API は**数字で結果を伝えます**。

| コード | 意味 | このアプリでの使いどころ |
|--------|------|--------------------------|
| 200 OK | 成功 | 一覧・詳細・更新・toggle |
| 201 Created | 作成できた | `POST /api/tasks` |
| 204 No Content | 成功したが返す中身は無い | `DELETE /api/tasks/{id}` |
| 404 Not Found | そのIDは無い | 存在しないIDを指定した時（自動） |
| 422 Unprocessable | 入力内容がおかしい | バリデーション失敗（自動） |

404 と 422 は**自分で書いていません**。`bootstrap/app.php` の

```php
$exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));
```

が「`/api/～` で例外が起きたら HTML ではなく JSON で返す」と決めているので、
コントローラーに `try-catch` を1つも書かずに済んでいます。

422 の中身はこの形で返ります。JS 側はこれを読んで画面にエラーを出しています。

```json
{ "message": "タスク名は必須です。", "errors": { "title": ["タスク名は必須です。"] } }
```

### 4-6. ハマりどころ：更新用の FormRequest を使い回さない

Web版の `UpdateTaskRequest` には、こんな処理が入っています。

```php
protected function prepareForValidation(): void
{
    $this->merge(['is_done' => $this->boolean('is_done')]);
}
```

これは「HTMLのチェックボックスは、外すと何も送信されない」というブラウザの仕様への対策で、
**Web のフォームでは正しい動き**です。

ところが API でこれを使い回すと事故ります。タイトルだけ直すつもりで

```
PATCH /api/tasks/1   {"title": "新しい名前"}
```

を送ると、`is_done` が送られていない → `false` 扱い → **完了済みのタスクが勝手に未完了に戻る**。

そこで API 用には `App\Http\Requests\Api\UpdateTaskRequest` を別に用意し、
`sometimes`（＝そのキーが送られてきた時だけチェックする）を使っています。

```php
'title'   => ['sometimes', 'required', 'string', 'max:255'],
'is_done' => ['sometimes', 'boolean'],
```

これで「送った項目だけ更新される」という、PATCH 本来の動きになります。
`ApiTaskTest::test_タイトルだけのPATCHでis_doneが巻き添えで変わらない()` がこれを見張っています。

> 一方 `StoreTaskRequest`（新規作成）は余計な加工が無く、ルールもそのまま使えるので
> Web版と共用しています。「なぜ更新だけ別なのか」がこの節の答えです。

### 4-7. JavaScript 側（`tasks/api.blade.php`）

全ての通信を `callApi()` という関数1本に通しています。

```js
const res = await fetch(url, {
    method,                                  // 'GET' / 'POST' / 'PATCH' / 'DELETE'
    headers: {
        'Accept': 'application/json',        // JSONで返してくれ、という意思表示
        'Content-Type': 'application/json',  // これからJSONを送るで、という宣言
    },
    body: JSON.stringify(body),
});
const json = res.status === 204 ? null : await res.json();  // 204は本文が空
```

注目してほしい点：

- **CSRF トークンを1回も送っていないのに通る**（Blade版の `@csrf` と比べてみてください）
- `escapeHtml()` を自作している
  → Blade の `{{ }}` が自動でやってくれていた XSS 対策を、JS では自分でやる必要がある
- `event.preventDefault()` でブラウザ標準のフォーム送信（＝リロード）を止めている

### 4-8. curl で直接叩いてみる

画面を経由せず、コマンドから API を叩けます。ブラウザが無くても動くのが API です。

```bash
# 一覧
curl -s http://localhost:8086/api/tasks

# 追加（201が返る）
curl -i -X POST http://localhost:8086/api/tasks \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"title":"curlから追加","due_date":"2026-12-31"}'

# バリデーションエラー（422とエラー内容が返る）
curl -i -X POST http://localhost:8086/api/tasks \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"title":""}'

# 存在しないID（404がJSONで返る）
curl -i -H 'Accept: application/json' http://localhost:8086/api/tasks/99999
```

叩いたあとに http://localhost:8086/tasks （Blade版）を開くと、
curl で追加したタスクがちゃんと出ます。入口が違うだけで中身は同じ、が実感できます。

---

## 5. よく使うコマンド

すべて `docker compose exec app` を頭に付けて実行します。

```bash
# ルート一覧を確認する（困ったらまずこれ）
docker compose exec app php artisan route:list
docker compose exec app php artisan route:list --path=api   # APIだけ見る

# マイグレーション
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:rollback     # 1つ戻す
docker compose exec app php artisan migrate:fresh --seed # 全部作り直して初期データ投入

# テスト
docker compose exec app php artisan test

# 対話シェル（モデルを直接触れる）
docker compose exec app php artisan tinker
>>> App\Models\Task::notDone()->count()

# 雛形の生成
docker compose exec app php artisan make:model Memo --migration --controller --resource --requests
docker compose exec app php artisan make:controller Api/MemoController --api   # API用
docker compose exec app php artisan make:resource MemoResource                 # JSON変換用
```

---

## 6. 練習課題（手を動かす用）

上から順にやると、MVC の各レイヤーを一通り触れます。

1. **View だけ**：一覧の「期限なし」の文字を「期限未設定」に変える
   → `resources/views/tasks/index.blade.php`

2. **Controller だけ**：1ページの表示件数を 10 件から 5 件に変える
   → `TaskController@index` の `paginate(10)`

3. **Validation**：タスク名を「3文字以上」必須にする
   → `StoreTaskRequest::rules()` に `'min:3'` を追加。テストも書いてみる

4. **M → V**：優先度（priority）カラムを追加する
   - `make:migration add_priority_to_tasks_table --table=tasks` で列を追加
   - `Task::$fillable` に `priority` を追加
   - フォーム（`partials/form.blade.php`）に選択欄を追加
   - 一覧に表示、`index()` の並び順にも反映

5. **新しい機能**：キーワード検索を付ける
   - 一覧に検索ボックスを置き、`?keyword=買い物` で絞り込む
   - `TaskController@index` に `->when($keyword, fn ($q) => $q->where('title', 'like', "%{$keyword}%"))`

6. **リレーション**：カテゴリ機能（`categories` テーブルを作り、tasks から `belongsTo`）

### API 編の課題

7. **Resource だけ**：JSON に `description` が出ないようにしてみる
   → `TaskResource::toArray()` から1行消す。画面が壊れないことも確認

8. **API を1本増やす**：`GET /api/tasks/summary` で件数だけ返す
   - `routes/api.php` に `Route::get('tasks/summary', ...)` を **apiResource より前に** 追加
   - `{"todo": 5, "done": 2}` を返す。後ろに書くと `{task}` に吸われるので注意

9. **フロントを直す**：API版の画面に「編集」機能を足す
   - 一覧の各行に編集ボタンを置き、`PATCH /api/tasks/{id}` でタイトルを更新
   - 422 が返ってきた時のエラー表示も忘れずに

10. **テストを書く**：8 で作った summary API のテストを `ApiTaskTest` に追加
    → `$this->getJson('/api/tasks/summary')->assertJsonPath('todo', 5)`

---

## 7. つまずいたら

| 症状 | 原因と対処 |
|------|-----------|
| 419 Page Expired | フォームに `@csrf` が無い（API ルートでは出ない症状） |
| 404 | `route:list` でURLを確認。`{task}` のIDが存在しない場合も404 |
| API が404になる | `bootstrap/app.php` の `withRouting` に `api:` があるか確認。URL の `/api` を忘れていないか |
| API なのにHTMLが返る | リクエストに `Accept: application/json` を付ける |
| 422 が返る | バリデーション失敗。レスポンスの `errors` に理由が入っている |
| PATCHで意図しない項目が変わる | FormRequest の `prepareForValidation()` を疑う（→ [4-6](#4-6-ハマりどころ更新用の-formrequest-を使い回さない)） |
| 500 / 画面が真っ白 | `docker compose logs -f app` と `src/storage/logs/laravel.log` を見る |
| 変更が反映されない | `docker compose exec app php artisan optimize:clear` |
| DBに繋がらない | `docker compose ps` で db が healthy か確認 |
