import { useCallback, useEffect, useRef, useState } from 'react'
import {
  converterConsignado,
  createConsignado,
  devolverConsignado,
  listConsignados,
} from '../api/consignado'
import { listClientes, listProdutos } from '../api/dominio'
import { Pagination, SearchBar } from '../components/ListToolbar'
import { useToast } from '../context/ToastContext'
import { usePagedList } from '../hooks/usePagedList'
import { formatQtd } from '../utils/format'

export default function ConsignadoPage() {
  const toast = useToast()
  const submittingRef = useRef(false)
  const [clientes, setClientes] = useState([])
  const [produtos, setProdutos] = useState([])
  const [clienteId, setClienteId] = useState('')
  const [produtoId, setProdutoId] = useState('')
  const [quantidade, setQuantidade] = useState('1')
  const [submitting, setSubmitting] = useState('')

  const fetcher = useCallback((params) => listConsignados({ ...params, abertos: 1 }), [])
  const { q, setQ, setPage, items, meta, reload } = usePagedList(fetcher)

  useEffect(() => {
    Promise.all([
      listClientes({ ativo: true, per_page: 100 }),
      listProdutos({ ativo: true, per_page: 100 }),
    ])
      .then(([cli, prod]) => {
        setClientes(cli.data || [])
        setProdutos(prod.data || [])
      })
      .catch(() => toast.error('Não foi possível carregar clientes/produtos.'))
  }, [toast])

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
        itens: [{ produto_id: Number(produtoId), quantidade: Math.trunc(Number(quantidade)) }],
      })
      toast.success('Consignado criado.')
      setProdutoId('')
      setQuantidade('1')
      await reload()
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
        quantidade: Math.trunc(
          Number(item.quantidade) - Number(item.quantidade_devolvida) - Number(item.quantidade_vendida),
        ),
      }))
      .filter((item) => item.quantidade > 0)
    if (!itens.length) return toast.error('Não há itens pendentes.')
    submittingRef.current = true
    setSubmitting(`${acao}-${consignado.id}`)
    try {
      if (acao === 'devolver') await devolverConsignado(consignado.id, itens)
      else await converterConsignado(consignado.id, { itens, forma_pagamento: 'dinheiro' })
      toast.success(acao === 'devolver' ? 'Devolução registrada.' : 'Consignado convertido em venda.')
      await reload()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível concluir a operação.')
    } finally {
      submittingRef.current = false
      setSubmitting('')
    }
  }

  return (
    <div data-testid="page-consignado">
      <div className="page-head">
        <div>
          <h1>Consignado</h1>
          <p>Produtos levados para provar. Também pode consignar pelo PDV.</p>
        </div>
      </div>

      <form className="panel mb-4 grid gap-3 md:grid-cols-[1fr_1fr_120px_auto]" onSubmit={criar}>
        <div className="field" style={{ margin: 0 }}>
          <label>Cliente</label>
          <select value={clienteId} onChange={(e) => setClienteId(e.target.value)}>
            <option value="">Selecione o cliente</option>
            {clientes.map((c) => (
              <option key={c.id} value={c.id}>
                {c.nome}
              </option>
            ))}
          </select>
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Produto</label>
          <select value={produtoId} onChange={(e) => setProdutoId(e.target.value)}>
            <option value="">Selecione o produto</option>
            {produtos.map((p) => (
              <option key={p.id} value={p.id}>
                {p.descricao}
              </option>
            ))}
          </select>
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Qtd</label>
          <input
            type="number"
            min="1"
            step="1"
            value={quantidade}
            onChange={(e) => setQuantidade(e.target.value)}
          />
        </div>
        <button className="btn btn-accent self-end" disabled={Boolean(submitting)} data-testid="consignado-criar">
          {submitting === 'criar' ? 'Processando…' : 'Criar consignado'}
        </button>
      </form>

      <section className="panel">
        <SearchBar value={q} onChange={setQ} placeholder="Buscar por cliente ou nº" />
        <div className="table-wrap">
          <table className="data">
            <thead>
              <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Itens</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              {items.length === 0 ? (
                <tr>
                  <td colSpan={5} className="hint">
                    Nenhum consignado aberto.
                  </td>
                </tr>
              ) : (
                items.map((c) => (
                  <tr key={c.id}>
                    <td>{c.id}</td>
                    <td>
                      <strong>{c.cliente?.nome}</strong>
                    </td>
                    <td>
                      {(c.itens || []).map((i) => (
                        <div key={i.id} className="hint">
                          {i.produto?.descricao} · {formatQtd(i.quantidade)} un.
                        </div>
                      ))}
                    </td>
                    <td>
                      <span className="pill warn">{c.status}</span>
                    </td>
                    <td className="actions">
                      <button
                        type="button"
                        className="btn btn-ghost"
                        style={{ padding: '6px 12px', fontSize: '0.8rem' }}
                        disabled={Boolean(submitting)}
                        onClick={() => agir(c, 'devolver')}
                        data-testid="consignado-devolver"
                      >
                        {submitting === `devolver-${c.id}` ? 'Processando…' : 'Devolver'}
                      </button>{' '}
                      <button
                        type="button"
                        className="btn btn-primary"
                        style={{ padding: '6px 12px', fontSize: '0.8rem' }}
                        disabled={Boolean(submitting)}
                        onClick={() => agir(c, 'converter')}
                        data-testid="consignado-converter"
                      >
                        {submitting === `converter-${c.id}` ? 'Processando…' : 'Virar venda'}
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
