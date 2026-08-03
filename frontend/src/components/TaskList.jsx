import TaskItem from './TaskItem'

/**
 * 一覧の入れ物。
 * 「読み込み中」「エラー」「0件」「通常」の4状態を出し分けるだけの係。
 */
export default function TaskList({ tasks, loading, error, onToggle, onDelete, onUpdate }) {
  if (error) {
    return <p className="alert-error">{error}</p>
  }

  if (loading && tasks.length === 0) {
    return <p className="empty">読み込み中…</p>
  }

  if (tasks.length === 0) {
    return <p className="empty">タスクはまだありません。下のフォームから追加してみましょう。</p>
  }

  return (
    <ul className={`tasks${loading ? ' is-loading' : ''}`}>
      {tasks.map((task) => (
        // key はリストを描き直す時に React が「どれがどれか」を見分けるための印。
        // 配列の添字ではなく、変わらない id を使うのが鉄則。
        <TaskItem
          key={task.id}
          task={task}
          onToggle={onToggle}
          onDelete={onDelete}
          onUpdate={onUpdate}
        />
      ))}
    </ul>
  )
}
