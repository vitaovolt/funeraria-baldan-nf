import { useCallback, useEffect, useRef, useState } from 'react'
import { getConfiguracaoFiscal } from '../api/dominio'
import { emitirNfeVenda, listNotasNfce, listVendas, reemitirNotaNfce } from '../api/pdv'
import { Pagination, SearchBar } from '../components/ListToolbar'
import { usePagedList } from '../hooks/usePagedList'
import { useToast } from '../context/ToastContext'
import { money } from '../utils/format'
import { abrirDanfe, abrirHtmlImpressao, maskCpfCnpj, soDigitos } from '../utils/impressao'

export default function NotasPage() {
  const toast = useToast()
  const submittingRef = useRef(false)
  const [busy, setBusy] = useState('')
  const [modalNfe, setModalNfe] = useState(false)
  const [buscaVenda, setBuscaVenda] = useState('')
  const [vendasOpts, setVendasOpts] = useState([])
  const [vendaSelecionada, setVendaSelecionada] = useState(null)
  const [documentoDest, setDocumentoDest] = useState('')
  const [moduloFiscalAtivo, setModuloFiscalAtivo] = useState(false)
  const fetcher = useCallback((params) => listNotasNfce(params), [])
  const { q, setQ, setPage, items, meta, reload } = usePagedList(fetcher)

  useEffect(() => {
    getConfiguracaoFiscal()
      .then((r) => setModuloFiscalAtivo(Boolean(r.data?.modulo_fiscal_ativo)))
      .catch(() => setModuloFiscalAtivo(false))
  }, [])

  useEffect(() => {
    if (!modalNfe) return undefined
    const t = window.setTimeout(() => {
      listVendas({ q: buscaVenda || undefined, page: 1, per_page: 10 })
        .then((r) => setVendasOpts(r.data || []))
        .catch(() => setVendasOpts([]))
    }, 200)
    return () => window.clearTimeout(t)
  }, [buscaVenda, modalNfe])

  function abrirModalNfe() {
    setBuscaVenda('')
    setVendaSelecionada(null)
    setDocumentoDest('')
    setVendasOpts([])
    setModalNfe(true)
  }

  function escolherVenda(venda) {
    setVendaSelecionada(venda)
    setDocumentoDest(maskCpfCnpj(venda.cliente?.documento || ''))
  }

  async function emitirNfeSaida() {
    if (submittingRef.current || !vendaSelecionada) return
    if (!vendaSelecionada.cliente_id) {
      toast.error('A venda precisa ter cliente cadastrado (com endereço) para NF-e.')
      return
    }
    submittingRef.current = true
    setBusy('emitir-nfe')
    try {
      const res = await emitirNfeVenda(vendaSelecionada.id, {
        documento_destinatario: soDigitos(documentoDest) || undefined,
      })
      const nota = res.data?.nota_nfe
      if (nota?.status === 'autorizada') {
        toast.success('NF-e de saída autorizada.')
        await abrirDanfe(nota.id)
      } else {
        toast.error(nota?.mensagem_erro || res.message || 'NF-e não autorizada.')
      }
      setModalNfe(false)
      await reload()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível emitir a NF-e.')
    } finally {
      submittingRef.current = false
      setBusy('')
    }
  }

  async function agir(nota, acao) {
    if (submittingRef.current) return
    submittingRef.current = true
    setBusy(`${acao}-${nota.id}`)
    try {
      if (acao === 'danfe') {
        await abrirDanfe(nota.id)
      } else if (acao === 'comprovante') {
        await abrirHtmlImpressao(`/vendas/${nota.venda_id}/comprovante`)
      } else if (acao === 'xml') {
        const { default: client } = await import('../api/client')
        const res = await client.get(`/notas-nfce/${nota.id}/xml`, { responseType: 'blob' })
        const url = URL.createObjectURL(res.data)
        const a = document.createElement('a')
        a.href = url
        a.download = `${nota.tipo || 'nfce'}-${nota.id}.xml`
        a.click()
        URL.revokeObjectURL(url)
      } else if (acao === 'reemitir') {
        const res = await reemitirNotaNfce(nota.id)
        const atual = res.data
        const rotulo = atual.tipo === 'nfe' ? 'NF-e' : 'NFC-e'
        if (atual.status === 'autorizada') {
          toast.success(`${rotulo} autorizada.`)
          await abrirDanfe(atual.id)
        } else {
          toast.error(atual.mensagem_erro || `${rotulo} não autorizada.`)
        }
        await reload()
      }
    } catch (err) {
      toast.error(err.response?.data?.message || err.message || 'Não foi possível concluir.')
    } finally {
      submittingRef.current = false
      setBusy('')
    }
  }

  return (
    <div data-testid="page-notas">
      <div className="page-head">
        <div>
          <h1>Notas fiscais</h1>
          <p>NFC-e da venda e NF-e de saída. Reimprima o DANFE ou retente se a SEFAZ recusou.</p>
        </div>
        {moduloFiscalAtivo ? (
          <button type="button" className="btn btn-accent" onClick={abrirModalNfe} data-testid="nova-nfe-saida">
            Gerar NF-e de saída
          </button>
        ) : null}
      </div>

      <section className="panel">
        <SearchBar value={q} onChange={setQ} placeholder="Buscar por nº, venda, status ou chave" />
        <div className="table-wrap">
          <table className="data">
            <thead>
              <tr>
                <th>Tipo</th>
                <th>Nota</th>
                <th>Venda</th>
                <th>Cliente</th>
                <th>Status</th>
                <th>Mensagem</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              {items.length === 0 ? (
                <tr>
                  <td colSpan={7} className="hint">
                    Nenhuma nota encontrada.
                  </td>
                </tr>
              ) : (
                items.map((n) => (
                  <tr key={n.id}>
                    <td>
                      <strong>{n.tipo === 'nfe' ? 'NF-e' : 'NFC-e'}</strong>
                    </td>
                    <td>#{n.numero || n.id}</td>
                    <td>#{n.venda_id}</td>
                    <td>{n.venda?.cliente?.nome || 'Consumidor'}</td>
                    <td>
                      <strong>{n.status}</strong>
                    </td>
                    <td className="hint">{n.mensagem_erro || (n.chave ? `${n.chave.slice(0, 16)}…` : '—')}</td>
                    <td className="actions">
                      <button
                        type="button"
                        className="btn btn-ghost"
                        style={{ padding: '6px 10px', fontSize: '0.8rem' }}
                        disabled={Boolean(busy)}
                        onClick={() => agir(n, n.status === 'autorizada' ? 'danfe' : 'comprovante')}
                      >
                        {busy === `danfe-${n.id}` || busy === `comprovante-${n.id}`
                          ? 'Processando…'
                          : n.status === 'autorizada'
                            ? 'DANFE'
                            : 'Comprovante'}
                      </button>{' '}
                      {n.status === 'autorizada' ? (
                        <button
                          type="button"
                          className="btn btn-ghost"
                          style={{ padding: '6px 10px', fontSize: '0.8rem' }}
                          disabled={Boolean(busy)}
                          onClick={() => agir(n, 'xml')}
                        >
                          XML
                        </button>
                      ) : (
                        <button
                          type="button"
                          className="btn btn-primary"
                          style={{ padding: '6px 10px', fontSize: '0.8rem' }}
                          disabled={Boolean(busy)}
                          onClick={() => agir(n, 'reemitir')}
                          data-testid="reemitir-nfce"
                        >
                          {busy === `reemitir-${n.id}` ? 'Processando…' : 'Tentar de novo'}
                        </button>
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
        <Pagination meta={meta} onPageChange={setPage} />
      </section>

      {modalNfe ? (
        <div className="modal-backdrop" role="presentation" onMouseDown={() => !busy && setModalNfe(false)}>
          <div
            className="modal-card panel"
            role="dialog"
            data-testid="modal-nfe-saida"
            onMouseDown={(e) => e.stopPropagation()}
          >
            <h2>Gerar NF-e de saída</h2>
            <p className="hint">
              Selecione uma venda com cliente e endereço completo. A NF-e (modelo 55) é enviada à Focus/SEFAZ.
            </p>
            <div className="field">
              <label>Buscar venda</label>
              <input
                value={buscaVenda}
                onChange={(e) => setBuscaVenda(e.target.value)}
                placeholder="Nº da venda ou nome do cliente"
                disabled={Boolean(busy)}
              />
            </div>
            <div className="table-wrap mb-3" style={{ maxHeight: 220, overflow: 'auto' }}>
              <table className="data" style={{ minWidth: 0 }}>
                <thead>
                  <tr>
                    <th>Venda</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {vendasOpts.length === 0 ? (
                    <tr>
                      <td colSpan={4} className="hint">
                        Nenhuma venda encontrada.
                      </td>
                    </tr>
                  ) : (
                    vendasOpts.map((v) => (
                      <tr key={v.id} className={vendaSelecionada?.id === v.id ? 'on' : ''}>
                        <td>#{v.id}</td>
                        <td>{v.cliente?.nome || '—'}</td>
                        <td>{money(v.total)}</td>
                        <td>
                          <button
                            type="button"
                            className="btn btn-ghost"
                            style={{ padding: '4px 10px', fontSize: '0.75rem' }}
                            disabled={Boolean(busy) || Boolean(v.nota_nfe?.status === 'autorizada')}
                            onClick={() => escolherVenda(v)}
                          >
                            {v.nota_nfe?.status === 'autorizada' ? 'Já tem NF-e' : 'Usar'}
                          </button>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
            {vendaSelecionada ? (
              <>
                <p className="hint m-0 mb-2">
                  Venda #{vendaSelecionada.id} · {vendaSelecionada.cliente?.nome || 'sem cliente'}
                </p>
                <div className="field">
                  <label>CPF/CNPJ do destinatário (opcional se já estiver no cliente)</label>
                  <input
                    value={documentoDest}
                    onChange={(e) => setDocumentoDest(maskCpfCnpj(e.target.value))}
                    disabled={Boolean(busy)}
                  />
                </div>
              </>
            ) : null}
            <div className="modal-actions">
              <button
                type="button"
                className="btn btn-accent w-full"
                disabled={Boolean(busy) || !vendaSelecionada}
                onClick={emitirNfeSaida}
                data-testid="confirmar-nfe-saida"
              >
                {busy === 'emitir-nfe' ? 'Processando…' : 'Emitir NF-e'}
              </button>
              <button type="button" className="btn btn-ghost w-full" disabled={Boolean(busy)} onClick={() => setModalNfe(false)}>
                Cancelar
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  )
}
