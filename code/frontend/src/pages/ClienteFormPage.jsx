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
import { useToast } from '../context/ToastContext'

const inicial = { tipo: 'pf', documento: '', nome: '', telefone: '', email: '', tem_plano: false, plano_nome: '' }

export default function ClienteFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const submittingRef = useRef(false)
  const depSubmittingRef = useRef(false)
  const [form, setForm] = useState(inicial)
  const [dependentes, setDependentes] = useState([])
  const [depNome, setDepNome] = useState('')
  const [depParentesco, setDepParentesco] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [depSubmitting, setDepSubmitting] = useState(false)

  const carregarDependentes = useCallback(async () => {
    if (!id) return
    const res = await listDependentes(id)
    setDependentes(res.data || [])
  }, [id])

  useEffect(() => {
    if (!id) return
    Promise.all([getCliente(id), listDependentes(id)])
      .then(([cliente, deps]) => {
        setForm(Object.fromEntries(Object.keys(inicial).map((key) => [key, cliente.data[key] ?? inicial[key]])))
        setDependentes(deps.data || [])
      })
      .catch(() => toast.error('Não foi possível carregar o cliente.'))
  }, [id, toast])

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

  const inputClass = 'rounded-lg border border-[var(--line)] px-3 py-2'
  return (
    <div className="mx-auto max-w-3xl">
      <form onSubmit={salvar} data-testid="cliente-form">
        <h1 className="m-0 text-2xl font-extrabold text-[var(--brand-primary)]">{id ? 'Editar cliente' : 'Novo cliente'}</h1>
        <div className="mt-5 grid gap-4 rounded-[10px] border border-[var(--line)] bg-white p-5 md:grid-cols-2">
          <label className="grid gap-1 text-sm font-semibold">Tipo
            <select className={inputClass} value={form.tipo} onChange={(e) => campo('tipo', e.target.value)}>
              <option value="pf">Pessoa física</option><option value="pj">Pessoa jurídica</option>
            </select>
          </label>
          {[
            ['documento', 'Documento', 'text'], ['nome', 'Nome', 'text'], ['telefone', 'Telefone', 'tel'],
            ['email', 'E-mail', 'email'], ['plano_nome', 'Nome do plano', 'text'],
          ].map(([nome, label, type]) => (
            <label key={nome} className="grid gap-1 text-sm font-semibold">{label}
              <input className={inputClass} type={type} value={form[nome]} onChange={(e) => campo(nome, e.target.value)} disabled={nome === 'plano_nome' && !form.tem_plano} />
            </label>
          ))}
          <label className="flex items-center gap-2 text-sm font-semibold">
            <input type="checkbox" checked={form.tem_plano} onChange={(e) => campo('tem_plano', e.target.checked)} /> Tem plano
          </label>
        </div>
        <button className="mt-4 rounded-lg bg-[var(--brand-primary)] px-5 py-3 font-bold text-white disabled:opacity-60" disabled={submitting} data-testid="cliente-salvar">
          {submitting ? 'Processando…' : 'Salvar cliente'}
        </button>
      </form>

      {id ? (
        <section className="mt-7 rounded-[10px] border border-[var(--line)] bg-white p-5">
          <h2 className="m-0 text-lg font-extrabold">Dependentes</h2>
          <form className="mt-3 grid gap-2 md:grid-cols-[1fr_1fr_auto]" onSubmit={adicionarDependente}>
            <input className={inputClass} placeholder="Nome" value={depNome} onChange={(e) => setDepNome(e.target.value)} />
            <input className={inputClass} placeholder="Parentesco" value={depParentesco} onChange={(e) => setDepParentesco(e.target.value)} />
            <button className="rounded-lg bg-[var(--brand-accent)] px-4 py-2 font-bold disabled:opacity-60" disabled={depSubmitting}>{depSubmitting ? 'Processando…' : 'Adicionar'}</button>
          </form>
          <ul className="mt-3 divide-y divide-[var(--line)]">
            {dependentes.map((d) => (
              <li key={d.id} className="flex justify-between py-2 text-sm">
                <span>{d.nome} {d.parentesco ? `· ${d.parentesco}` : ''}</span>
                <button type="button" className="text-red-700 underline" disabled={depSubmitting} onClick={() => removerDependente(d.id)}>Remover</button>
              </li>
            ))}
          </ul>
        </section>
      ) : null}
    </div>
  )
}
