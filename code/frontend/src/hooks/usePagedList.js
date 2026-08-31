import { useCallback, useEffect, useState } from 'react'
import { emptyMeta, metaFromResponse } from '../utils/format'

/**
 * Lista com busca (debounce) + paginação server-side.
 * @param {(params: { q?: string, page: number, per_page: number }) => Promise} fetcher
 */
export function usePagedList(fetcher, { perPage = 15, debounceMs = 200, extraParams } = {}) {
  const [q, setQ] = useState('')
  const [page, setPage] = useState(1)
  const [items, setItems] = useState([])
  const [meta, setMeta] = useState(emptyMeta())
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  const extraKey = JSON.stringify(extraParams ?? {})

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const extra = extraKey ? JSON.parse(extraKey) : {}
      const params = {
        page,
        per_page: perPage,
        ...(q.trim() ? { q: q.trim() } : {}),
        ...extra,
      }
      const res = await fetcher(params)
      const list = res.data || []
      setItems(list)
      setMeta(metaFromResponse(res, list.length))
    } catch (err) {
      setError(err)
      setItems([])
      setMeta(emptyMeta())
    } finally {
      setLoading(false)
    }
  }, [fetcher, page, perPage, q, extraKey])

  useEffect(() => {
    const t = window.setTimeout(load, debounceMs)
    return () => window.clearTimeout(t)
  }, [load, debounceMs])

  function setSearch(value) {
    setPage(1)
    setQ(value)
  }

  return {
    q,
    setQ: setSearch,
    page,
    setPage,
    items,
    meta,
    loading,
    error,
    reload: load,
  }
}
