import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { listProdutos } from '../api/dominio'
import { finalizarVenda, getCaixaAtual } from '../api/pdv'
import { useToast } from '../context/ToastContext'

function money(n) {
  return Number(n || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

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
  const [produtos, setProdutos] = useState([])
  const [carrinho, setCarrinho] = useState([])
  const [descontoTipo, setDescontoTipo] = useState('nenhum')
  const [descontoValor, setDescontoValor] = useState('')
  const [formaPagamento, setFormaPagamento] = useState('dinheiro')
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
      listProdutos({ q: q || undefined, ativo: true })
        .then((r) => setProdutos(r.data || []))
        .catch(() => toast.error('Falha ao buscar produtos.'))
    }, 200)
    return () => window.clearTimeout(t)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q, caixaOk])

  const subtotal = useMemo(
    () => carrinho.reduce((acc, i) => acc + Number(i.preco_venda) * i.quantidade, 0),
    [carrinho],
  )

  const total = useMemo(() => {
    let d = 0
    const dv = Number(descontoValor || 0)
    if (descontoTipo === 'percentual') d = (subtotal * dv) / 100
    if (descontoTipo === 'valor') d = dv
    return Math.max(0, subtotal - d)
  }, [subtotal, descontoTipo, descontoValor])

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
    const qtd = Number(quantidade)
    if (!qtd || qtd <= 0) {
      setCarrinho((prev) => prev.filter((i) => i.produto_id !== produtoId))
      return
    }
    setCarrinho((prev) => prev.map((i) => (i.produto_id === produtoId ? { ...i, quantidade: qtd } : i)))
  }

  async function onPagar() {
    if (submittingRef.current) return
    if (carrinho.length === 0) {
      toast.error('Adicione produtos ao carrinho.')
      return
    }
    submittingRef.current = true
    setSubmitting(true)
    try {
      const payload = {
        itens: carrinho.map((i) => ({
          produto_id: i.produto_id,
          quantidade: i.quantidade,
        })),
        desconto_tipo: descontoTipo,
        desconto_valor: Number(descontoValor || 0),
        forma_pagamento: formaPagamento,
      }
      const res = await finalizarVenda(payload, { idempotencyKey: newIdempotencyKey() })
      toast.success(`Venda #${res.data.id} finalizada. NFC-e ${res.data.nota_nfce?.status}.`)
      setCarrinho([])
      setDescontoTipo('nenhum')
      setDescontoValor('')
      navigate('/caixa')
      // sucesso com redirect: mantém bloqueado
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      toast.error(err.response?.data?.message || 'Não foi possível finalizar a venda.')
    }
  }

  if (!caixaOk) return <p className="text-[var(--muted)]">Verificando caixa…</p>

  return (
    <div className="grid gap-6 lg:grid-cols-2" data-testid="page-pdv">
      <section>
        <h1 className="m-0 text-2xl font-extrabold text-[var(--brand-primary)]">PDV</h1>
        <p className="mt-1 text-[var(--muted)]">Buscar produto / código (T4–T5).</p>
        <input
          className="mt-3 w-full rounded-lg border border-[var(--line)] px-3 py-2"
          placeholder="Buscar descrição ou código de barras"
          value={q}
          onChange={(e) => setQ(e.target.value)}
          data-testid="busca-produto"
        />
        <ul className="mt-3 max-h-80 space-y-2 overflow-auto" data-testid="lista-produtos">
          {produtos.map((p) => (
            <li key={p.id}>
              <button
                type="button"
                className="flex w-full items-center justify-between rounded-lg border border-[var(--line)] bg-white px-3 py-2 text-left hover:border-[var(--brand-primary)]"
                onClick={() => addProduto(p)}
                data-testid={`add-produto-${p.id}`}
              >
                <span>
                  <strong>{p.descricao}</strong>
                  <br />
                  <span className="text-xs text-[var(--muted)]">{p.codigo_barras}</span>
                </span>
                <span className="font-bold text-[var(--brand-primary)]">{money(p.preco_venda)}</span>
              </button>
            </li>
          ))}
        </ul>
      </section>

      <section className="rounded-[10px] border border-[var(--line)] bg-[var(--surface)] p-4">
        <h2 className="m-0 text-lg font-extrabold text-[var(--brand-primary)]">Carrinho</h2>
        {carrinho.length === 0 ? (
          <p className="mt-2 text-[var(--muted)]">Vazio.</p>
        ) : (
          <ul className="mt-3 space-y-2" data-testid="carrinho">
            {carrinho.map((i) => (
              <li key={i.produto_id} className="flex items-center justify-between gap-2 text-sm">
                <span className="flex-1">{i.descricao}</span>
                <input
                  className="w-16 rounded border border-[var(--line)] px-2 py-1"
                  type="number"
                  min="1"
                  step="1"
                  value={i.quantidade}
                  onChange={(e) => setQty(i.produto_id, e.target.value)}
                />
                <span className="w-24 text-right font-semibold">{money(i.preco_venda * i.quantidade)}</span>
              </li>
            ))}
          </ul>
        )}

        <div className="mt-4 grid gap-2 border-t border-[var(--line)] pt-4 text-sm">
          <label className="flex items-center justify-between gap-2">
            <span>Desconto</span>
            <select
              className="rounded border border-[var(--line)] px-2 py-1"
              value={descontoTipo}
              onChange={(e) => setDescontoTipo(e.target.value)}
              data-testid="desconto-tipo"
            >
              <option value="nenhum">Nenhum</option>
              <option value="percentual">%</option>
              <option value="valor">R$</option>
            </select>
          </label>
          {descontoTipo !== 'nenhum' ? (
            <input
              className="rounded border border-[var(--line)] px-2 py-1"
              type="number"
              min="0"
              step="0.01"
              value={descontoValor}
              onChange={(e) => setDescontoValor(e.target.value)}
              data-testid="desconto-valor"
            />
          ) : null}
          <label className="flex items-center justify-between gap-2">
            <span>Pagamento</span>
            <select
              className="rounded border border-[var(--line)] px-2 py-1"
              value={formaPagamento}
              onChange={(e) => setFormaPagamento(e.target.value)}
              data-testid="forma-pagamento"
            >
              <option value="dinheiro">Dinheiro</option>
              <option value="pix">PIX</option>
              <option value="cartao">Cartão</option>
            </select>
          </label>
          <p className="m-0 flex justify-between font-bold text-[var(--brand-primary)]">
            <span>Total</span>
            <span data-testid="total-venda">{money(total)}</span>
          </p>
        </div>

        <button
          type="button"
          className="mt-4 w-full rounded-lg bg-[var(--brand-primary)] px-4 py-3 font-bold text-white disabled:opacity-60"
          disabled={submitting || carrinho.length === 0}
          onClick={onPagar}
          data-testid="pagar-emitir"
        >
          {submitting ? 'Processando…' : 'Pagar e emitir nota'}
        </button>
      </section>
    </div>
  )
}
