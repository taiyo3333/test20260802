# タスク管理フロントエンド（React + Vite）

このリポジトリのルートにある Laravel（`../src`）が公開している JSON API を叩く、
タスク管理画面の React 版です。同じリポジトリの中で、API 側とフロント側が同居しています。

```
test20260802/
  src/         Laravel（API + Blade 画面）
  infra/       Docker（nginx / php / mysql）
  frontend/    ← ここ（React）
  docs/        チュートリアル
```

同じデータに対して**入口が3つ**あります。見比べると「サーバーがどこまでやるか」の違いが分かります。

| 入口 | URL | 誰が HTML を作るか |
|------|-----|-------------------|
| Blade 版 | http://localhost:8086/tasks | サーバー（PHP）。操作のたびにページごと再読み込み |
| fetch 版 | http://localhost:8086/tasks-api | ブラウザ（素の JS）。Laravel が配ったページの中で動く |
| **React 版（これ）** | http://localhost:5174/ | ブラウザ（React）。**別のサーバーから配られたページ**が API だけ叩く |

---

## 起動手順

### 1. Laravel（API 側）を起動する

```bash
cd ..              # リポジトリのルート（docker-compose.yml がある場所）
docker compose up -d
```

http://localhost:8086/api/tasks が JSON を返せば準備完了です。

### 2. 接続先を設定する

```bash
cp .env.example .env
```

```
VITE_API_BASE_URL=http://localhost:8086/api
```

`.env` を書き換えたら **Vite の再起動が必要**です（ビルド時に値が埋め込まれるため）。

### 3. React 側を起動する

```bash
npm install
npm run dev
```

http://localhost:5174/ を開きます。

> ポートを 5174 に固定しているのは、Laravel 側の `docker-compose.yml` が
> 127.0.0.1:5173（Laravel 用 Vite）を先に押さえているためです。

### その他のコマンド

```bash
npm run lint      # oxlint
npm run build     # 本番ビルド（dist/ に出る）
npm run preview   # ビルド結果を確認
```

---

## なぜ CORS の設定が要らないのか

ブラウザから見ると、このページは `localhost:5174` から配られ、
API は `localhost:8086` にあります。**ポートが違うので別オリジン**です。
本来ならブラウザが勝手にブロックします（同一オリジンポリシー）。

今回それが通るのは、**Laravel 側が最初から許可を出しているから**です。
`vendor/laravel/framework/config/cors.php` の初期値がこうなっています。

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],  // /api/～ だけ許可
'allowed_origins' => ['*'],                    // どのオリジンからでもOK
'allowed_methods' => ['*'],
```

`HandleCors` ミドルウェアも標準で有効なので、**Laravel 側は無改造**で動きます。

POST / PATCH / DELETE の前にブラウザが投げる**プリフライト（OPTIONS リクエスト）**にも
Laravel が自動で 204 を返してくれます。DevTools の Network タブで
`OPTIONS /api/tasks` が飛んでいるのを確認してみてください。

> 本番で公開するときは `allowed_origins` を `['*']` のままにせず、
> `php artisan config:publish cors` で `config/cors.php` を出してから
> 自分のドメインだけに絞るのが安全です。

---

## 本番（EC2）へのデプロイ

本番では **React も Laravel も同じドメイン**から配ります。

| | URL |
|---|---|
| Blade版 | https://task.taiyo333.com/tasks |
| fetch版 | https://task.taiyo333.com/tasks-api |
| **React版** | https://task.taiyo333.com/app/ |
| API | https://task.taiyo333.com/api/tasks |

同一オリジンになるので **本番では CORS が一切発生しません**。
`.env.production` が `VITE_API_BASE_URL=/api`（相対パス）になっているためです。

### 手順

```bash
cd <リポジトリ>
git pull

# 1. Laravel 側のキャッシュを捨てる
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan optimize:clear

# 2. nginx の設定が変わったのでコンテナを作り直す（後述の注意を参照）
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --force-recreate web

# 3. React をビルドする（成果物は src/public/app に出る）
cd frontend
npm ci
npm run build
```

Node が入っていないサーバーなら、Docker で代用できます。

```bash
docker run --rm -v "$PWD/frontend":/work -w /work node:22-alpine \
  sh -c "npm ci && npm run build"
