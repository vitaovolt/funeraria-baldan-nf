import { useCallback, useEffect, useRef, useState } from 'react'
import { criarMovimentacao, listMovimentacoes, listProdutos } from '../api/dominio'
import { Pagination, SearchBar } from '../components/ListToolbar'
import { useToast } from '../context/ToastContext'
import { usePagedList } from '../hooks/usePagedList'
import { formatQtd, metaFromResponse } from '../utils/format'

export default function EstoquePage() {
  const toast = useToast()
  const submittingRef = useRef(false)
  const [produtoId, setProdutoId] = useState('')
  const [tipo, setTipo] = useState('entrada')
  const [quantidade, setQuantidade] = useState('')
  const [observacao, setObservacao] = useState('')
  const [movimentacoes, setMovimentacoes] = useState([])
  const [movMeta, setMovMeta] = useState({ current_page: 1, last_page: 1, total: 0 })
  const [movPage, setMovPage] = useState(1)
  const [movQ, setMovQ] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const fetcher = useCallback((params) => listProdutos(params), [])
  const { q, setQ, setPage, items: produtos, meta, reload } = usePagedList(fetcher)

  useEffect(() => {
    if (!produtoId) {
      setMovimentacoes([])
      return undefined
    }
    const t = window.setTimeout(() => {
      listMovimentacoes(produtoId, {
        page: movPage,
        per_page: 15,
        ...(movQ.trim() ? { q: movQ.trim() } : {}),
      })
        .then((res) => {
          setMovimentacoes(res.data || [])
          setMovMeta(metaFromResponse(res, (res.data || []).length))
        })
        .catch(() => toast.error('Não foi possível carregar as movimentações.'))
    }, 200)
    return () => window.clearTimeout(t)
  }, [produtoId, movPage, movQ, toast])

  async function salvar(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!produtoId || Number(quantidade) <= 0) {
      toast.error('Selecione o produto e informe uma quantidade válida.')
      return
    }
    submittingRef.current = true
    setSubmitting(true)
    try {
      await criarMovimentacao(produtoId, {
        tipo,
        quantidade: Math.trunc(Number(quantidade)),
        observacao: observacao || null,
      })
      toast.success('Estoque ajustado.')
      setQuantidade('')
      setObservacao('')
      await reload()
      const movimentos = await listMovimentacoes(produtoId, { page: movPage, per_page: 15 })
      setMovimentacoes(movimentos.data || [])
      setMovMeta(metaFromResponse(movimentos, (movimentos.data || []).length))
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível ajustar o estoque.')
    } finally {
      submittingRef.current = false
      setSubmitting(false)
    }
  }

  return (
    <div data-testid="page-estoque">
      <div className="page-head">
        <div>
          <h1>Estoque</h1>
          <p>Entradas, saídas e ajustes por unidade.</p>
        </div>
      </div>

      <form className="panel mb-4 grid gap-3 md:grid-cols-2 lg:grid-cols-5" onSubmit={salvar}>
        <div className="field" style={{ margin: 0 }}>
          <label>Produto</label>
          <select
            value={produtoId}
            onChange={(e) => {
              setProdutoId(e.target.value)
              setMovPage(1)
              if (!e.target.value) setMovimentacoes([])
            }}
          >
            <option value="">Selecione o produto</option>
            {produtos.map((p) => (
              <option key={p.id} value={p.id}>
                {p.descricao} ({formatQtd(p.estoque_atual)})
              </option>
            ))}
          </select>
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Tipo</label>
          <select value={tipo} onChange={(e) => setTipo(e.target.value)}>
            <option value="entrada">Entrada</option>
            <option value="saida">Saída</option>
            <option value="ajuste">Ajuste</option>
          </select>
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Quantidade</label>
          <input
            type="number"
            min="1"
            step="1"
            placeholder="Unidades"
            value={quantidade}
            onChange={(e) => setQuantidade(e.target.value)}
          />
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Observação</label>
          <input placeholder="Opcional" value={observacao} onChange={(e) => setObservacao(e.target.value)} />
        </div>
        <button className="btn btn-primary self-end" disabled={submitting} data-testid="estoque-salvar">
          {submitting ? 'Processando…' : 'Ajustar estoque'}
        </button>
      </form>

      <div className="grid gap-5 md:grid-cols-2">
        <section className="panel">
          <h2 className="m-0 text-lg font-extrabold">Saldos</h2>
          <SearchBar value={q} onChange={setQ} placeholder="Filtrar produtos" />
          <div className="table-wrap">
            <table className="data" style={{ minWidth: 0 }}>
              <thead>
                <tr>
                  <th>Produto</th>
                  <th>Saldo</th>
                </tr>
              </thead>
              <tbody>
                {produtos.map((p) => (
                  <tr key={p.id}>
                    <td>{p.descricao}</td>
                    <td>
                      <strong>{formatQtd(p.estoque_atual)}</strong>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pagination meta={meta} onPageChange={setPage} />
        </section>

        <section className="panel">
          <h2 className="m-0 text-lg font-extrabold">Movimentações</h2>
          <SearchBar
            value={movQ}
            onChange={(v) => {
              setMovPage(1)
              setMovQ(v)
            }}
            placeholder="Filtrar tipo ou observação"
          />
          {!produtoId ? (
            <p className="hint">Selecione um produto para ver o histórico.</p>
          ) : (
            <>
              <div className="table-wrap">
                <table className="data" style={{ minWidth: 0 }}>
                  <thead>
                    <tr>
                      <th>Tipo</th>
                      <th>Qtd</th>
                      <th>Obs.</th>
                    </tr>
                  </thead>
                  <tbody>
                    {movimentacoes.length === 0 ? (
                      <tr>
                        <td colSpan={3} className="hint">
                          Sem movimentações.
                        </td>
                      </tr>
                    ) : (
                      movimentacoes.map((m) => (
                        <tr key={m.id}>
                          <td>
                            <strong>{m.tipo}</strong>
                          </td>
                          <td>{formatQtd(m.quantidade)}</td>
                          <td className="hint">{m.observacao || '—'}</td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
              <Pagination meta={movMeta} onPageChange={setMovPage} />
            </>
          )}
        </section>
      </div>
    </div>
  )
}
