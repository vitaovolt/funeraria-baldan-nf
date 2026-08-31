import { useCallback } from 'react'
import { listNotasNfce } from '../api/pdv'
import { Pagination, SearchBar } from '../components/ListToolbar'
import { usePagedList } from '../hooks/usePagedList'

export default function NotasPage() {
  const fetcher = useCallback((params) => listNotasNfce(params), [])
  const { q, setQ, setPage, items, meta } = usePagedList(fetcher)

  return (
    <div data-testid="page-notas">
      <div className="page-head">
        <div>
          <h1>Notas NFC-e</h1>
          <p>Documentos emitidos no fechamento da venda.</p>
        </div>
      </div>

      <section className="panel">
        <SearchBar value={q} onChange={setQ} placeholder="Buscar por nº, venda, status ou chave" />
        <div className="table-wrap">
          <table className="data">
            <thead>
              <tr>
                <th>Nota</th>
                <th>Venda</th>
                <th>Cliente</th>
                <th>Status</th>
                <th>Chave</th>
              </tr>
            </thead>
            <tbody>
              {items.length === 0 ? (
                <tr>
                  <td colSpan={5} className="hint">
                    Nenhuma nota encontrada.
                  </td>
                </tr>
              ) : (
                items.map((n) => (
                  <tr key={n.id}>
                    <td>#{n.id}</td>
                    <td>#{n.venda_id}</td>
                    <td>{n.venda?.cliente?.nome || 'Consumidor'}</td>
                    <td>
                      <strong>{n.status}</strong>
                    </td>
                    <td className="hint">{n.chave ? `${n.chave.slice(0, 20)}…` : '—'}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
        <Pagination meta={meta} onPageChange={setPage} />
      </section>
    </div>
  )
}
