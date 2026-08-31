import { useCallback, useRef } from 'react'
import { Link } from 'react-router-dom'
import { deleteCliente, listClientes } from '../api/dominio'
import { Pagination, SearchBar } from '../components/ListToolbar'
import { useToast } from '../context/ToastContext'
import { usePagedList } from '../hooks/usePagedList'

export default function ClientesPage() {
  const toast = useToast()
  const deletingRef = useRef(false)
  const fetcher = useCallback((params) => listClientes(params), [])
  const { q, setQ, setPage, items, meta, reload } = usePagedList(fetcher)

  async function excluir(id) {
    if (deletingRef.current || !window.confirm('Excluir este cliente?')) return
    deletingRef.current = true
    try {
      await deleteCliente(id)
      toast.success('Cliente excluído.')
      await reload()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível excluir o cliente.')
    } finally {
      deletingRef.current = false
    }
  }

  return (
    <div data-testid="page-clientes">
      <div className="page-head">
        <div>
          <h1>Clientes</h1>
          <p>Cadastro de titulares e dependentes para venda e consignado.</p>
        </div>
        <Link to="/clientes/novo" className="btn btn-accent">
          Novo cliente
        </Link>
      </div>

      <section className="panel">
        <SearchBar
          value={q}
          onChange={setQ}
          placeholder="Buscar nome ou documento"
          testId="busca-cliente-lista"
        />
        <div className="table-wrap">
          <table className="data">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Documento</th>
                <th>Telefone</th>
                <th>Plano</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              {items.length === 0 ? (
                <tr>
                  <td colSpan={5} className="hint">
                    Nenhum cliente encontrado.
                  </td>
                </tr>
              ) : (
                items.map((c) => (
                  <tr key={c.id}>
                    <td>
                      <strong>{c.nome}</strong>
                    </td>
                    <td>{c.documento || '—'}</td>
                    <td>{c.telefone || '—'}</td>
                    <td>{c.tem_plano ? c.plano_nome || 'Sim' : '—'}</td>
                    <td className="actions">
                      <Link className="btn btn-ghost" style={{ padding: '6px 12px', fontSize: '0.8rem' }} to={`/clientes/${c.id}`}>
                        Editar
                      </Link>{' '}
                      <button
                        type="button"
                        className="btn btn-danger"
                        style={{ padding: '6px 12px', fontSize: '0.8rem' }}
                        onClick={() => excluir(c.id)}
                      >
                        Excluir
                      </button>
                    </td>
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
