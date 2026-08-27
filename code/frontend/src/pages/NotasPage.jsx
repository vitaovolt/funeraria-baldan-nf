import { useEffect, useState } from 'react'
import { listNotasNfce } from '../api/pdv'
import { useToast } from '../context/ToastContext'

export default function NotasPage() {
  const toast = useToast()
  const [notas, setNotas] = useState([])

  useEffect(() => {
    listNotasNfce()
      .then((r) => setNotas(r.data || []))
      .catch(() => toast.error('Falha ao listar notas.'))
  }, [toast])

  return (
    <div data-testid="page-notas">
      <h1 className="m-0 text-2xl font-extrabold text-[var(--brand-primary)]">Notas NFC-e</h1>
      <p className="mt-1 text-[var(--muted)]">Documentos emitidos no fechamento da venda (T15).</p>
      <ul className="mt-4 space-y-2">
        {notas.map((n) => (
          <li key={n.id} className="rounded-lg border border-[var(--line)] bg-white px-3 py-2 text-sm">
            Nota #{n.id} · venda #{n.venda_id} · <strong>{n.status}</strong>
            {n.chave ? ` · ${n.chave.slice(0, 16)}…` : ''}
          </li>
        ))}
      </ul>
    </div>
  )
}
