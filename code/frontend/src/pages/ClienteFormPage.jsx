import { useCallback, useEffect, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import {
  createCliente,
  createDependente,
  deleteDependente,
  getCliente,
  listDependentes,
  updateCliente,
} from '../api/dominio'
import { Pagination, SearchBar } from '../components/ListToolbar'
import { useToast } from '../context/ToastContext'
import { metaFromResponse } from '../utils/format'

const inicial = { tipo: 'pf', documento: '', nome: '', telefone: '', email: '', tem_plano: false, plano_nome: '' }

export default function ClienteFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const submittingRef = useRef(false)
  const depSubmittingRef = useRef(false)
  const [form, setForm] = useState(inicial)
  const [dependentes, setDependentes] = useState([])
  const [depMeta, setDepMeta] = useState({ current_page: 1, last_page: 1, total: 0 })
  const [depQ, setDepQ] = useState('')
  const [depPage, setDepPage] = useState(1)
  const [depNome, setDepNome] = useState('')
  const [depParentesco, setDepParentesco] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [depSubmitting, setDepSubmitting] = useState(false)

  const carregarDependentes = useCallback(async () => {
    if (!id) return
    const res = await listDependentes(id, {
      page: depPage,
      per_page: 10,
      ...(depQ.trim() ? { q: depQ.trim() } : {}),
    })
    setDependentes(res.data || [])
    setDepMeta(metaFromResponse(res, (res.data || []).length))
  }, [id, depPage, depQ])

  useEffect(() => {
    if (!id) return
    getCliente(id)
      .then((cliente) => {
        setForm(Object.fromEntries(Object.keys(inicial).map((key) => [key, cliente.data[key] ?? inicial[key]])))
      })
      .catch(() => toast.error('Não foi possível carregar o cliente.'))
  }, [id, toast])

  useEffect(() => {
    if (!id) return undefined
    const t = window.setTimeout(() => {
      carregarDependentes().catch(() => toast.error('Não foi possível carregar os dependentes.'))
    }, 200)
    return () => window.clearTimeout(t)
  }, [id, carregarDependentes, toast])

  function campo(nome, valor) {
    setForm((atual) => ({ ...atual, [nome]: valor }))
  }

  async function salvar(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!form.documento.trim() || !form.nome.trim()) {
      toast.error('Informe documento e nome.')
      return
    }
    submittingRef.current = true
    setSubmitting(true)
    try {
      await (id ? updateCliente(id, form) : createCliente(form))
      toast.success('Cliente salvo.')
      navigate('/clientes')
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      toast.error(err.response?.data?.message || 'Não foi possível salvar o cliente.')
    }
  }

  async function adicionarDependente(event) {
    event.preventDefault()
    if (depSubmittingRef.current) return
    if (!depNome.trim()) return toast.error('Informe o nome do dependente.')
    depSubmittingRef.current = true
    setDepSubmitting(true)
    try {
      await createDependente(id, { nome: depNome, parentesco: depParentesco || null })
      toast.success('Dependente adicionado.')
      setDepNome('')
      setDepParentesco('')
      await carregarDependentes()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível adicionar o dependente.')
    } finally {
      depSubmittingRef.current = false
      setDepSubmitting(false)
    }
  }

  async function removerDependente(depId) {
    if (depSubmittingRef.current || !window.confirm('Remover este dependente?')) return
    depSubmittingRef.current = true
    setDepSubmitting(true)
    try {
      await deleteDependente(depId)
      toast.success('Dependente removido.')
      await carregarDependentes()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível remover o dependente.')
    } finally {
      depSubmittingRef.current = false
      setDepSubmitting(false)
    }
  }

  return (
    <div className="mx-auto max-w-3xl">
      <form onSubmit={salvar} data-testid="cliente-form">
        <div className="page-head">
          <div>
            <h1>{id ? 'Editar cliente' : 'Novo cliente'}</h1>
            <p>Dados do titular e vínculo com plano, se houver.</p>
          </div>
        </div>
        <div className="panel grid gap-4 md:grid-cols-2">
          <div className="field" style={{ margin: 0 }}>
            <label>Tipo</label>
            <select value={form.tipo} onChange={(e) => campo('tipo', e.target.value)}>
              <option value="pf">Pessoa física</option>
              <option value="pj">Pessoa jurídica</option>
            </select>
          </div>
          {[
            ['documento', 'Documento', 'text'],
            ['nome', 'Nome', 'text'],
            ['telefone', 'Telefone', 'tel'],
            ['email', 'E-mail', 'email'],
            ['plano_nome', 'Nome do plano', 'text'],
          ].map(([nome, label, type]) => (
            <div key={nome} className="field" style={{ margin: 0 }}>
              <label>{label}</label>
              <input
                type={type}
                value={form[nome]}
                onChange={(e) => campo(nome, e.target.value)}
                disabled={nome === 'plano_nome' && !form.tem_plano}
              />
            </div>
          ))}
          <label className="flex items-center gap-2 text-sm font-semibold">
            <input type="checkbox" checked={form.tem_plano} onChange={(e) => campo('tem_plano', e.target.checked)} /> Tem
            plano
          </label>
        </div>
        <button className="btn btn-primary mt-4" disabled={submitting} data-testid="cliente-salvar">
          {submitting ? 'Processando…' : 'Salvar cliente'}
        </button>
      </form>

      {id ? (
        <section className="panel mt-6">
          <h2 className="m-0 text-lg font-extrabold">Dependentes</h2>
          <form className="mt-3 grid gap-2 md:grid-cols-[1fr_1fr_auto]" onSubmit={adicionarDependente}>
            <div className="field" style={{ margin: 0 }}>
              <label>Nome</label>
              <input placeholder="Nome" value={depNome} onChange={(e) => setDepNome(e.target.value)} />
            </div>
            <div className="field" style={{ margin: 0 }}>
              <label>Parentesco</label>
              <input
                placeholder="Parentesco"
                value={depParentesco}
                onChange={(e) => setDepParentesco(e.target.value)}
              />
            </div>
            <button className="btn btn-accent self-end" disabled={depSubmitting}>
              {depSubmitting ? 'Processando…' : 'Adicionar'}
            </button>
          </form>
          <div className="mt-4">
            <SearchBar
              value={depQ}
              onChange={(v) => {
                setDepPage(1)
                setDepQ(v)
              }}
              placeholder="Buscar dependente"
            />
            <div className="table-wrap">
              <table className="data" style={{ minWidth: 0 }}>
                <thead>
                  <tr>
                    <th>Nome</th>
                    <th>Parentesco</th>
                    <th>Ações</th>
                  </tr>
                </thead>
                <tbody>
                  {dependentes.length === 0 ? (
                    <tr>
                      <td colSpan={3} className="hint">
                        Nenhum dependente.
                      </td>
                    </tr>
                  ) : (
                    dependentes.map((d) => (
                      <tr key={d.id}>
                        <td>{d.nome}</td>
                        <td>{d.parentesco || '—'}</td>
                        <td>
                          <button
                            type="button"
                            className="btn btn-danger"
                            style={{ padding: '6px 12px', fontSize: '0.8rem' }}
                            disabled={depSubmitting}
                            onClick={() => removerDependente(d.id)}
                          >
                            Remover
                          </button>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
            <Pagination meta={depMeta} onPageChange={setDepPage} />
          </div>
        </section>
      ) : null}
    </div>
  )
}