```

> composer の新規パッケージもマイグレーションも増えていないので、
> `composer install` と `php artisan migrate` は不要です。

### ⚠️ nginx 設定を変えたら「reload」では足りない

`docker-compose.yml` は設定ファイルを**1ファイル単位**でマウントしています。

```yaml
- ./infra/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
```

この形だとコンテナはファイルの実体（inode）を掴むため、
エディタや `git pull` がファイルを**置き換える**と、コンテナ側は古い中身のままになります。
`nginx -s reload` をしても変わりません。**コンテナごと作り直してください。**

```bash
docker compose ... up -d --force-recreate web
```

反映されたかは、コンテナの中を直接見るのが確実です。

```bash
docker compose exec web grep -c "location /app/" /etc/nginx/conf.d/default.conf   # 1 なら反映済み
```

### 仕組み

- `vite.config.js` の `base: '/app/'` … JS/CSS の参照先を `/app/...` にする
- `vite.config.js` の `outDir: '../src/public/app'` … nginx の公開ディレクトリ配下に出す
- `infra/nginx/default.conf` の `location /app/` … 静的ファイルとして配る

ビルド成果物（`src/public/app`）は `.gitignore` 済みです。**サーバー側でビルドします。**

---

## ファイル構成

```
src/
  api/
    client.js        HTTP をどう喋るか（fetch / エラー / 通信ログ）
    tasks.js         どの URL を叩くか（Laravel の routes/api.php と1対1）
  hooks/
    useTasks.js      状態管理（一覧・絞り込み・ページ・CRUD）
  components/
    FilterTabs.jsx   絞り込みタブ
    TaskList.jsx     一覧の入れ物（読み込み中／エラー／0件／通常）
    TaskItem.jsx     1行ぶん。表示モードと編集モードを持つ
    TaskForm.jsx     新規追加フォーム
    Pager.jsx        ページ送り
    RequestLog.jsx   通信ログ
  App.jsx            上記を組み立てるだけ
```

**層を分ける意味**：URL の形が変わったら `api/`、見た目を変えたいなら `components/`、
というふうに直す場所が1か所に決まります。
コンポーネントの中に `fetch` を直接書くと、この境界が崩れます。

---

## 読みどころ

### 1. バリデーションはフロントに書き写さない

`TaskForm.jsx` は入力チェックを自分でしていません。
送ってみて 422 が返ってきたら、**サーバーが返した日本語メッセージをそのまま表示**します。

```js
catch (e) {
  if (e.status === 422) setErrors(e.errors)   // {"title": ["タスク名は必須です。"]}
}
```

両方にルールを書くと、片方だけ直して食い違う事故が起きます。ルールは Laravel 側の1か所だけ。

### 2. 部分更新（PATCH）

`TaskItem.jsx` の編集で送るのは `title` と `due_date` だけで、`is_done` は送りません。
それでも完了状態は保たれます。Laravel 側の `Api\UpdateTaskRequest` が
`sometimes` を使っていて「送られてきた項目だけ」を更新するからです。

完了済みタスクの名前を変えて、完了のままなのを確かめてみてください。

### 3. 通信ログ

画面下のパネルに、飛んだリクエストと返ってきたレスポンスが出ます。
`api/client.js` が流すログを `RequestLog.jsx` が購読しているだけで、
**ログ表示のコンポーネントは fetch を一切知りません**。

### 4. 開発中は初回の GET が2回飛ぶ

`main.jsx` の `<StrictMode>` が、後片付け漏れを見つけるために
`useEffect` をわざと2回実行するためです。**本番ビルドでは1回**です。

### 5. 古いレスポンスで画面が壊れないようにする

通信は送った順に返ってくるとは限りません。絞り込みを素早く2回押すと、
1回目の遅い返事が2回目の後に届いて画面が古いデータになることがあります。
`useTasks.js` では通し番号を持たせて、追い越された結果は捨てています。

---

## 練習課題

1. **表示だけ**：期限が今日のタスクに「今日まで」バッジを出す → `TaskItem.jsx`
2. **API 層**：`fetchTask(id)` を足して、行をクリックしたら詳細を出す → `api/tasks.js`
3. **状態管理**：削除を「取り消せる」ようにする（先に画面から消して、失敗したら戻す＝楽観的更新）
4. **検索**：Laravel 側に `?keyword=` を足し、React 側に検索ボックスを付ける
5. **本番配信**：`npm run build` した `dist/` を Laravel の `public/` に置き、
   同一オリジンで動かしてみる（CORS が不要になるのを確かめる）

---

## つまずいたら

| 症状 | 原因と対処 |
|------|-----------|
| 画面が空 / 「接続できませんでした」 | Laravel が起動しているか（`docker compose ps`）。http://localhost:8086/api/tasks を直接開いて確認 |
| CORS エラーがコンソールに出る | `.env` の `VITE_API_BASE_URL` の綴り、末尾の `/api` を確認。Laravel 側で `config/cors.php` を自作して `paths` から `api/*` を外していないか |
| `.env` を変えても反映されない | Vite を再起動する（`Ctrl+C` → `npm run dev`） |
| ポート 5174 が使えない | `vite.config.js` の `port` を変える |
| 追加しても一覧に出てこない | Laravel は「未完了 → 期限が近い順」で並べ、10件ずつページ送りする。期限が遠いタスクは2ページ目に入ることがある |
