/**
 * タスク API の呼び出し口。
 *
 * Laravel 側の routes/api.php と1対1で対応させてある。
 * 画面側（コンポーネント）は fetch も URL も知らずに、この関数だけを使う。
 */
// 拡張子まで書いておくと、バンドラを通さない素の Node からも読み込める
import { request } from './client.js'

/**
 * 一覧： GET /api/tasks
 *
 * 返ってくる形（paginate + Resource の組み合わせ）:
 *   {
 *     data:   [ { id, title, description, is_done, due_date, is_overdue, ... } ],
 *     links:  { first, last, prev, next },
 *     meta:   { current_page, last_page, total, ... },
 *     counts: { todo, done }        ← コントローラーの additional() で足したもの
 *   }
 */
export function fetchTasks({ filter = 'all', page = 1 } = {}) {
  const query = new URLSearchParams({ filter, page: String(page) })
  return request('GET', `/tasks?${query}`)
}

/** 作成： POST /api/tasks → 201 Created。失敗すると 422 の ApiError が飛ぶ */
export function createTask({ title, description = null, dueDate = null }) {
  return request('POST', '/tasks', {
    title,
    description,
    // 空文字ではなく null を送る（Laravel 側のルール nullable に合わせる）
    due_date: dueDate || null,
  })
}

/**
 * 更新： PATCH /api/tasks/{id}
 *
 * ★部分更新★
 * Laravel 側の Api\UpdateTaskRequest が sometimes を使っているので、
 * 「送った項目だけ」が更新される。
 * つまりタイトルだけ送れば is_done は完了のまま保たれる。
 */
export function updateTask(id, changes) {
  return request('PATCH', `/tasks/${id}`, changes)
}

/** 完了 / 未完了の切り替え： PATCH /api/tasks/{id}/toggle → 更新後のタスクが返る */
export function toggleTask(id) {
  return request('PATCH', `/tasks/${id}/toggle`)
}

/** 削除： DELETE /api/tasks/{id} → 204 No Content（本文なし） */
export function deleteTask(id) {
  return request('DELETE', `/tasks/${id}`)
}
