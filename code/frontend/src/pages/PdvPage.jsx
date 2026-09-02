import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { createConsignado } from '../api/consignado'
import { createCliente, getConfiguracaoFiscal, listClientes, listProdutos } from '../api/dominio'
import { finalizarVenda, getCaixaAtual } from '../api/pdv'
import { Pagination, SearchBar } from '../components/ListToolbar'
import MoneyInput from '../components/MoneyInput'
import NfcePerguntaModal from '../components/NfcePerguntaModal'
import { useToast } from '../context/ToastContext'
import { formatQtd, money, metaFromResponse } from '../utils/format'
import { abrirHtmlImpressao, imprimirPosVenda, maskCpfCnpj, soDigitos } from '../utils/impressao'
import { parseBrl } from '../utils/moneyMask'

function newIdempotencyKey() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID()
  }
  return `venda-${Date.now()}-${Math.random().toString(16).slice(2)}`
}

const clienteRapidoInicial = { tipo: 'pf', nome: '', documento: '' }

export default function PdvPage() {
  const toast = useToast()
  const navigate = useNavigate()
  const submittingRef = useRef(false)
  const buscaRef = useRef(null)
  const [caixaOk, setCaixaOk] = useState(false)
  const [q, setQ] = useState('')
  const [prodPage, setProdPage] = useState(1)
  const [produtos, setProdutos] = useState([])
  const [prodMeta, setProdMeta] = useState({ current_page: 1, last_page: 1, total: 0 })
  const [carrinho, setCarrinho] = useState([])
  const [descontoTipo, setDescontoTipo] = useState('nenhum')
  const [descontoValor, setDescontoValor] = useState('')
  const [formaPagamento, setFormaPagamento] = useState('dinheiro')
  const [valorRecebido, setValorRecebido] = useState('')
  const [modo, setModo] = useState('venda')
  const [cliente, setCliente] = useState(null)
  const [cliQ, setCliQ] = useState('')
  const [cliOpts, setCliOpts] = useState([])
  const [cliOpen, setCliOpen] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [nfceOpen, setNfceOpen] = useState(false)
  const [documentoNfce, setDocumentoNfce] = useState('')
  const [moduloFiscalAtivo, setModuloFiscalAtivo] = useState(false)
  const [clienteRapidoOpen, setClienteRapidoOpen] = useState(false)
  const [clienteRapido, setClienteRapido] = useState(clienteRapidoInicial)

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
    getConfiguracaoFiscal()
      .then((r) => {
        if (!cancelled) setModuloFiscalAtivo(Boolean(r.data?.modulo_fiscal_ativo))
      })
      .catch(() => {
        if (!cancelled) setModuloFiscalAtivo(false)
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
    const dv = descontoTipo === 'valor' ? parseBrl(descontoValor) || 0 : Number(descontoValor || 0)
    if (descontoTipo === 'percentual') return (subtotal * dv) / 100
    if (descontoTipo === 'valor') return dv
    return 0
  }, [subtotal, descontoTipo, descontoValor])

  const total = useMemo(() => Math.max(0, subtotal - descontoAplicado), [subtotal, descontoAplicado])

  const recebidoNum = useMemo(() => parseBrl(valorRecebido), [valorRecebido])

  const troco = useMemo(() => {
    if (formaPagamento !== 'dinheiro' || recebidoNum == null) return null
    return Math.round((recebidoNum - total) * 100) / 100
  }, [formaPagamento, recebidoNum, total])

  function focarBusca() {
    window.setTimeout(() => buscaRef.current?.focus(), 0)
  }

  function addProduto(p) {
    const estoque = Number(p.estoque_atual)
    let recusado = false
    setCarrinho((prev) => {
      const exists = prev.find((i) => i.produto_id === p.id)
      const novaQtd = (exists ? exists.quantidade : 0) + 1
      if (estoque >= 0 && novaQtd > estoque) {
        recusado = true
        return prev
      }
      if (exists) {
        return prev.map((i) => (i.produto_id === p.id ? { ...i, quantidade: novaQtd } : i))
      }
      return [
        ...prev,
        {
          produto_id: p.id,
          descricao: p.descricao,
          codigo_barras: p.codigo_barras,
          preco_venda: p.preco_venda,
          estoque_atual: estoque,
          quantidade: 1,
        },
      ]
    })
    if (recusado) {
      toast.error(`Estoque insuficiente para ${p.descricao} (${formatQtd(estoque)} un.).`)
      return
    }
    focarBusca()
  }

  function setQty(produtoId, quantidade) {
    const qtd = Math.trunc(Number(quantidade))
    if (!qtd || qtd <= 0) {
      setCarrinho((prev) => prev.filter((i) => i.produto_id !== produtoId))
      return
    }
    setCarrinho((prev) =>
      prev.map((i) => {
        if (i.produto_id !== produtoId) return i
        if (i.estoque_atual >= 0 && qtd > i.estoque_atual) {
          toast.error(`Estoque insuficiente (${formatQtd(i.estoque_atual)} un.).`)
          return { ...i, quantidade: i.estoque_atual }
        }
        return { ...i, quantidade: qtd }
      }),
    )
  }

  function limparVenda() {
    setCarrinho([])
    setDescontoTipo('nenhum')
    setDescontoValor('')
    setFormaPagamento('dinheiro')
    setValorRecebido('')
    setCliente(null)
    setCliQ('')
    setModo('venda')
    setDocumentoNfce('')
    focarBusca()
  }

  function pedirLimpar() {
    if (carrinho.length === 0) return
    if (!window.confirm('Limpar o carrinho desta venda?')) return
    limparVenda()
  }

  function validarAntesDePagar() {
    if (carrinho.length === 0) {
      toast.error('Adicione produtos ao carrinho.')
      return false
    }
    if (modo === 'consignado' && !cliente) {
      toast.error('Selecione o cliente para consignar.')
      return false
    }
    if (modo === 'venda' && formaPagamento === 'dinheiro' && recebidoNum != null && recebidoNum < total) {
      toast.error(`Valor recebido insuficiente. Faltam ${money(total - recebidoNum)}.`)
      return false
    }
    return true
  }

  function onPedirFinalizar() {
    if (submittingRef.current) return
    if (!validarAntesDePagar()) return
    if (modo === 'consignado') {
      void onConsignar()
      return
    }
    if (!moduloFiscalAtivo) {
      void onConfirmarNfce(false, '')
      return
    }
    setDocumentoNfce(maskCpfCnpj(cliente?.documento || ''))
    setNfceOpen(true)
  }

  async function onConsignar() {
    if (submittingRef.current) return
    submittingRef.current = true
    setSubmitting(true)
    try {
      const res = await createConsignado({
        cliente_id: cliente.id,
        itens: carrinho.map((i) => ({
          produto_id: i.produto_id,
          quantidade: i.quantidade,
        })),
      })
      toast.success(`Consignado #${res.data.id} criado para ${cliente.nome}.`)
      try {
        await abrirHtmlImpressao(`/consignados/${res.data.id}/notinha`)
      } catch (err) {
        toast.error(err.message || 'Consignado criado. Impressão da notinha bloqueada.')
      }
      limparVenda()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível criar o consignado.')
    } finally {
      submittingRef.current = false
      setSubmitting(false)
    }
  }

  async function onConfirmarNfce(emitir, documento) {
    if (submittingRef.current) return
    if (!validarAntesDePagar()) return
    submittingRef.current = true
    setSubmitting(true)
    try {
      const payload = {
        itens: carrinho.map((i) => ({
          produto_id: i.produto_id,
          quantidade: i.quantidade,
        })),
        cliente_id: cliente?.id || undefined,
        desconto_tipo: descontoTipo,
        desconto_valor:
          descontoTipo === 'valor' ? parseBrl(descontoValor) || 0 : Number(descontoValor || 0),
        forma_pagamento: formaPagamento,
        emitir_nfce: emitir,
        documento_nfce: documento || undefined,
        valor_recebido: formaPagamento === 'dinheiro' && recebidoNum != null ? recebidoNum : undefined,
      }
      const res = await finalizarVenda(payload, { idempotencyKey: newIdempotencyKey() })
      const venda = res.data
      const nota = venda.nota_nfce
      const msgTroco =
        formaPagamento === 'dinheiro' && troco != null && troco > 0 ? ` Troco: ${money(troco)}.` : ''
      if (emitir && nota?.status === 'autorizada') {
        toast.success(`Venda #${venda.id} finalizada. NFC-e autorizada.${msgTroco}`)
      } else if (emitir && nota) {
        toast.error(
          `Venda #${venda.id} gravada. NFC-e: ${nota.mensagem_erro || nota.status}. Imprimindo comprovante.`,
        )
      } else {
        toast.success(`Venda #${venda.id} finalizada. Comprovante sem valor fiscal.${msgTroco}`)
      }
      try {
        await imprimirPosVenda(venda)
      } catch (err) {
        toast.error(err.message || 'Venda ok. Não foi possível abrir a impressão.')
      }
      setNfceOpen(false)
      limparVenda()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível finalizar a venda.')
    } finally {
      submittingRef.current = false
      setSubmitting(false)
    }
  }

  const onBuscaProduto = useCallback((value) => {
    setProdPage(1)
    setQ(value)
  }, [])

  async function onBuscaKeyDown(event) {
    if (event.key !== 'Enter') return
    event.preventDefault()
    const termo = q.trim()
    if (!termo) return
    try {
      const r = await listProdutos({ q: termo, ativo: true, page: 1, per_page: 12 })
      const lista = r.data || []
      const exato = lista.find(
        (p) => p.codigo_barras === termo || String(p.referencia || '') === termo,
      )
      const alvo = exato || (lista.length === 1 ? lista[0] : null)
      if (alvo) {
        addProduto(alvo)
        setQ('')
        setProdPage(1)
        return
      }
      if (lista.length === 0) {
        toast.error('Produto não encontrado.')
      }
    } catch {
      toast.error('Falha ao buscar produtos.')
    }
  }

  async function salvarClienteRapido(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!clienteRapido.nome.trim() || soDigitos(clienteRapido.documento).length < 11) {
      toast.error('Informe nome e CPF/CNPJ.')
      return
    }
    submittingRef.current = true
    setSubmitting(true)
    try {
      const res = await createCliente({
        tipo: clienteRapido.tipo,
        nome: clienteRapido.nome.trim(),
        documento: soDigitos(clienteRapido.documento),
      })
      setCliente(res.data)
      setClienteRapidoOpen(false)
      setClienteRapido(clienteRapidoInicial)
      setCliOpen(false)
      toast.success('Cliente cadastrado.')
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível cadastrar o cliente.')
    } finally {
      submittingRef.current = false
      setSubmitting(false)
    }
  }

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
              <div className="cli-busca-row">
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
                <button
                  type="button"
                  className="btn btn-ghost"
                  onClick={() => {
                    setClienteRapidoOpen(true)
                    setCliOpen(false)
                  }}
                  data-testid="cliente-rapido"
                >
                  + Cliente
                </button>
              </div>
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
                  {cliOpts.length === 0 ? <p className="hint m-0">Nenhum cliente encontrado.</p> : null}
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
            onKeyDown={onBuscaKeyDown}
            inputRef={buscaRef}
            placeholder="Produto: nome ou código de barras (Enter adiciona)"
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
                    <span className="meta">
                      {p.codigo_barras} · estoque {formatQtd(p.estoque_atual)}
                    </span>
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
                <span className="hint">Busque um produto acima e pressione Enter, ou clique para adicionar.</span>
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
                      <input
                        className="qty-input"
                        inputMode="numeric"
                        value={i.quantidade}
                        onChange={(e) => setQty(i.produto_id, e.target.value.replace(/\D/g, ''))}
                        aria-label="Quantidade"
                      />
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
                  onChange={(e) => {
                    setDescontoTipo(e.target.value)
                    setDescontoValor('')
                  }}
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
                  {descontoTipo === 'valor' ? (
                    <MoneyInput
                      id="desc-valor"
                      value={descontoValor}
                      onChange={setDescontoValor}
                      placeholder="0,00"
                      data-testid="desconto-valor"
                    />
                  ) : (
                    <input
                      id="desc-valor"
                      type="text"
                      inputMode="decimal"
                      value={descontoValor}
                      onChange={(e) => setDescontoValor(e.target.value.replace(/[^\d.,]/g, ''))}
                      placeholder="10"
                      data-testid="desconto-valor"
                    />
                  )}
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
                        onChange={() => {
                          setFormaPagamento(value)
                          if (value !== 'dinheiro') setValorRecebido('')
                        }}
                      />
                      {label}
                    </label>
                  ))}
                </div>
              </div>

              {formaPagamento === 'dinheiro' ? (
                <div className="field" data-testid="campo-troco">
                  <label htmlFor="valor-recebido">Valor recebido (opcional)</label>
                  <MoneyInput
                    id="valor-recebido"
                    value={valorRecebido}
                    onChange={setValorRecebido}
                    placeholder={total > 0 ? total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0,00'}
                    data-testid="valor-recebido"
                  />
                  <p className="hint m-0 mt-1">Informe se o cliente pagar com valor maior para ver o troco.</p>
                  {troco != null && troco >= 0 ? (
                    <p className="troco-ok m-0 mt-2" data-testid="troco-valor">
                      Troco: <strong>{money(troco)}</strong>
                    </p>
                  ) : null}
                  {troco != null && troco < 0 ? (
                    <p className="troco-faltando m-0 mt-2" data-testid="troco-faltando">
                      Faltam {money(Math.abs(troco))}
                    </p>
                  ) : null}
                </div>
              ) : null}
            </>
          ) : null}

          <div className="hint">Total</div>
          <div className="total" data-testid="total-venda">
            {money(total)}
          </div>

          <button
            type="button"
            className="btn btn-accent w-full"
            disabled={
              submitting ||
              carrinho.length === 0 ||
              (modo === 'venda' && formaPagamento === 'dinheiro' && troco != null && troco < 0)
            }
            onClick={onPedirFinalizar}
            data-testid="pagar-emitir"
          >
            {submitting ? 'Processando…' : modo === 'consignado' ? 'Consignar produtos' : 'Finalizar venda'}
          </button>
          <button type="button" className="btn btn-ghost mt-2 w-full" disabled={submitting} onClick={pedirLimpar}>
            Limpar venda
          </button>
        </aside>
      </div>

      <NfcePerguntaModal
        open={nfceOpen}
        documento={documentoNfce}
        onDocumento={setDocumentoNfce}
        submitting={submitting}
        onConfirmar={onConfirmarNfce}
        onCancelar={() => {
          if (!submitting) setNfceOpen(false)
        }}
      />

      {clienteRapidoOpen ? (
        <div className="modal-backdrop" role="presentation" onMouseDown={() => !submitting && setClienteRapidoOpen(false)}>
          <form
            className="modal-card panel"
            data-testid="modal-cliente-rapido"
            onMouseDown={(e) => e.stopPropagation()}
            onSubmit={salvarClienteRapido}
          >
            <h2>Cadastrar cliente</h2>
            <div className="field">
              <label>Tipo</label>
              <select
                value={clienteRapido.tipo}
                onChange={(e) => setClienteRapido((a) => ({ ...a, tipo: e.target.value, documento: '' }))}
              >
                <option value="pf">Pessoa física</option>
                <option value="pj">Pessoa jurídica</option>
              </select>
            </div>
            <div className="field">
              <label>Nome</label>
              <input
                value={clienteRapido.nome}
                onChange={(e) => setClienteRapido((a) => ({ ...a, nome: e.target.value }))}
                required
              />
            </div>
            <div className="field">
              <label>{clienteRapido.tipo === 'pj' ? 'CNPJ' : 'CPF'}</label>
              <input
                value={clienteRapido.documento}
                onChange={(e) => setClienteRapido((a) => ({ ...a, documento: maskCpfCnpj(e.target.value) }))}
                required
              />
            </div>
            <div className="modal-actions">
              <button type="submit" className="btn btn-accent w-full" disabled={submitting}>
                {submitting ? 'Processando…' : 'Salvar'}
              </button>
              <button type="button" className="btn btn-ghost w-full" disabled={submitting} onClick={() => setClienteRapidoOpen(false)}>
                Cancelar
              </button>
            </div>
          </form>
        </div>
      ) : null}
    </div>
  )
}
