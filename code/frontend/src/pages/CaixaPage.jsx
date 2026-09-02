import { useCallback, useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  abrirCaixa,
  fecharCaixa,
  getCaixaAtual,
  getFechamento,
  listVendasDoDia,
  registrarSangria,
  registrarSuprimento,
} from '../api/pdv'
import { Pagination, SearchBar } from '../components/ListToolbar'
import MoneyInput from '../components/MoneyInput'
import { useToast } from '../context/ToastContext'
import { money, metaFromResponse } from '../utils/format'
import { abrirHtmlImpressao } from '../utils/impressao'
import { parseBrl } from '../utils/moneyMask'

export default function CaixaPage() {
  const toast = useToast()
  const openingRef = useRef(false)
  const actionRef = useRef(false)
  const [caixa, setCaixa] = useState(null)
  const [vendas, setVendas] = useState([])
  const [vendasMeta, setVendasMeta] = useState({ current_page: 1, last_page: 1, total: 0 })
  const [vendasQ, setVendasQ] = useState('')
  const [vendasPage, setVendasPage] = useState(1)
  const [fechamento, setFechamento] = useState(null)
  const [sangriaValor, setSangriaValor] = useState('')
  const [sangriaMotivo, setSangriaMotivo] = useState('')
  const [suprimentoValor, setSuprimentoValor] = useState('')
  const [suprimentoMotivo, setSuprimentoMotivo] = useState('')
  const [valorAbertura, setValorAbertura] = useState('')
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [action, setAction] = useState('')

  const carregarVendas = useCallback(async () => {
    try {
      const v = await listVendasDoDia({
        page: vendasPage,
        per_page: 15,
        ...(vendasQ.trim() ? { q: vendasQ.trim() } : {}),
      })
      setVendas(v.data || [])
      setVendasMeta(metaFromResponse(v, (v.data || []).length))
    } catch {
      toast.error('Não foi possível carregar as vendas do dia.')
    }
  }, [vendasPage, vendasQ, toast])

  async function carregar() {
    setLoading(true)
    try {
      const [c, f] = await Promise.all([getCaixaAtual(), getFechamento()])
      setCaixa(c.data)
      setFechamento(f.data)
      await carregarVendas()
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

  useEffect(() => {
    if (loading) return undefined
    const t = window.setTimeout(carregarVendas, 200)
    return () => window.clearTimeout(t)
  }, [carregarVendas, loading])

  async function onAbrir() {
    if (openingRef.current) return
    openingRef.current = true
    setSubmitting(true)
    try {
      await abrirCaixa(parseBrl(valorAbertura) || 0)
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
    if (parseBrl(sangriaValor) == null || parseBrl(sangriaValor) <= 0 || !sangriaMotivo.trim()) {
      toast.error('Informe valor e motivo da sangria.')
      return
    }
    actionRef.current = true
    setAction('sangria')
    try {
      await registrarSangria({ valor: parseBrl(sangriaValor), motivo: sangriaMotivo })
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

  async function onSuprimento(event) {
    event.preventDefault()
    if (actionRef.current) return
    if (parseBrl(suprimentoValor) == null || parseBrl(suprimentoValor) <= 0 || !suprimentoMotivo.trim()) {
      toast.error('Informe valor e motivo do suprimento.')
      return
    }
    actionRef.current = true
    setAction('suprimento')
    try {
      await registrarSuprimento({ valor: parseBrl(suprimentoValor), motivo: suprimentoMotivo })
      toast.success('Suprimento registrado.')
      setSuprimentoValor('')
      setSuprimentoMotivo('')
      await carregar()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível registrar o suprimento.')
    } finally {
      actionRef.current = false
      setAction('')
    }
  }

  async function onImprimirFechamento() {
    try {
      await abrirHtmlImpressao('/caixa/fechamento/impressao')
    } catch (err) {
      toast.error(err.message || 'Não foi possível imprimir o fechamento.')
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

  if (loading) return <p className="text-[var(--brand-muted)]">Carregando caixa…</p>

  return (
    <div data-testid="page-caixa">
      <div className="page-head">
        <div>
          <h1>Caixa</h1>
          <p>Abra o caixa para vender. Sangria e fechamento ficam aqui — não na tela de venda.</p>
        </div>
        <span className={`pill ${caixa ? 'ok' : 'danger'}`}>{caixa ? 'Aberto' : 'Fechado'}</span>
      </div>

      <section className="panel">
        {caixa ? (
          <>
            <p className="m-0 font-semibold text-[var(--brand-primary)]" data-testid="caixa-status">
              Caixa aberto desde {new Date(caixa.aberto_em).toLocaleString('pt-BR')}
            </p>
            <form className="mt-4 grid gap-2 md:grid-cols-[160px_1fr_auto]" onSubmit={onSangria}>
              <div className="field" style={{ margin: 0 }}>
                <label>Valor</label>
                <MoneyInput
                  value={sangriaValor}
                  onChange={setSangriaValor}
                  placeholder="0,00"
                  data-testid="sangria-valor"
                />
              </div>
              <div className="field" style={{ margin: 0 }}>
                <label>Motivo</label>
                <input
                  placeholder="Motivo"
                  value={sangriaMotivo}
                  onChange={(e) => setSangriaMotivo(e.target.value)}
                />
              </div>
              <button className="btn btn-ghost self-end" disabled={Boolean(action)} data-testid="sangria-salvar">
                {action === 'sangria' ? 'Processando…' : 'Registrar sangria'}
              </button>
            </form>
            <form className="mt-3 grid gap-2 md:grid-cols-[160px_1fr_auto]" onSubmit={onSuprimento}>
              <div className="field" style={{ margin: 0 }}>
                <label>Suprimento</label>
                <MoneyInput
                  value={suprimentoValor}
                  onChange={setSuprimentoValor}
                  placeholder="0,00"
                  data-testid="suprimento-valor"
                />
              </div>
              <div className="field" style={{ margin: 0 }}>
                <label>Motivo</label>
                <input
                  placeholder="Entrada de dinheiro"
                  value={suprimentoMotivo}
                  onChange={(e) => setSuprimentoMotivo(e.target.value)}
                />
              </div>
              <button className="btn btn-ghost self-end" disabled={Boolean(action)} data-testid="suprimento-salvar">
                {action === 'suprimento' ? 'Processando…' : 'Registrar suprimento'}
              </button>
            </form>
            <div className="mt-3 flex flex-wrap gap-2">
              <Link to="/pdv" className="btn btn-accent" data-testid="ir-pdv">
                Ir para PDV
              </Link>
              <button
                type="button"
                className="btn btn-primary"
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
            <p className="m-0 text-[var(--brand-muted)]" data-testid="caixa-status">
              Nenhum caixa aberto.
            </p>
            <div className="field mt-3" style={{ maxWidth: 200 }}>
              <label htmlFor="fundo-caixa">Fundo de abertura</label>
              <MoneyInput
                id="fundo-caixa"
                value={valorAbertura}
                onChange={setValorAbertura}
                placeholder="0,00"
                data-testid="valor-abertura"
              />
            </div>
            <button
              type="button"
              className="btn btn-primary mt-3"
              disabled={submitting}
              onClick={onAbrir}
              data-testid="abrir-caixa"
            >
              {submitting ? 'Processando…' : 'Abrir caixa'}
            </button>
          </>
        )}
      </section>

      <section className="panel mt-4">
        <h2 className="m-0 text-lg font-bold text-[var(--brand-primary)]">Vendas do dia</h2>
        <SearchBar
          value={vendasQ}
          onChange={(v) => {
            setVendasPage(1)
            setVendasQ(v)
          }}
          placeholder="Buscar venda ou cliente"
        />
        {vendas.length === 0 ? (
          <p className="text-[var(--brand-muted)]">Nenhuma venda ainda.</p>
        ) : (
          <div className="table-wrap" data-testid="vendas-dia">
            <table className="data">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Cliente</th>
                  <th>Total</th>
                  <th>NFC-e</th>
                </tr>
              </thead>
              <tbody>
                {vendas.map((v) => (
                  <tr key={v.id}>
                    <td>#{v.id}</td>
                    <td>{v.cliente?.nome || 'Consumidor'}</td>
                    <td>{money(v.total)}</td>
                    <td>{v.nota_nfce?.status || '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <Pagination meta={vendasMeta} onPageChange={setVendasPage} />
      </section>

      {fechamento ? (
        <section className="panel mt-4">
          <div id="fechamento-print" className="print-area">
            <h2 className="m-0 text-lg font-bold text-[var(--brand-primary)]">
              {fechamento.preview ? 'Prévia do fechamento' : 'Último fechamento'}
            </h2>
            <p className="mb-0 text-sm">Fundo: {money(fechamento.valor_abertura)}</p>
            <p className="my-1 text-sm">Total de vendas: {money(fechamento.total_vendas)}</p>
            <p className="my-1 text-sm">
              Dinheiro {money(fechamento.totais_forma?.dinheiro)} · PIX {money(fechamento.totais_forma?.pix)} ·
              Crédito {money(fechamento.totais_forma?.cartao_credito)} · Débito{' '}
              {money(fechamento.totais_forma?.cartao_debito)}
            </p>
            <p className="my-1 text-sm">Suprimentos: {money(fechamento.total_suprimentos)}</p>
            <p className="my-1 text-sm">Sangrias: {money(fechamento.total_sangrias)}</p>
            <p className="mt-0 text-sm font-bold">
              Dinheiro esperado: {money(fechamento.total_dinheiro_esperado)}
            </p>
          </div>
          <button type="button" className="btn btn-ghost mt-3" onClick={onImprimirFechamento} data-testid="imprimir-fechamento">
            Imprimir fechamento
          </button>
        </section>
      ) : null}
    </div>
  )
}
