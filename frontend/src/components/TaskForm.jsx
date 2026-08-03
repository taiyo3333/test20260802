import { useState } from 'react'

/**
 * 新規追加フォーム。
 *
 * ポイントは「サーバーが返した 422 の中身をそのまま画面に出す」ところ。
 * 入力チェックのルールはフロントに書き写さず、Laravel 側の1か所だけで管理する。
 * （フロントにも書くと、片方だけ直して食い違う事故が起きる）
 */
export default function TaskForm({ onCreate }) {
  const [title, setTitle] = useState('')
  const [dueDate, setDueDate] = useState('')
  const [errors, setErrors] = useState(null) // { title: ['タスク名は必須です。'] }
  const [message, setMessage] = useState(null) // 422 以外のエラー
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(event) {
    // ブラウザ標準の送信（＝ページ遷移）を止める。React ではこれが必須
    event.preventDefault()

    setSubmitting(true)
    setErrors(null)
    setMessage(null)

    try {
      await onCreate({ title, dueDate })
      // 成功した時だけ入力欄を空にする
      setTitle('')
      setDueDate('')
    } catch (e) {
      if (e.status === 422) {
        setErrors(e.errors)
      } else {
        setMessage(e.message)
      }
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <form className="panel" onSubmit={handleSubmit}>
      <div className="panel-head">
        <h2>新規追加</h2>
        <code>POST /api/tasks</code>
      </div>

      <div className="field">
        <label htmlFor="title">タスク名</label>
        <input
          id="title"
          type="text"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          placeholder="例：牛乳を買う"
          autoComplete="off"
          className={errors?.title ? 'is-invalid' : ''}
        />
        {/* サーバーが返したメッセージをそのまま表示する */}
        {errors?.title?.map((text) => (
          <p key={text} className="field-error">
            {text}
          </p>
        ))}
      </div>

      <div className="field">
        <label htmlFor="due_date">期限（任意）</label>
        <input
          id="due_date"
          type="date"
          value={dueDate}
          onChange={(e) => setDueDate(e.target.value)}
          className={errors?.due_date ? 'is-invalid' : ''}
        />
        {errors?.due_date?.map((text) => (
          <p key={text} className="field-error">
            {text}
          </p>
        ))}
      </div>

      {message && <p className="alert-error">{message}</p>}

      <button type="submit" className="btn btn-primary" disabled={submitting}>
        {submitting ? '送信中…' : '追加する'}
      </button>
    </form>
  )
}
