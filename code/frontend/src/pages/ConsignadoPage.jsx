import { useCallback, useEffect, useRef, useState } from 'react'
import {
  converterConsignado,
  createConsignado,
  devolverConsignado,
  listConsignados,
} from '../api/consignado'
import { listClientes, listProdutos } from '../api/dominio'
import { useToast } from '../context/ToastContext'

export default function ConsignadoPage() {
  const toast = useToast()
  const submittingRef = useRef(false)
  const [consignados, setConsignados] = useState([])
  const [clientes, setClientes] = useState([])
  const [produtos, setProdutos] = useState([])
  const [clienteId, setClienteId] = useState('')
  const [produtoId, setProdutoId] = useState('')
  const [quantidade, setQuantidade] = useState('1')
  const [submitting, setSubmitting] = useState('')

  const carregar = useCallback(async () => {
    try {
      const [cons, cli, prod] = await Promise.all([
        listConsignados({ abertos: 1 }),
        listClientes({ ativo: true }),
        listProdutos({ ativo: true }),
      ])
      setConsignados(cons.data || [])
      setClientes(cli.data || [])
      setProdutos(prod.data || [])
    } catch {
      toast.error('Não foi possível carregar os consignados.')
    }
  }, [toast])

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    carregar()
  }, [carregar])

  async function criar(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!clienteId || !produtoId || Number(quantidade) <= 0) {
      toast.error('Selecione cliente, produto e quantidade.')
      return
    }
    submittingRef.current = true
    setSubmitting('criar')
    try {
      await createConsignado({
        cliente_id: Number(clienteId),
        itens: [{ produto_id: Number(produtoId), quantidade: Number(quantidade) }],
      })
      toast.success('Consignado criado.')
      setProdutoId('')
      setQuantidade('1')
      await carregar()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível criar o consignado.')
    } finally {
      submittingRef.current = false
      setSubmitting('')
    }
  }

  async function agir(consignado, acao) {
    if (submittingRef.current) return
    const itens = consignado.itens
      .map((item) => ({
        item_id: item.id,
        quantidade: Number(item.quantidade) - Number(item.quantidade_devolvida) - Number(item.quantidade_vendida),
      }))
      .filter((item) => item.quantidade > 0)
    if (!itens.length) return toast.error('Não há itens pendentes.')
    submittingRef.current = true
    setSubmitting(`${acao}-${consignado.id}`)
    try {
      if (acao === 'devolver') await devolverConsignado(consignado.id, itens)
      else await converterConsignado(consignado.id, { itens, forma_pagamento: 'dinheiro' })
      toast.success(acao === 'devolver' ? 'Devolução registrada.' : 'Consignado convertido em venda.')
      await carregar()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível concluir a operação.')
    } finally {
      submittingRef.current = false
      setSubmitting('')
    }
  }

  const inputClass = 'rounded-lg border border-[var(--line)] px-3 py-2'
  return (
    <div data-testid="page-consignado">
      <h1 className="m-0 text-2xl font-extrabold text-[var(--brand-primary)]">Consignado</h1>
      <form className="mt-5 grid gap-3 rounded-[10px] border border-[var(--line)] bg-white p-4 md:grid-cols-[1fr_1fr_120px_auto]" onSubmit={criar}>
        <select className={inputClass} value={clienteId} onChange={(e) => setClienteId(e.target.value)}>
          <option value="">Selecione o cliente</option>{clientes.map((c) => <option key={c.id} value={c.id}>{c.nome}</option>)}
        </select>
        <select className={inputClass} value={produtoId} onChange={(e) => setProdutoId(e.target.value)}>
          <option value="">Selecione o produto</option>{produtos.map((p) => <option key={p.id} value={p.id}>{p.descricao}</option>)}
        </select>
        <input className={inputClass} type="number" min="0.001" step="0.001" value={quantidade} onChange={(e) => setQuantidade(e.target.value)} />
        <button className="rounded-lg bg-[var(--brand-accent)] px-4 py-2 font-bold text-[var(--brand-primary)] disabled:opacity-60" disabled={Boolean(submitting)} data-testid="consignado-criar">
          {submitting === 'criar' ? 'Processando…' : 'Criar consignado'}
        </button>
      </form>
      <div className="mt-5 grid gap-4">
        {consignados.map((c) => (
          <article key={c.id} className="rounded-[10px] border border-[var(--line)] bg-white p-4">
            <div className="flex flex-wrap justify-between gap-3">
              <div><strong>Consignado #{c.id} · {c.cliente?.nome}</strong>
                <ul className="mt-2 text-sm text-[var(--muted)]">{c.itens.map((i) => <li key={i.id}>{i.produto?.descricao} · {i.quantidade} enviado(s)</li>)}</ul>
              </div>
              <div className="flex items-start gap-2">
                <button type="button" className="rounded-lg border border-[var(--brand-primary)] px-3 py-2 text-sm font-bold disabled:opacity-60" disabled={Boolean(submitting)} onClick={() => agir(c, 'devolver')} data-testid="consignado-devolver">
                  {submitting === `devolver-${c.id}` ? 'Processando…' : 'Devolver pendentes'}
                </button>
                <button type="button" className="rounded-lg bg-[var(--brand-primary)] px-3 py-2 text-sm font-bold text-white disabled:opacity-60" disabled={Boolean(submitting)} onClick={() => agir(c, 'converter')} data-testid="consignado-converter">
                  {submitting === `converter-${c.id}` ? 'Processando…' : 'Virar venda'}
                </button>
              </div>
            </div>
          </article>
        ))}
      </div>
    </div>
  )
}
