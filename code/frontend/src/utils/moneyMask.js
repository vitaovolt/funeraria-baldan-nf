/** Digitação por centavos → exibição pt-BR (`1.234,56`). Sem `R$` no texto (prefixo no UI). */
export function formatBrlDigits(digitsOrDisplay) {
  const digits = String(digitsOrDisplay ?? '').replace(/\D/g, '')
  if (!digits) return ''
  const value = Number(digits) / 100
  return value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

/** Converte máscara/display BRL → número (ex.: `1.234,56` → 1234.56). Vazio → null. */
export function parseBrl(display) {
  const digits = String(display ?? '').replace(/\D/g, '')
  if (!digits) return null
  return Number(digits) / 100
}

/** Número → máscara pt-BR. */
export function formatBrlNumber(n) {
  if (n == null || n === '' || !Number.isFinite(Number(n))) return ''
  return Number(n).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

/** onChange de input: mantém só dígitos e reformata. */
export function maskBrlInput(raw) {
  return formatBrlDigits(raw)
}
