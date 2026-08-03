import { BASE_URL } from './api/client'
import { useTasks } from './hooks/useTasks'
import FilterTabs from './components/FilterTabs'
import TaskList from './components/TaskList'
import TaskForm from './components/TaskForm'
import Pager from './components/Pager'
import RequestLog from './components/RequestLog'
import './App.css'

/**
 * Laravel の JSON API を叩くタスク管理画面（React 版）。
 *
 * ■ 役割分担
 *   api/client.js      … fetch のやり方（HTTP をどう喋るか）
 *   api/tasks.js       … タスク API の呼び出し口（どの URL を叩くか）
 *   hooks/useTasks.js  … 状態管理（今どのデータを持っているか）
 *   components/*       … 見た目
 *   App.jsx（ここ）    … それらを組み立てるだけ
 *
 * ■ Laravel 側は無改造
 *   CORS は Laravel の初期設定（paths: ['api/*'], allowed_origins: ['*']）で
 *   最初から通るので、API を別ドメインから叩くための設定追加は要らなかった。
 */
export default function App() {
  const {
    tasks,
    counts,
    meta,
    filter,
    loading,
    error,
    changeFilter,
    setPage,
    create,
    toggle,
    update,
    remove,
  } = useTasks()

  return (
    <div className="app">
      <header className="app-header">
        <h1>📋 タスク管理（React 版）</h1>
        <p className="hint">
          接続先: <code>{BASE_URL}</code>
        </p>
      </header>

      <section className="panel">
        <div className="panel-head">
          <h2>タスク一覧</h2>
          <code>GET /api/tasks</code>
        </div>

        <FilterTabs filter={filter} counts={counts} onChange={changeFilter} />

        <TaskList
          tasks={tasks}
          loading={loading}
          error={error}
          onToggle={toggle}
          onDelete={remove}
          onUpdate={update}
        />

        <Pager meta={meta} onChange={setPage} />
      </section>

      <TaskForm onCreate={create} />

      <RequestLog />

      <footer className="app-footer">
        <p className="hint">
          同じデータは Laravel 側の{' '}
          <a href="http://localhost:8086/tasks" target="_blank" rel="noreferrer">
            Blade 版
          </a>
          {' / '}
          <a href="http://localhost:8086/tasks-api" target="_blank" rel="noreferrer">
            fetch 版
          </a>
          {' '}からも見られます。入口が3つあるだけで、Model と DB は同じものです。
        </p>
      </footer>
    </div>
  )
}
