/**
 * API 通信の土台。
 *
 * ここには「HTTP をどう喋るか」だけを書く。
 * 「タスクをどう扱うか」は api/tasks.js、「画面にどう出すか」は components/ の担当。
 * こうやって層を分けておくと、URL の形が変わってもここ1枚を直せば済む。
 */

// Vite は import.meta.env 経由で環境変数を渡してくれる（VITE_ 始まりのものだけ）。
// .env を書き換えたら Vite の再起動が必要な点に注意。
// （?. を挟んでいるのは import.meta.env が無い環境＝Node から
//   このファイルを読み込んでもエラーにならないようにするため）
const BASE_URL =
  import.meta.env?.VITE_API_BASE_URL ??
  globalThis.process?.env?.VITE_API_BASE_URL ??
  'http://localhost:8086/api'

export { BASE_URL }

/**
 * API がエラーを返したことを表す例外。
 *
 * ふつうの Error と違って status（HTTPステータス）と errors（バリデーション内容）を持つ。
 * 呼び出す側は `if (e.status === 422)` のように分岐できる。
 */
export class ApiError extends Error {
  constructor(status, payload) {
    super(payload?.message ?? `APIエラー (${status})`)
    this.name = 'ApiError'
    this.status = status
    // Laravel が 422 の時に返す {"errors": {"title": ["タスク名は必須です。"]}}
    this.errors = payload?.errors ?? null
  }
}

/* ------------------------------------------------------------------ *
 * 通信ログ
 *
 * 画面に「今どんなリクエストが飛んだか」を出すための仕組み。
 * API 層が画面を直接触ると層が混ざるので、
 * 「ログが出たで」と知らせるだけにして、表示は RequestLog.jsx に任せる。
 * ------------------------------------------------------------------ */
const logListeners = new Set()
let logSeq = 0

/** ログを受け取りたい人が呼ぶ。戻り値を呼ぶと購読解除できる（useEffect の後片付け用）。 */
export function subscribeToApiLog(listener) {
  logListeners.add(listener)
  return () => logListeners.delete(listener)
}

function emitLog(entry) {
  const record = { id: ++logSeq, at: new Date(), ...entry }
  logListeners.forEach((listener) => listener(record))
}

/**
 * 実際に fetch を呼ぶ関数。すべての通信がここを通る。
 *
 * @param {string} method  'GET' | 'POST' | 'PATCH' | 'DELETE'
 * @param {string} path    '/tasks' のように BASE_URL からの続き
 * @param {object} [body]  送りたい JSON（GET / DELETE では省略する）
 */
export async function request(method, path, body) {
  const url = `${BASE_URL}${path}`

  const headers = {
    // 「JSON で返してくれ」という意思表示。
    // これが無いと、エラー時に HTML のエラーページが返ってくることがある。
    Accept: 'application/json',
  }
  const init = { method, headers }

  if (body !== undefined) {
    // 「これから JSON を送るで」という宣言。これが無いとサーバーが中身を読めない。
    headers['Content-Type'] = 'application/json'
    init.body = JSON.stringify(body)
  }

  // ★ CSRF トークンを一切送っていない ★
  //   Laravel 側の /api/* は web ミドルウェアグループの外なので CSRF 検証が無い。
  //   （Blade のフォームでは @csrf が必須だったのと対照的）

  emitLog({ kind: 'request', method, url, body })

  let res
  try {
    res = await fetch(url, init)
  } catch (e) {
    // ここに来るのは「サーバーに届かなかった」時。
    // Laravel が落ちている / URL が違う / CORS で弾かれた、などが該当する。
    const message =
      `${url} に接続できませんでした。` +
      'Laravel 側が起動しているか、.env の VITE_API_BASE_URL が正しいか確認してください。'
    emitLog({ kind: 'error', method, url, message: `${e.message} / ${message}` })
    throw new ApiError(0, { message })
  }

  // 204 No Content は本文が空なので .json() を呼ぶとエラーになる
  const payload = res.status === 204 ? null : await res.json().catch(() => null)

  emitLog({
    kind: 'response',
    method,
    url,
    status: res.status,
    statusText: res.statusText,
    payload,
  })

  // 200番台以外は例外にして、呼び出し側の try/catch に処理を任せる
  if (!res.ok) {
    throw new ApiError(res.status, payload)
  }

  return payload
}
