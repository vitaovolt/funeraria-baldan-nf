import { useCallback, useEffect, useRef, useState } from 'react'
import { criarMovimentacao, listMovimentacoes, listProdutos } from '../api/dominio'
import { useToast } from '../context/ToastContext'

export default function EstoquePage() {
  const toast = useToast()
  const submittingRef = useRef(false)
  const [produtos, setProdutos] = useState([])
  const [produtoId, setProdutoId] = useState('')
  const [tipo, setTipo] = useState('entrada')
  const [quantidade, setQuantidade] = useState('')
  const [observacao, setObservacao] = useState('')
  const [movimentacoes, setMovimentacoes] = useState([])
  const [submitting, setSubmitting] = useState(false)

  const carregar = useCallback(async () => {
    try {
      const res = await listProdutos()
      setProdutos(res.data || [])
    } catch {
      toast.error('Não foi possível carregar o estoque.')
    }
  }, [toast])

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    carregar()
  }, [carregar])

  useEffect(() => {
    if (!produtoId) return
    // eslint-disable-next-line react-hooks/set-state-in-effect
    listMovimentacoes(produtoId)
      .then((res) => setMovimentacoes(res.data || []))
      .catch(() => toast.error('Não foi possível carregar as movimentações.'))
  }, [produtoId, toast])

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
      await criarMovimentacao(produtoId, { tipo, quantidade: Number(quantidade), observacao: observacao || null })
      toast.success('Estoque ajustado.')
      setQuantidade('')
      setObservacao('')
      const [lista, movimentos] = await Promise.all([listProdutos(), listMovimentacoes(produtoId)])
      setProdutos(lista.data || [])
      setMovimentacoes(movimentos.data || [])
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível ajustar o estoque.')
    } finally {
      submittingRef.current = false
      setSubmitting(false)
    }
  }

  const inputClass = 'rounded-lg border border-[var(--line)] px-3 py-2'
  return (
    <div data-testid="page-estoque">
      <h1 className="m-0 text-2xl font-extrabold text-[var(--brand-primary)]">Estoque</h1>
      <form className="mt-5 grid gap-3 rounded-[10px] border border-[var(--line)] bg-white p-4 md:grid-cols-2 lg:grid-cols-5" onSubmit={salvar}>
        <select
          className={inputClass}
          value={produtoId}
          onChange={(e) => {
            setProdutoId(e.target.value)
            if (!e.target.value) setMovimentacoes([])
          }}
        >
          <option value="">Selecione o produto</option>
          {produtos.map((p) => <option key={p.id} value={p.id}>{p.descricao}</option>)}
        </select>
        <select className={inputClass} value={tipo} onChange={(e) => setTipo(e.target.value)}>
          <option value="entrada">Entrada</option><option value="saida">Saída</option><option value="ajuste">Ajuste</option>
        </select>
        <input className={inputClass} type="number" min="0.001" step="0.001" placeholder="Quantidade" value={quantidade} onChange={(e) => setQuantidade(e.target.value)} />
        <input className={inputClass} placeholder="Observação" value={observacao} onChange={(e) => setObservacao(e.target.value)} />
        <button className="rounded-lg bg-[var(--brand-primary)] px-4 py-2 font-bold text-white disabled:opacity-60" disabled={submitting} data-testid="estoque-salvar">
          {submitting ? 'Processando…' : 'Ajustar estoque'}
        </button>
      </form>
      <div className="mt-5 grid gap-5 md:grid-cols-2">
        <section className="rounded-[10px] border border-[var(--line)] bg-white p-4">
          <h2 className="m-0 text-lg font-extrabold">Saldos</h2>
          <ul className="mt-3 divide-y divide-[var(--line)]">{produtos.map((p) => <li key={p.id} className="flex justify-between py-2 text-sm"><span>{p.descricao}</span><strong>{p.estoque_atual}</strong></li>)}</ul>
        </section>
        <section className="rounded-[10px] border border-[var(--line)] bg-white p-4">
          <h2 className="m-0 text-lg font-extrabold">Movimentações</h2>
          <ul className="mt-3 divide-y divide-[var(--line)]">{movimentacoes.map((m) => <li key={m.id} className="py-2 text-sm"><strong>{m.tipo}</strong> · {m.quantidade} {m.observacao ? `· ${m.observacao}` : ''}</li>)}</ul>
        </section>
      </div>
    </div>
  )
}
