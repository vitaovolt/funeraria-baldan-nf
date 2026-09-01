import { useCallback, useRef, useState } from 'react'
import { listNotasNfce, reemitirNotaNfce } from '../api/pdv'
import { Pagination, SearchBar } from '../components/ListToolbar'
import { usePagedList } from '../hooks/usePagedList'
import { useToast } from '../context/ToastContext'
import { abrirDanfe, abrirHtmlImpressao } from '../utils/impressao'

export default function NotasPage() {
  const toast = useToast()
  const submittingRef = useRef(false)
  const [busy, setBusy] = useState('')
  const fetcher = useCallback((params) => listNotasNfce(params), [])
  const { q, setQ, setPage, items, meta, reload } = usePagedList(fetcher)

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
        a.download = `nfce-${nota.id}.xml`
        a.click()
        URL.revokeObjectURL(url)
      } else if (acao === 'reemitir') {
        const res = await reemitirNotaNfce(nota.id)
        const atual = res.data
        if (atual.status === 'autorizada') {
          toast.success('NFC-e autorizada.')
          await abrirDanfe(atual.id)
        } else {
          toast.error(atual.mensagem_erro || 'NFC-e não autorizada.')
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
          <h1>Notas NFC-e</h1>
          <p>Documentos da venda. Reimprima o DANFE ou retente se a SEFAZ recusou.</p>
        </div>
      </div>

      <section className="panel">
        <SearchBar value={q} onChange={setQ} placeholder="Buscar por nº, venda, status ou chave" />
        <div className="table-wrap">
          <table className="data">
            <thead>
              <tr>
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
                  <td colSpan={6} className="hint">
                    Nenhuma nota encontrada.
                  </td>
                </tr>
              ) : (
                items.map((n) => (
                  <tr key={n.id}>
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
    </div>
  )
}
