import { useCallback, useRef } from 'react'
import { Link } from 'react-router-dom'
import { deleteProduto, listProdutos } from '../api/dominio'
import { Pagination, SearchBar } from '../components/ListToolbar'
import { useToast } from '../context/ToastContext'
import { usePagedList } from '../hooks/usePagedList'
import { formatQtd, money } from '../utils/format'

export default function ProdutosPage() {
  const toast = useToast()
  const deletingRef = useRef(false)
  const fetcher = useCallback((params) => listProdutos(params), [])
  const { q, setQ, setPage, items, meta, reload } = usePagedList(fetcher)

  async function excluir(id) {
    if (deletingRef.current || !window.confirm('Excluir este produto?')) return
    deletingRef.current = true
    try {
      await deleteProduto(id)
      toast.success('Produto excluído.')
      await reload()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível excluir o produto.')
    } finally {
      deletingRef.current = false
    }
  }

  return (
    <div data-testid="page-produtos">
      <div className="page-head">
        <div>
          <h1>Produtos</h1>
          <p>Cadastro com NCM para a nota fiscal.</p>
        </div>
        <Link to="/produtos/novo" className="btn btn-accent">
          Novo produto
        </Link>
      </div>

      <section className="panel">
        <SearchBar
          value={q}
          onChange={setQ}
          placeholder="Buscar por descrição ou código"
          testId="busca-produto-lista"
        />
        <div className="table-wrap">
          <table className="data">
            <thead>
              <tr>
                <th>Código</th>
                <th>Descrição</th>
                <th>Preço</th>
                <th>Estoque</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              {items.length === 0 ? (
                <tr>
                  <td colSpan={5} className="hint">
                    Nenhum produto encontrado.
                  </td>
                </tr>
              ) : (
                items.map((p) => (
                  <tr key={p.id}>
                    <td>{p.codigo_barras}</td>
                    <td>
                      <strong>{p.descricao}</strong>
                    </td>
                    <td>{money(p.preco_venda)}</td>
                    <td>{formatQtd(p.estoque_atual)}</td>
                    <td className="actions">
                      <Link className="btn btn-ghost" style={{ padding: '6px 12px', fontSize: '0.8rem' }} to={`/produtos/${p.id}`}>
                        Editar
                      </Link>{' '}
                      <button
                        type="button"
                        className="btn btn-danger"
                        style={{ padding: '6px 12px', fontSize: '0.8rem' }}
                        onClick={() => excluir(p.id)}
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
