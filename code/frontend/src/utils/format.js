export function money(n) {
  return Number(n || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

/** Quantidade em unidades (sem casas decimais). */
export function formatQtd(n) {
  const v = Number(n)
  if (!Number.isFinite(v)) return '0'
  return String(Math.trunc(v))
}

export function emptyMeta() {
  return { current_page: 1, last_page: 1, per_page: 15, total: 0 }
}

export function formatDateTime(value) {
  if (!value) return '—'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export function metaFromResponse(res, fallbackLength = 0) {
  if (res?.meta) return res.meta
  return {
    current_page: 1,
    last_page: 1,
    per_page: fallbackLength || 15,
    total: fallbackLength,
  }
}
