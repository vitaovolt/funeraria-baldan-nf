export function soDigitosCep(value) {
  return String(value || '').replace(/\D/g, '').slice(0, 8)
}

export function maskCep(value) {
  const d = soDigitosCep(value)
  if (d.length <= 5) return d
  return `${d.slice(0, 5)}-${d.slice(5)}`
}
