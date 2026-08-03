import { useCallback, useEffect, useRef, useState } from 'react'
import * as taskApi from '../api/tasks'

/**
 * タスク一覧の「状態」をまとめて面倒みるカスタムフック。
 *
 * Blade 版では PHP が毎回ページを作り直していたので、状態はサーバーが持っていた。
 * React ではブラウザ側が状態を持ち続けるので、
 *   ・今どのタスクが表示されているか（tasks）
 *   ・どの絞り込みか（filter） / 何ページ目か（page）
 *   ・読み込み中か（loading） / エラーが出ているか（error）
 * を自分で管理する必要がある。それをここに閉じ込めている。
 *
 * コンポーネント側は「const { tasks, create, toggle } = useTasks()」と書くだけでよくなる。
 */
export function useTasks() {
  const [tasks, setTasks] = useState([])
  const [counts, setCounts] = useState({ todo: 0, done: 0 })
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 })
  const [filter, setFilter] = useState('all')
  const [page, setPage] = useState(1)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  // 「今いちばん新しい読み込み」を見分けるための通し番号。
  //
  // 通信は必ずしも送った順に返ってこない。
  // 例えば絞り込みを素早く2回押すと、1回目の遅い返事が2回目の後に届いて、
  // 画面が古いデータで上書きされてしまうことがある（レースコンディション）。
  // 自分の番号が最新でなくなっていたら、その結果は捨てる。
  const requestIdRef = useRef(0)

  /**
   * 一覧を取り直す。
   * useCallback で包んでいるのは、filter / page が変わった時だけ
   * 関数を作り直して useEffect を走らせたいため。
   */
  const load = useCallback(async () => {
    const myId = ++requestIdRef.current
    setLoading(true)
    setError(null)
    try {
      const json = await taskApi.fetchTasks({ filter, page })

      if (myId !== requestIdRef.current) return // 追い越されたので破棄

      // 最終ページのタスクを全部消した時など、存在しないページに取り残される対策
      if (json.data.length === 0 && json.meta.current_page > 1) {
        setPage(1)
        return
      }

      setTasks(json.data)
      setCounts(json.counts)
      setMeta(json.meta)
    } catch (e) {
      if (myId !== requestIdRef.current) return
      setError(e.message)
    } finally {
      if (myId === requestIdRef.current) setLoading(false)
    }
  }, [filter, page])

  // 起動時と、filter / page が変わった時に読み直す。
  //
  // ※ 開発中は main.jsx の <StrictMode> のせいで、初回だけ意図的に2回走る。
  //   （後片付け漏れを見つけるための React の仕様。本番ビルドでは1回だけ）
  //   通信ログに GET が2本出るのはこのため。
  useEffect(() => {
    load()
  }, [load])

  /** 絞り込みを変える。ページ番号は1に戻す */
  const changeFilter = useCallback((nextFilter) => {
    setFilter(nextFilter)
    setPage(1)
  }, [])

  /**
   * 追加。バリデーションエラー(422)はそのまま投げ直して、
   * フォーム側で項目ごとのメッセージを出せるようにする。
   */
  const create = useCallback(
    async (input) => {
      await taskApi.createTask(input)
      if (page !== 1) {
        setPage(1) // ページが変わるので useEffect 側が読み直してくれる
      } else {
        await load()
      }
    },
    [page, load],
  )

  /**
   * 完了 / 未完了の切り替え。
   *
   * API は更新後のタスクを返してくれるので、その1件だけ差し替えることもできる。
   * ただし Laravel 側は「未完了を上」に並べているので、切り替えると並び順が変わる。
   * ここでは一覧を取り直して、サーバーの並び順に合わせている。
   */
  const toggle = useCallback(
    async (id) => {
      await taskApi.toggleTask(id)
      await load()
    },
    [load],
  )

  /** 部分更新。changes に入れた項目だけが変わる（is_done は巻き添えにならない） */
  const update = useCallback(
    async (id, changes) => {
      await taskApi.updateTask(id, changes)
      await load()
    },
    [load],
  )

  /** 削除 */
  const remove = useCallback(
    async (id) => {
      await taskApi.deleteTask(id)
      await load()
    },
    [load],
  )

  return {
    tasks,
    counts,
    meta,
    filter,
    page,
    loading,
    error,
    changeFilter,
    setPage,
    create,
    toggle,
    update,
    remove,
    reload: load,
  }
}
