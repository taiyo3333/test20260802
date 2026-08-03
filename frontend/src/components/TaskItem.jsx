import { useState } from 'react'

/** '2026-08-10' → '2026/08/10' */
function formatDate(value) {
  return value ? value.replaceAll('-', '/') : null
}

/**
 * タスク1件ぶんの行。表示モードと編集モードを自分で切り替える。
 *
 * ★このコンポーネントが「部分更新」の見せ場★
 * 保存時に送るのは title と due_date だけで、is_done は送らない。
 * それでも完了状態は保たれる。Laravel 側の Api\UpdateTaskRequest が
 * sometimes を使っていて「送られてきた項目だけ」を更新するため。
 */
export default function TaskItem({ task, onToggle, onDelete, onUpdate }) {
  const [editing, setEditing] = useState(false)
  const [title, setTitle] = useState(task.title)
  const [dueDate, setDueDate] = useState(task.due_date ?? '')
  const [errors, setErrors] = useState(null)
  const [busy, setBusy] = useState(false)

  function startEdit() {
    // 開くたびに今の値で初期化し直す
    setTitle(task.title)
    setDueDate(task.due_date ?? '')
    setErrors(null)
    setEditing(true)
  }

  async function handleSave(event) {
    event.preventDefault()
    setBusy(true)
    setErrors(null)
    try {
      // is_done を送っていないことに注目
      await onUpdate(task.id, { title, due_date: dueDate || null })
      setEditing(false)
    } catch (e) {
      if (e.status === 422) setErrors(e.errors)
      else setErrors({ title: [e.message] })
    } finally {
      setBusy(false)
    }
  }

  async function run(action) {
    setBusy(true)
    try {
      await action()
    } finally {
      setBusy(false)
    }
  }

  if (editing) {
    return (
      <li className="task-row is-editing">
        <form className="edit-form" onSubmit={handleSave}>
          <input
            type="text"
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            aria-label="タスク名"
            className={errors?.title ? 'is-invalid' : ''}
          />
          <input
            type="date"
            value={dueDate}
            onChange={(e) => setDueDate(e.target.value)}
            aria-label="期限"
          />
          <div className="edit-actions">
            <button type="submit" className="btn btn-sm btn-primary" disabled={busy}>
              保存
            </button>
            <button
              type="button"
              className="btn btn-sm"
              onClick={() => setEditing(false)}
              disabled={busy}
            >
              やめる
            </button>
          </div>
          {errors &&
            Object.values(errors)
              .flat()
              .map((text) => (
                <p key={text} className="field-error">
                  {text}
                </p>
              ))}
          <p className="hint">
            送るのは title と due_date だけ。is_done は送らないので完了状態は変わらない（部分更新）
          </p>
        </form>
      </li>
    )
  }

  return (
    <li className={`task-row${task.is_done ? ' is-done' : ''}`}>
      <input
        type="checkbox"
        checked={task.is_done}
        onChange={() => run(() => onToggle(task.id))}
        disabled={busy}
        aria-label={`${task.title} の完了状態を切り替える`}
      />

      <div className="task-main">
        <span className="task-title">{task.title}</span>
        <div className="task-meta">
          {task.due_date ? (
            <span className={task.is_overdue ? 'overdue' : ''}>
              期限: {formatDate(task.due_date)}
              {task.is_overdue && '（期限切れ）'}
            </span>
          ) : (
            '期限なし'
          )}
        </div>
      </div>

      <div className="task-actions">
        <button type="button" className="btn btn-sm" onClick={startEdit} disabled={busy}>
          編集
        </button>
        <button
          type="button"
          className="btn btn-sm btn-danger"
          disabled={busy}
          onClick={() => {
            if (confirm(`「${task.title}」を削除します。よろしいですか？`)) {
              run(() => onDelete(task.id))
            }
          }}
        >
          削除
        </button>
      </div>
    </li>
  )
}
