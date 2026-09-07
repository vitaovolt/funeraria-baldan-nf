import { useCallback, useMemo, useState } from 'react'
import { Navigate } from 'react-router-dom'
import { listLogsAcoes } from '../api/logs'
import { Pagination, SearchBar } from '../components/ListToolbar'
import { useAuth } from '../context/AuthContext'
import { usePagedList } from '../hooks/usePagedList'
import { formatDateTime } from '../utils/format'

const ACOES = [
  { value: '', label: 'Todas as ações' },
  { value: 'auth.login', label: 'Login' },
  { value: 'auth.login_falhou', label: 'Login falhou' },
  { value: 'auth.logout', label: 'Logout' },
  { value: 'caixa.abrir', label: 'Abriu o caixa' },
  { value: 'caixa.fechar', label: 'Fechou o caixa' },
  { value: 'caixa.sangria', label: 'Sangria' },
  { value: 'caixa.suprimento', label: 'Suprimento' },
  { value: 'venda.finalizar', label: 'Finalizou venda' },
  { value: 'venda.emitir_nfce', label: 'Emitiu NFC-e' },
  { value: 'venda.emitir_nfe', label: 'Emitiu NF-e' },
  { value: 'nota.reemitir', label: 'Reemitiu nota' },
  { value: 'produto.criar', label: 'Criou produto' },
  { value: 'produto.atualizar', label: 'Atualizou produto' },
  { value: 'produto.excluir', label: 'Excluiu produto' },
  { value: 'cliente.criar', label: 'Criou cliente' },
  { value: 'cliente.atualizar', label: 'Atualizou cliente' },
  { value: 'cliente.excluir', label: 'Excluiu cliente' },
  { value: 'usuario.criar', label: 'Criou usuário' },
  { value: 'usuario.atualizar', label: 'Atualizou usuário' },
  { value: 'usuario.excluir', label: 'Excluiu usuário' },
  { value: 'estoque.movimentar', label: 'Movimentou estoque' },
  { value: 'consignado.criar', label: 'Criou consignado' },
  { value: 'consignado.devolver', label: 'Devolveu consignado' },
  { value: 'consignado.converter', label: 'Converteu consignado' },
  { value: 'config.atualizar', label: 'Atualizou configuração' },
  { value: 'config.certificado', label: 'Enviou certificado' },
]

export default function LogsPage() {
  const { user } = useAuth()
  const isAdmin = user?.role === 'admin'
  const [acao, setAcao] = useState('')
  const [de, setDe] = useState('')
  const [ate, setAte] = useState('')

  const extraParams = useMemo(() => {
    const extra = {}
    if (acao) extra.acao = acao
    if (de) extra.de = de
    if (ate) extra.ate = ate
    return extra
  }, [acao, de, ate])

  const fetcher = useCallback((params) => listLogsAcoes(params), [])
  const { q, setQ, setPage, items, meta, loading } = usePagedList(fetcher, { extraParams })

  if (!isAdmin) {
    return <Navigate to="/" replace />
  }

  return (
    <div data-testid="page-logs">
      <div className="page-head">
        <div>
          <h1>Logs</h1>
          <p>Histórico de ações na plataforma: quem fez, o quê e quando.</p>
        </div>
      </div>

      <section className="panel">
        <SearchBar value={q} onChange={setQ} placeholder="Buscar usuário ou descrição" testId="busca-log" />
        <div className="filters-row" style={{ display: 'flex', gap: 12, flexWrap: 'wrap', margin: '12px 0' }}>
          <div className="field" style={{ minWidth: 220 }}>
            <label>Ação</label>
            <select value={acao} onChange={(e) => { setAcao(e.target.value); setPage(1) }} data-testid="filtro-acao">
              {ACOES.map((op) => (
                <option key={op.value || 'todas'} value={op.value}>
                  {op.label}
                </option>
              ))}
            </select>
          </div>
          <div className="field">
            <label>De</label>
            <input type="date" value={de} onChange={(e) => { setDe(e.target.value); setPage(1) }} />
          </div>
          <div className="field">
            <label>Até</label>
            <input type="date" value={ate} onChange={(e) => { setAte(e.target.value); setPage(1) }} />
          </div>
        </div>
        <div className="table-wrap">
          <table className="data">
            <thead>
              <tr>
                <th>Quando</th>
                <th>Usuário</th>
                <th>Ação</th>
                <th>Detalhe</th>
              </tr>
            </thead>
            <tbody>
              {loading && items.length === 0 ? (
                <tr>
                  <td colSpan={4} className="hint">
                    Carregando…
                  </td>
                </tr>
              ) : items.length === 0 ? (
                <tr>
                  <td colSpan={4} className="hint">
                    Nenhum log encontrado.
                  </td>
                </tr>
              ) : (
                items.map((log) => (
                  <tr key={log.id}>
                    <td>{formatDateTime(log.created_at)}</td>
                    <td>
                      <strong>{log.user_nome || '—'}</strong>
                      <div className="hint">{log.user_login || ''}</div>
                    </td>
                    <td>{log.acao_label || log.acao}</td>
                    <td>{log.descricao}</td>
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
