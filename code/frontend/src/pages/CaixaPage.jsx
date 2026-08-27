import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  abrirCaixa,
  fecharCaixa,
  getCaixaAtual,
  getFechamento,
  listVendasDoDia,
  registrarSangria,
} from '../api/pdv'
import { useToast } from '../context/ToastContext'

export default function CaixaPage() {
  const toast = useToast()
  const openingRef = useRef(false)
  const actionRef = useRef(false)
  const [caixa, setCaixa] = useState(null)
  const [vendas, setVendas] = useState([])
  const [fechamento, setFechamento] = useState(null)
  const [sangriaValor, setSangriaValor] = useState('')
  const [sangriaMotivo, setSangriaMotivo] = useState('')
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [action, setAction] = useState('')

  async function carregar() {
    setLoading(true)
    try {
      const [c, v, f] = await Promise.all([getCaixaAtual(), listVendasDoDia(), getFechamento()])
      setCaixa(c.data)
      setVendas(v.data || [])
      setFechamento(f.data)
    } catch {
      toast.error('Não foi possível carregar o caixa.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    carregar()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  async function onAbrir() {
    if (openingRef.current) return
    openingRef.current = true
    setSubmitting(true)
    try {
      await abrirCaixa(0)
      toast.success('Caixa aberto.')
      await carregar()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível abrir o caixa.')
    } finally {
      openingRef.current = false
      setSubmitting(false)
    }
  }

  async function onSangria(event) {
    event.preventDefault()
    if (actionRef.current) return
    if (Number(sangriaValor) <= 0 || !sangriaMotivo.trim()) {
      toast.error('Informe valor e motivo da sangria.')
      return
    }
    actionRef.current = true
    setAction('sangria')
    try {
      await registrarSangria({ valor: Number(sangriaValor), motivo: sangriaMotivo })
      toast.success('Sangria registrada.')
      setSangriaValor('')
      setSangriaMotivo('')
      await carregar()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível registrar a sangria.')
    } finally {
      actionRef.current = false
      setAction('')
    }
  }

  async function onFechar() {
    if (actionRef.current || !window.confirm('Fechar o caixa atual?')) return
    actionRef.current = true
    setAction('fechar')
    try {
      await fecharCaixa()
      toast.success('Caixa fechado.')
      await carregar()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível fechar o caixa.')
    } finally {
      actionRef.current = false
      setAction('')
    }
  }

  if (loading) return <p className="text-[var(--muted)]">Carregando caixa…</p>

  return (
    <div data-testid="page-caixa">
      <h1 className="m-0 text-2xl font-extrabold text-[var(--brand-primary)]">Caixa</h1>
      <p className="mt-1 text-[var(--muted)]">Abertura, sangria, vendas e fechamento.</p>

      <section className="mt-6 rounded-[10px] border border-[var(--line)] bg-[var(--surface)] p-4">
        {caixa ? (
          <>
            <p className="m-0 font-semibold text-[var(--brand-primary)]" data-testid="caixa-status">
              Caixa aberto desde {new Date(caixa.aberto_em).toLocaleString('pt-BR')}
            </p>
            <form className="mt-4 grid gap-2 md:grid-cols-[160px_1fr_auto]" onSubmit={onSangria}>
              <input
                className="rounded-lg border border-[var(--line)] px-3 py-2"
                type="number"
                min="0.01"
                step="0.01"
                placeholder="Valor da sangria"
                value={sangriaValor}
                onChange={(e) => setSangriaValor(e.target.value)}
                data-testid="sangria-valor"
              />
              <input
                className="rounded-lg border border-[var(--line)] px-3 py-2"
                placeholder="Motivo"
                value={sangriaMotivo}
                onChange={(e) => setSangriaMotivo(e.target.value)}
              />
              <button
                className="rounded-lg border border-[var(--brand-primary)] px-4 py-2 font-bold disabled:opacity-60"
                disabled={Boolean(action)}
                data-testid="sangria-salvar"
              >
                {action === 'sangria' ? 'Processando…' : 'Registrar sangria'}
              </button>
            </form>
            <div className="mt-3 flex flex-wrap gap-2">
              <Link
                to="/pdv"
                className="inline-block rounded-lg bg-[var(--brand-accent)] px-4 py-2 font-bold text-[var(--brand-primary)]"
                data-testid="ir-pdv"
              >
                Ir para PDV
              </Link>
              <button
                type="button"
                className="rounded-lg bg-[var(--brand-primary)] px-4 py-2 font-bold text-white disabled:opacity-60"
                disabled={Boolean(action)}
                onClick={onFechar}
                data-testid="fechar-caixa"
              >
                {action === 'fechar' ? 'Processando…' : 'Fechar caixa'}
              </button>
            </div>
          </>
        ) : (
          <>
            <p className="m-0 text-[var(--muted)]" data-testid="caixa-status">
              Nenhum caixa aberto.
            </p>
            <button
              type="button"
              className="mt-3 rounded-lg bg-[var(--brand-primary)] px-4 py-2 font-bold text-white disabled:opacity-60"
              disabled={submitting}
              onClick={onAbrir}
              data-testid="abrir-caixa"
            >
              {submitting ? 'Processando…' : 'Abrir caixa'}
            </button>
          </>
        )}
      </section>

      <section className="mt-6">
        <h2 className="text-lg font-extrabold text-[var(--brand-primary)]">Vendas do dia</h2>
        {vendas.length === 0 ? (
          <p className="text-[var(--muted)]">Nenhuma venda ainda.</p>
        ) : (
          <ul className="mt-2 space-y-2" data-testid="vendas-dia">
            {vendas.map((v) => (
              <li key={v.id} className="rounded-lg border border-[var(--line)] bg-white px-3 py-2 text-sm">
                #{v.id} · R$ {Number(v.total).toFixed(2)} · {v.cliente?.nome || 'Consumidor'} · NFC-e{' '}
                {v.nota_nfce?.status || '—'}
              </li>
            ))}
          </ul>
        )}
      </section>

      {fechamento ? (
        <section className="mt-6 rounded-[10px] border border-[var(--line)] bg-white p-4">
          <div id="fechamento-print" className="print-area">
            <h2 className="m-0 text-lg font-extrabold text-[var(--brand-primary)]">
              {fechamento.preview ? 'Prévia do fechamento' : 'Último fechamento'}
            </h2>
            <p className="mb-0 text-sm">Total de vendas: R$ {Number(fechamento.total_vendas).toFixed(2)}</p>
            <p className="my-1 text-sm">Total de sangrias: R$ {Number(fechamento.total_sangrias).toFixed(2)}</p>
            <p className="mt-0 text-sm font-bold">Dinheiro esperado: R$ {Number(fechamento.total_dinheiro_esperado).toFixed(2)}</p>
          </div>
          <button
            type="button"
            className="mt-3 rounded-lg border border-[var(--brand-primary)] px-4 py-2 font-bold"
            onClick={() => window.print()}
            data-testid="imprimir-fechamento"
          >
            Imprimir fechamento
          </button>
        </section>
      ) : null}
    </div>
  )
}
