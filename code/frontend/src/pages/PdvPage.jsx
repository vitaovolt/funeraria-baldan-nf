import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { createConsignado } from '../api/consignado'
import { listClientes, listProdutos } from '../api/dominio'
import { finalizarVenda, getCaixaAtual } from '../api/pdv'
import { Pagination, SearchBar } from '../components/ListToolbar'
import { useToast } from '../context/ToastContext'
import { formatQtd, money, metaFromResponse } from '../utils/format'

function newIdempotencyKey() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID()
  }
  return `venda-${Date.now()}-${Math.random().toString(16).slice(2)}`
}

export default function PdvPage() {
  const toast = useToast()
  const navigate = useNavigate()
  const submittingRef = useRef(false)
  const [caixaOk, setCaixaOk] = useState(false)
  const [q, setQ] = useState('')
  const [prodPage, setProdPage] = useState(1)
  const [produtos, setProdutos] = useState([])
  const [prodMeta, setProdMeta] = useState({ current_page: 1, last_page: 1, total: 0 })
  const [carrinho, setCarrinho] = useState([])
  const [descontoTipo, setDescontoTipo] = useState('nenhum')
  const [descontoValor, setDescontoValor] = useState('')
  const [formaPagamento, setFormaPagamento] = useState('dinheiro')
  const [modo, setModo] = useState('venda')
  const [cliente, setCliente] = useState(null)
  const [cliQ, setCliQ] = useState('')
  const [cliOpts, setCliOpts] = useState([])
  const [cliOpen, setCliOpen] = useState(false)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    let cancelled = false
    getCaixaAtual()
      .then((r) => {
        if (cancelled) return
        if (!r.data) {
          toast.error('Abra o caixa antes de vender.')
          navigate('/caixa')
          return
        }
        setCaixaOk(true)
      })
      .catch(() => {
        if (!cancelled) navigate('/caixa')
      })
    return () => {
      cancelled = true
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [navigate])

  useEffect(() => {
    if (!caixaOk) return undefined
    const t = window.setTimeout(() => {
      listProdutos({ q: q || undefined, ativo: true, page: prodPage, per_page: 12 })
        .then((r) => {
          setProdutos(r.data || [])
          setProdMeta(metaFromResponse(r, (r.data || []).length))
        })
        .catch(() => toast.error('Falha ao buscar produtos.'))
    }, 200)
    return () => window.clearTimeout(t)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q, caixaOk, prodPage])

  useEffect(() => {
    if (!cliOpen) return undefined
    const t = window.setTimeout(() => {
      listClientes({ q: cliQ || undefined, ativo: true, page: 1, per_page: 8 })
        .then((r) => setCliOpts(r.data || []))
        .catch(() => setCliOpts([]))
    }, 200)
    return () => window.clearTimeout(t)
  }, [cliQ, cliOpen])

  const subtotal = useMemo(
    () => carrinho.reduce((acc, i) => acc + Number(i.preco_venda) * i.quantidade, 0),
    [carrinho],
  )

  const descontoAplicado = useMemo(() => {
    const dv = Number(descontoValor || 0)
    if (descontoTipo === 'percentual') return (subtotal * dv) / 100
    if (descontoTipo === 'valor') return dv
    return 0
  }, [subtotal, descontoTipo, descontoValor])

  const total = useMemo(() => Math.max(0, subtotal - descontoAplicado), [subtotal, descontoAplicado])

  function addProduto(p) {
    setCarrinho((prev) => {
      const exists = prev.find((i) => i.produto_id === p.id)
      if (exists) {
        return prev.map((i) =>
          i.produto_id === p.id ? { ...i, quantidade: i.quantidade + 1 } : i,
        )
      }
      return [
        ...prev,
        {
          produto_id: p.id,
          descricao: p.descricao,
          codigo_barras: p.codigo_barras,
          preco_venda: p.preco_venda,
          quantidade: 1,
        },
      ]
    })
  }

  function setQty(produtoId, quantidade) {
    const qtd = Math.trunc(Number(quantidade))
    if (!qtd || qtd <= 0) {
      setCarrinho((prev) => prev.filter((i) => i.produto_id !== produtoId))
      return
    }
    setCarrinho((prev) => prev.map((i) => (i.produto_id === produtoId ? { ...i, quantidade: qtd } : i)))
  }

  function limparVenda() {
    setCarrinho([])
    setDescontoTipo('nenhum')
    setDescontoValor('')
    setFormaPagamento('dinheiro')
    setCliente(null)
    setCliQ('')
    setModo('venda')
  }

  async function onFinalizar() {
    if (submittingRef.current) return
    if (carrinho.length === 0) {
      toast.error('Adicione produtos ao carrinho.')
      return
    }
    if (modo === 'consignado' && !cliente) {
      toast.error('Selecione o cliente para consignar.')
      return
    }

    submittingRef.current = true
    setSubmitting(true)
    try {
      if (modo === 'consignado') {
        const res = await createConsignado({
          cliente_id: cliente.id,
          itens: carrinho.map((i) => ({
            produto_id: i.produto_id,
            quantidade: i.quantidade,
          })),
        })
        toast.success(`Consignado #${res.data.id} criado para ${cliente.nome}.`)
        limparVenda()
        navigate('/consignado')
        return
      }

      const payload = {
        itens: carrinho.map((i) => ({
          produto_id: i.produto_id,
          quantidade: i.quantidade,
        })),
        cliente_id: cliente?.id || undefined,
        desconto_tipo: descontoTipo,
        desconto_valor: Number(descontoValor || 0),
        forma_pagamento: formaPagamento,
      }
      const res = await finalizarVenda(payload, { idempotencyKey: newIdempotencyKey() })
      toast.success(`Venda #${res.data.id} finalizada. NFC-e ${res.data.nota_nfce?.status}.`)
      limparVenda()
      navigate('/caixa')
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      toast.error(
        err.response?.data?.message ||
          (modo === 'consignado'
            ? 'Não foi possível criar o consignado.'
            : 'Não foi possível finalizar a venda.'),
      )
    }
  }

  const onBuscaProduto = useCallback((value) => {
    setProdPage(1)
    setQ(value)
  }, [])

  if (!caixaOk) return <p className="text-[var(--muted)]">Verificando caixa…</p>

  return (
    <div data-testid="page-pdv">
      <div className="page-head">
        <div>
          <h1>Venda</h1>
          <p>Escolha o cliente, monte o carrinho e finalize o pagamento — ou consignar para provar.</p>
        </div>
      </div>

      <div className="pdv-grid">
        <section className="panel">
          {cliente ? (
            <div className="cli-chip">
              <div>
                <strong>{cliente.nome}</strong>
                <span className="hint">
                  {cliente.documento || 'sem documento'}
                  {cliente.telefone ? ` · ${cliente.telefone}` : ''}
                </span>
              </div>
              <button type="button" className="btn btn-ghost" style={{ padding: '7px 12px' }} onClick={() => setCliente(null)}>
                Trocar
              </button>
            </div>
          ) : (
            <div className="field" style={{ position: 'relative' }}>
              <label>Cliente da venda</label>
              <input
                value={cliQ}
                placeholder="Buscar por nome ou documento…"
                onChange={(e) => {
                  setCliQ(e.target.value)
                  setCliOpen(true)
                }}
                onFocus={() => setCliOpen(true)}
                data-testid="busca-cliente"
              />
              {cliOpen ? (
                <div className="combo-list panel" style={{ position: 'absolute', zIndex: 5, left: 0, right: 0, marginTop: 4, padding: 8 }}>
                  <button
                    type="button"
                    className="product-pick"
                    style={{ display: 'block', marginBottom: 4 }}
                    onClick={() => {
                      setCliente(null)
                      setCliQ('')
                      setCliOpen(false)
                    }}
                  >
                    <strong>Consumidor final</strong>
                    <span className="hint">Sem vínculo de cadastro</span>
                  </button>
                  {cliOpts.map((c) => (
                    <button
                      key={c.id}
                      type="button"
                      className="product-pick"
                      style={{ display: 'block', marginBottom: 4 }}
                      onClick={() => {
                        setCliente(c)
                        setCliQ('')
                        setCliOpen(false)
                      }}
                    >
                      <strong>{c.nome}</strong>
                      <span className="hint">{c.documento}</span>
                    </button>
                  ))}
                </div>
              ) : null}
            </div>
          )}

          <SearchBar
            value={q}
            onChange={onBuscaProduto}
            placeholder="Produto: nome ou código de barras"
            testId="busca-produto"
          />
          <div className="product-pick" data-testid="lista-produtos">
            {produtos.length === 0 ? (
              <div className="empty-state">Nenhum produto encontrado.</div>
            ) : (
              produtos.map((p) => (
                <button
                  key={p.id}
                  type="button"
                  onClick={() => addProduto(p)}
                  data-testid={`add-produto-${p.id}`}
                >
                  <span>
                    <strong>{p.descricao}</strong>
                    <br />
                    <span className="meta">{p.codigo_barras} · estoque {formatQtd(p.estoque_atual)}</span>
                  </span>
                  <span className="font-bold text-[var(--brand-primary)]">{money(p.preco_venda)}</span>
                </button>
              ))
            )}
          </div>
          <Pagination meta={prodMeta} onPageChange={setProdPage} />

          <div className="mt-4">
            <h2 className="m-0 text-base font-bold text-[var(--brand-primary)]">Carrinho</h2>
            {carrinho.length === 0 ? (
              <div className="empty-state mt-3">
                <strong>Carrinho vazio</strong>
                <br />
                <span className="hint">Busque um produto acima para adicionar.</span>
              </div>
            ) : (
              <div className="cart-list mt-3" data-testid="carrinho">
                {carrinho.map((i) => (
                  <div key={i.produto_id} className="cart-item">
                    <div>
                      <strong>{i.descricao}</strong>
                      <div className="meta">{i.codigo_barras}</div>
                    </div>
                    <div className="qty">
                      <button type="button" onClick={() => setQty(i.produto_id, i.quantidade - 1)} aria-label="Diminuir">
                        −
                      </button>
                      <span>{formatQtd(i.quantidade)}</span>
                      <button type="button" onClick={() => setQty(i.produto_id, i.quantidade + 1)} aria-label="Aumentar">
                        +
                      </button>
                    </div>
                    <div style={{ textAlign: 'right' }}>
                      <strong>{money(i.preco_venda * i.quantidade)}</strong>
                      <br />
                      <button
                        type="button"
                        className="hint"
                        style={{ background: 'none', border: 0, padding: 0, marginTop: 4, textDecoration: 'underline' }}
                        onClick={() => setQty(i.produto_id, 0)}
                      >
                        Remover
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </section>

        <aside className="panel pdv-summary">
          <div className="modo-venda" role="group" aria-label="Tipo de operação">
            <button type="button" className={modo === 'venda' ? 'on' : ''} onClick={() => setModo('venda')} data-testid="modo-venda">
              Venda
            </button>
            <button
              type="button"
              className={modo === 'consignado' ? 'on' : ''}
              onClick={() => setModo('consignado')}
              data-testid="modo-consignado"
            >
              Consignado
            </button>
          </div>
          {modo === 'consignado' ? (
            <p className="hint m-0 mb-3">Consignado = levar para provar (baixa estoque). Cliente obrigatório.</p>
          ) : null}

          <div className="hint">Subtotal</div>
          <div style={{ fontWeight: 700, marginBottom: 8 }}>{money(subtotal)}</div>

          {modo === 'venda' ? (
            <>
              <div className="field">
                <label htmlFor="desc-tipo">Desconto</label>
                <select
                  id="desc-tipo"
                  value={descontoTipo}
                  onChange={(e) => setDescontoTipo(e.target.value)}
                  data-testid="desconto-tipo"
                >
                  <option value="nenhum">Sem desconto</option>
                  <option value="percentual">Percentual (%)</option>
                  <option value="valor">Valor (R$)</option>
                </select>
              </div>
              {descontoTipo !== 'nenhum' ? (
                <div className="field">
                  <label htmlFor="desc-valor">{descontoTipo === 'percentual' ? 'Percentual' : 'Valor em R$'}</label>
                  <input
                    id="desc-valor"
                    type="number"
                    min="0"
                    step="0.01"
                    value={descontoValor}
                    onChange={(e) => setDescontoValor(e.target.value)}
                    placeholder={descontoTipo === 'percentual' ? '10' : '50,00'}
                    data-testid="desconto-valor"
                  />
                </div>
              ) : null}
              {descontoAplicado > 0 ? <p className="hint">Desconto aplicado: −{money(descontoAplicado)}</p> : null}

              <div className="field">
                <label>Forma de pagamento</label>
                <div className="pay-options" data-testid="forma-pagamento">
                  {[
                    ['dinheiro', 'Dinheiro'],
                    ['pix', 'PIX'],
                    ['cartao', 'Cartão'],
                  ].map(([value, label]) => (
                    <label key={value} className={formaPagamento === value ? 'on' : ''}>
                      <input
                        type="radio"
                        name="forma"
                        value={value}
                        checked={formaPagamento === value}
                        onChange={() => setFormaPagamento(value)}
                      />
                      {label}
                    </label>
                  ))}
                </div>
              </div>
            </>
          ) : null}

          <div className="hint">Total</div>
          <div className="total" data-testid="total-venda">
            {money(total)}
          </div>

          <button
            type="button"
            className="btn btn-accent w-full"
            disabled={submitting || carrinho.length === 0}
            onClick={onFinalizar}
            data-testid="pagar-emitir"
          >
            {submitting
              ? 'Processando…'
              : modo === 'consignado'
                ? 'Consignar produtos'
                : 'Pagar e emitir nota'}
          </button>
          <button type="button" className="btn btn-ghost mt-2 w-full" disabled={submitting} onClick={limparVenda}>
            Limpar venda
          </button>
        </aside>
      </div>
    </div>
  )
}
