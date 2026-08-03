import { useEffect, useRef, useState } from 'react'
import { subscribeToApiLog } from '../api/client'

const MAX_ROWS = 60

/** 長い JSON は先頭だけ見せる */
function preview(value, limit = 300) {
  if (value === null || value === undefined) return null
  const text = JSON.stringify(value)
  return text.length > limit ? `${text.slice(0, limit)} …` : text
}

/**
 * 通信ログ。
 *
 * DevTools を開かなくても「どの URL に何を送って、何が返ってきたか」が画面で追える。
 * api/client.js が流してくるログを購読しているだけで、
 * この画面自身は fetch を一切知らない。
 */
export default function RequestLog() {
  const [rows, setRows] = useState([])
  const boxRef = useRef(null)

  useEffect(() => {
    // 購読を始める。戻り値（購読解除の関数）を return しておくと、
    // このコンポーネントが消える時に React が呼んでくれる（後片付け）。
    return subscribeToApiLog((entry) => {
      setRows((prev) => [...prev, entry].slice(-MAX_ROWS))
    })
  }, [])

  // 新しい行が増えたら一番下までスクロールする
  useEffect(() => {
    if (boxRef.current) {
      boxRef.current.scrollTop = boxRef.current.scrollHeight
    }
  }, [rows])

  return (
    <section className="panel">
      <div className="panel-head">
        <h2>通信ログ</h2>
        <button type="button" className="btn btn-sm" onClick={() => setRows([])}>
          クリア
        </button>
      </div>
      <p className="hint">→ が送ったリクエスト、← が返ってきたレスポンス。</p>

      <div className="log" ref={boxRef}>
        {rows.length === 0 && <div className="log-row log-empty">まだ通信していません。</div>}

        {rows.map((row) => {
          if (row.kind === 'request') {
            return (
              <div key={row.id} className="log-row">
                <span className="log-req">
                  → {row.method} {row.url}
                </span>
                {row.body !== undefined && (
                  <span className="log-body">   {preview(row.body)}</span>
                )}
              </div>
            )
          }

          if (row.kind === 'error') {
            return (
              <div key={row.id} className="log-row">
                <span className="log-err">✗ {row.method} {row.url}</span>
                <span className="log-body">   {row.message}</span>
              </div>
            )
          }

          // 200番台は成功、それ以外はエラー色で出す
          const ok = row.status >= 200 && row.status < 300
          return (
            <div key={row.id} className="log-row">
              <span className={ok ? 'log-res' : 'log-err'}>
                ← {row.status} {row.statusText}
              </span>
              <span className="log-body">
                {'   '}
                {row.payload === null ? '（本文なし）' : preview(row.payload)}
              </span>
            </div>
          )
        })}
      </div>
    </section>
  )
}
