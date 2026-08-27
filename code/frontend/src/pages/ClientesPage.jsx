import { useCallback, useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { deleteCliente, listClientes } from '../api/dominio'
import { useToast } from '../context/ToastContext'

export default function ClientesPage() {
  const toast = useToast()
  const deletingRef = useRef(false)
  const [q, setQ] = useState('')
  const [clientes, setClientes] = useState([])
  const carregar = useCallback(async () => {
    try {
      const res = await listClientes({ q: q || undefined })
      setClientes(res.data || [])
    } catch {
      toast.error('Não foi possível carregar os clientes.')
    }
  }, [q, toast])

  useEffect(() => {
    const timer = window.setTimeout(carregar, 200)
    return () => window.clearTimeout(timer)
  }, [carregar])

  async function excluir(id) {
    if (deletingRef.current || !window.confirm('Excluir este cliente?')) return
    deletingRef.current = true
    try {
      await deleteCliente(id)
      toast.success('Cliente excluído.')
      await carregar()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível excluir o cliente.')
    } finally {
      deletingRef.current = false
    }
  }

  return (
    <div data-testid="page-clientes">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="m-0 text-2xl font-extrabold text-[var(--brand-primary)]">Clientes</h1>
        <Link to="/clientes/novo" className="rounded-lg bg-[var(--brand-accent)] px-4 py-2 font-bold text-[var(--brand-primary)]">Novo cliente</Link>
      </div>
      <input className="mt-4 w-full rounded-lg border border-[var(--line)] px-3 py-2" placeholder="Buscar nome ou documento" value={q} onChange={(e) => setQ(e.target.value)} />
      <ul className="mt-4 divide-y divide-[var(--line)] rounded-[10px] border border-[var(--line)] bg-white px-4">
        {clientes.map((c) => (
          <li key={c.id} className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
            <span><strong>{c.nome}</strong><br /><span className="text-[var(--muted)]">{c.documento} · {c.telefone || 'sem telefone'}</span></span>
            <span>
              <Link className="mr-3 font-semibold underline" to={`/clientes/${c.id}`}>Editar</Link>
              <button type="button" className="font-semibold text-red-700 underline" onClick={() => excluir(c.id)}>Excluir</button>
            </span>
          </li>
        ))}
      </ul>
    </div>
  )
}
