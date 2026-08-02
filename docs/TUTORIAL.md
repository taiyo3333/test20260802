# Laravel MVC 学習アプリ（タスク管理）

Laravel の MVC を「動くもの」で理解するための最小構成のタスク管理アプリです。

- 一覧・追加・詳細・編集・削除（CRUD）
- 完了 / 未完了の切り替え
- 未完了 / 完了での絞り込み、ページ送り
- 入力バリデーション（日本語メッセージ）

アクセス: http://localhost:8086/

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

## 4. よく使うコマンド

すべて `docker compose exec app` を頭に付けて実行します。

```bash
# ルート一覧を確認する（困ったらまずこれ）
docker compose exec app php artisan route:list

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
```

---

## 5. 練習課題（手を動かす用）

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

---

## 6. つまずいたら

| 症状 | 原因と対処 |
|------|-----------|
| 419 Page Expired | フォームに `@csrf` が無い |
| 404 | `route:list` でURLを確認。`{task}` のIDが存在しない場合も404 |
| 500 / 画面が真っ白 | `docker compose logs -f app` と `src/storage/logs/laravel.log` を見る |
| 変更が反映されない | `docker compose exec app php artisan optimize:clear` |
| DBに繋がらない | `docker compose ps` で db が healthy か確認 |
