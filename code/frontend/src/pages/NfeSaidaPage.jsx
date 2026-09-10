import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { getConfiguracaoFiscal, listClientes, listProdutos } from '../api/dominio'
import { emitirNfeSaidaProdutos, getCaixaAtual } from '../api/pdv'
import { Pagination, SearchBar } from '../components/ListToolbar'
import { useToast } from '../context/ToastContext'
import { formatQtd, money, metaFromResponse } from '../utils/format'
import { abrirDanfe, maskCpfCnpj, soDigitos } from '../utils/impressao'

function newIdempotencyKey() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID()
  }
  return `nfe-${Date.now()}-${Math.random().toString(16).slice(2)}`
}

function enderecoCompleto(cliente) {
  if (!cliente) return false
  const cep = soDigitos(cliente.cep || '')
  const uf = String(cliente.uf || '').trim()
  return Boolean(cliente.logradouro && cliente.cidade && uf.length === 2 && cep.length === 8)
}

export default function NfeSaidaPage() {
  const toast = useToast()
  const navigate = useNavigate()
  const submittingRef = useRef(false)
  const [caixaOk, setCaixaOk] = useState(false)
  const [moduloFiscalAtivo, setModuloFiscalAtivo] = useState(false)
  const [q, setQ] = useState('')
  const [prodPage, setProdPage] = useState(1)
  const [produtos, setProdutos] = useState([])
  const [prodMeta, setProdMeta] = useState({ current_page: 1, last_page: 1, total: 0 })
  const [carrinho, setCarrinho] = useState([])
  const [cliente, setCliente] = useState(null)
  const [cliQ, setCliQ] = useState('')
  const [cliOpts, setCliOpts] = useState([])
  const [cliOpen, setCliOpen] = useState(false)
  const [documentoDest, setDocumentoDest] = useState('')
  const [formaPagamento, setFormaPagamento] = useState('pix')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    let cancelled = false
    getCaixaAtual()
      .then((r) => {
        if (cancelled) return
        if (!r.data) {
          toast.error('Abra o caixa antes de emitir a NF-e de saída.')
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

  function addProduto(produto) {
    setCarrinho((atual) => {
      const existente = atual.find((i) => i.produto_id === produto.id)
      if (existente) {
        return atual.map((i) =>
          i.produto_id === produto.id ? { ...i, quantidade: i.quantidade + 1 } : i,
        )
      }
      return [
        ...atual,
        {
          produto_id: produto.id,
          descricao: produto.descricao,
          codigo_barras: produto.codigo_barras,
          preco_venda: Number(produto.preco_venda),
          quantidade: 1,
        },
      ]
    })
  }

  function setQty(produtoId, quantidade) {
    const qtd = Math.max(0, Number(quantidade) || 0)
    setCarrinho((atual) => {
      if (qtd <= 0) return atual.filter((i) => i.produto_id !== produtoId)
      return atual.map((i) => (i.produto_id === produtoId ? { ...i, quantidade: qtd } : i))
    })
  }

  const total = useMemo(
    () => carrinho.reduce((acc, i) => acc + i.preco_venda * i.quantidade, 0),
    [carrinho],
  )

  const onBuscaProduto = useCallback((value) => {
    setProdPage(1)
    setQ(value)
  }, [])

  function escolherCliente(c) {
    setCliente(c)
    setDocumentoDest(maskCpfCnpj(c.documento || ''))
    setCliQ('')
    setCliOpen(false)
  }

  async function emitir() {
    if (submittingRef.current) return
    if (!moduloFiscalAtivo) {
      toast.error('Habilite o módulo fiscal em Configuração.')
      return
    }
    if (!cliente) {
      toast.error('Selecione o destinatário (cliente com endereço).')
      return
    }
    if (!enderecoCompleto(cliente)) {
      toast.error('Complete o endereço do cliente (logradouro, cidade, UF e CEP) para a NF-e.')
      return
    }
    if (carrinho.length === 0) {
      toast.error('Adicione ao menos um produto.')
      return
    }
    submittingRef.current = true
    setBusy(true)
    try {
      const res = await emitirNfeSaidaProdutos(
        {
          cliente_id: cliente.id,
          itens: carrinho.map((i) => ({
            produto_id: i.produto_id,
            quantidade: i.quantidade,
          })),
          forma_pagamento: formaPagamento,
          documento_destinatario: soDigitos(documentoDest) || undefined,
        },
        { idempotencyKey: newIdempotencyKey() },
      )
      const nota = res.data?.nota_nfe
      if (nota?.status === 'autorizada') {
        toast.success('NF-e de saída autorizada.')
        await abrirDanfe(nota.id)
        navigate('/notas')
        return
      }
      toast.error(nota?.mensagem_erro || res.message || 'NF-e não autorizada.')
      navigate('/notas')
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível emitir a NF-e.')
    } finally {
      submittingRef.current = false
      setBusy(false)
    }
  }

  if (!caixaOk) return <p className="text-[var(--muted)]">Verificando caixa…</p>

  return (
    <div data-testid="page-nfe-saida">
      <div className="page-head">
        <div>
          <h1>NF-e de saída</h1>
          <p>Escolha o destinatário e os produtos. A nota (modelo 55) vai para a Focus/SEFAZ.</p>
        </div>
        <Link to="/notas" className="btn btn-ghost">
          Ver notas
        </Link>
      </div>

      {!moduloFiscalAtivo ? (
        <section className="panel">
          <p className="hint m-0">
            O módulo fiscal está desligado.{' '}
            <Link to="/config">Abra Configuração</Link> e habilite para emitir NF-e.
          </p>
        </section>
      ) : null}

      <div className="pdv-grid">
        <section className="panel">
          {cliente ? (
            <div className="cli-chip">
              <div>
                <strong>{cliente.nome}</strong>
                <span className="hint">
                  {cliente.documento || 'sem documento'}
                  {enderecoCompleto(cliente)
                    ? ` · ${cliente.cidade}/${cliente.uf}`
                    : ' · endereço incompleto'}
                </span>
              </div>
              <button type="button" className="btn btn-ghost" style={{ padding: '7px 12px' }} onClick={() => setCliente(null)}>
                Trocar
              </button>
            </div>
          ) : (
            <div className="field" style={{ position: 'relative' }}>
              <label>Destinatário</label>
              <input
                value={cliQ}
                placeholder="Buscar cliente por nome ou documento…"
                onChange={(e) => {
                  setCliQ(e.target.value)
                  setCliOpen(true)
                }}
                onFocus={() => setCliOpen(true)}
                disabled={busy}
                data-testid="busca-destinatario"
              />
              {cliOpen ? (
                <div className="combo-list panel" style={{ position: 'absolute', zIndex: 5, left: 0, right: 0, marginTop: 4, padding: 8 }}>
                  {cliOpts.length === 0 ? <p className="hint m-0">Nenhum cliente encontrado.</p> : null}
                  {cliOpts.map((c) => (
                    <button
                      key={c.id}
                      type="button"
                      className="product-pick"
                      style={{ display: 'block', marginBottom: 4 }}
                      onClick={() => escolherCliente(c)}
                    >
                      <strong>{c.nome}</strong>
                      <span className="hint">{c.documento}</span>
                    </button>
                  ))}
                </div>
              ) : null}
            </div>
          )}

          {cliente && !enderecoCompleto(cliente) ? (
            <p className="hint">
              Complete o endereço em{' '}
              <Link to={`/clientes/${cliente.id}`}>cadastro do cliente</Link> antes de emitir.
            </p>
          ) : null}

          <SearchBar
            value={q}
            onChange={onBuscaProduto}
            placeholder="Produto: nome ou código de barras"
            testId="busca-produto-nfe"
          />
          <div className="product-pick" data-testid="lista-produtos-nfe">
            {produtos.length === 0 ? (
              <div className="empty-state">Nenhum produto encontrado.</div>
            ) : (
              produtos.map((p) => (
                <button
                  key={p.id}
                  type="button"
                  onClick={() => addProduto(p)}
                  disabled={busy}
                  data-testid={`add-produto-nfe-${p.id}`}
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
            <h2 className="m-0 text-base font-bold text-[var(--brand-primary)]">Itens da nota</h2>
            {carrinho.length === 0 ? (
              <div className="empty-state mt-3">
                <strong>Nenhum produto</strong>
                <br />
                <span className="hint">Clique em um produto acima para incluir na NF-e.</span>
              </div>
            ) : (
              <div className="cart-list mt-3" data-testid="carrinho-nfe">
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
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </section>

        <aside className="panel pdv-summary">
          <div className="field">
            <label>CPF/CNPJ do destinatário</label>
            <input
              value={documentoDest}
              onChange={(e) => setDocumentoDest(maskCpfCnpj(e.target.value))}
              disabled={busy}
              data-testid="documento-destinatario-nfe"
            />
          </div>
          <div className="field">
            <label>Forma de pagamento</label>
            <select
              value={formaPagamento}
              onChange={(e) => setFormaPagamento(e.target.value)}
              disabled={busy}
            >
              <option value="pix">Pix</option>
              <option value="dinheiro">Dinheiro</option>
              <option value="cartao_credito">Cartão crédito</option>
              <option value="cartao_debito">Cartão débito</option>
            </select>
          </div>
          <p className="hint">
            Total da nota: <strong>{money(total)}</strong>
          </p>
          <button
            type="button"
            className="btn btn-accent w-full"
            disabled={busy || !moduloFiscalAtivo || !cliente || carrinho.length === 0}
            onClick={emitir}
            data-testid="emitir-nfe-saida"
          >
            {busy ? 'Processando…' : 'Emitir NF-e de saída'}
          </button>
        </aside>
      </div>
    </div>
  )
}
