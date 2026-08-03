/**
 * ページ送り。
 *
 * Laravel の paginate() が付けてくれた meta をそのまま使う。
 *   meta.current_page … 今何ページ目か
 *   meta.last_page    … 全部で何ページあるか
 * 自分で総ページ数を計算する必要はない。
 */
export default function Pager({ meta, onChange }) {
  if (!meta || meta.last_page <= 1) return null

  const { current_page: current, last_page: last } = meta

  return (
    <div className="pager">
      <button
        type="button"
        className="btn btn-sm"
        onClick={() => onChange(current - 1)}
        disabled={current <= 1}
      >
        ← 前へ
      </button>
      <span className="pager-info">
        {current} / {last} ページ（全 {meta.total} 件）
      </span>
      <button
        type="button"
        className="btn btn-sm"
        onClick={() => onChange(current + 1)}
        disabled={current >= last}
      >
        次へ →
      </button>
    </div>
  )
}
