/**
 * 絞り込みタブ。
 *
 * Blade 版はリンクを踏んでページごと再読み込みしていたが、
 * React では onChange で state を変えるだけ。URL は変わらない。
 */
export default function FilterTabs({ filter, counts, onChange }) {
  const tabs = [
    { key: 'all', label: 'すべて', count: counts.todo + counts.done },
    { key: 'todo', label: '未完了', count: counts.todo },
    { key: 'done', label: '完了', count: counts.done },
  ]

  return (
    <div className="filters">
      {tabs.map((tab) => (
        <button
          key={tab.key}
          type="button"
          className={`filter-tab${filter === tab.key ? ' is-active' : ''}`}
          aria-pressed={filter === tab.key}
          onClick={() => onChange(tab.key)}
        >
          {tab.label}（{tab.count}）
        </button>
      ))}
    </div>
  )
}
