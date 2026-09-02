import { useCallback, useRef, useState } from 'react'
import { Navigate } from 'react-router-dom'
import { createUsuario, deleteUsuario, listUsuarios, updateUsuario } from '../api/usuarios'
import { Pagination, SearchBar } from '../components/ListToolbar'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { usePagedList } from '../hooks/usePagedList'

const formVazio = {
  name: '',
  login: '',
  email: '',
  password: '',
  role: 'operador',
  ativo: true,
}

function erroValidacao(err) {
  const errors = err.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const first = Object.values(errors).flat()[0]
    if (first) return String(first)
  }
  return err.response?.data?.message || 'Não foi possível salvar o usuário.'
}

export default function UsuariosPage() {
  const toast = useToast()
  const { user } = useAuth()
  const isAdmin = user?.role === 'admin'
  const submittingRef = useRef(false)
  const [modal, setModal] = useState(null)
  const [form, setForm] = useState(formVazio)
  const [submitting, setSubmitting] = useState(false)

  const fetcher = useCallback((params) => listUsuarios(params), [])
  const { q, setQ, setPage, items, meta, reload } = usePagedList(fetcher)

  if (!isAdmin) {
    return <Navigate to="/" replace />
  }

  function abrirNovo() {
    setForm(formVazio)
    setModal('novo')
  }

  function abrirEditar(u) {
    setForm({
      name: u.name || '',
      login: u.login || '',
      email: u.email || '',
      password: '',
      role: u.role || 'operador',
      ativo: Boolean(u.ativo),
    })
    setModal(u)
  }

  function campo(nome, valor) {
    setForm((atual) => ({ ...atual, [nome]: valor }))
  }

  async function salvar(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!form.name.trim() || !form.login.trim()) {
      toast.error('Informe nome e usuário de acesso.')
      return
    }
    if (modal === 'novo' && !form.password) {
      toast.error('Informe a senha.')
      return
    }
    submittingRef.current = true
    setSubmitting(true)
    try {
      const payload = {
        name: form.name.trim(),
        login: form.login.trim().toLowerCase(),
        email: form.email.trim() || undefined,
        role: form.role,
        ativo: form.ativo,
      }
      if (form.password) payload.password = form.password

      if (modal === 'novo') {
        await createUsuario({ ...payload, password: form.password })
        toast.success('Usuário criado.')
      } else {
        await updateUsuario(modal.id, payload)
        toast.success('Usuário atualizado.')
      }
      setModal(null)
      await reload()
    } catch (err) {
      toast.error(erroValidacao(err))
    } finally {
      submittingRef.current = false
      setSubmitting(false)
    }
  }

  async function excluir(u) {
    if (submittingRef.current || !window.confirm(`Excluir o usuário ${u.login}?`)) return
    submittingRef.current = true
    try {
      await deleteUsuario(u.id)
      toast.success('Usuário excluído.')
      await reload()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível excluir.')
    } finally {
      submittingRef.current = false
    }
  }

  return (
    <div data-testid="page-usuarios">
      <div className="page-head">
        <div>
          <h1>Usuários</h1>
          <p>Cadastro de operadores e administradores. O acesso pode ser um usuário simples, sem e-mail.</p>
        </div>
        <button type="button" className="btn btn-accent" onClick={abrirNovo} data-testid="usuario-novo">
          Novo usuário
        </button>
      </div>

      <section className="panel">
        <SearchBar value={q} onChange={setQ} placeholder="Buscar nome ou usuário" testId="busca-usuario" />
        <div className="table-wrap">
          <table className="data">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Usuário</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              {items.length === 0 ? (
                <tr>
                  <td colSpan={6} className="hint">
                    Nenhum usuário encontrado.
                  </td>
                </tr>
              ) : (
                items.map((u) => (
                  <tr key={u.id}>
                    <td>
                      <strong>{u.name}</strong>
                    </td>
                    <td>{u.login}</td>
                    <td>{u.email || '—'}</td>
                    <td>{u.role === 'admin' ? 'Admin' : 'Operador'}</td>
                    <td>
                      <span className={`pill ${u.ativo ? 'ok' : 'warn'}`}>{u.ativo ? 'Ativo' : 'Inativo'}</span>
                    </td>
                    <td className="actions">
                      <button
                        type="button"
                        className="btn btn-ghost"
                        style={{ padding: '6px 12px', fontSize: '0.8rem' }}
                        onClick={() => abrirEditar(u)}
                      >
                        Editar
                      </button>{' '}
                      <button
                        type="button"
                        className="btn btn-danger"
                        style={{ padding: '6px 12px', fontSize: '0.8rem' }}
                        onClick={() => excluir(u)}
                        disabled={u.id === user?.id}
                      >
                        Excluir
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
        <Pagination meta={meta} onPageChange={setPage} />
      </section>

      {modal ? (
        <div className="modal-backdrop" role="presentation" onMouseDown={() => !submitting && setModal(null)}>
          <form
            className="modal-card panel"
            data-testid="modal-usuario"
            onMouseDown={(e) => e.stopPropagation()}
            onSubmit={salvar}
          >
            <h2>{modal === 'novo' ? 'Novo usuário' : 'Editar usuário'}</h2>
            <div className="field">
              <label>Nome</label>
              <input value={form.name} onChange={(e) => campo('name', e.target.value)} required disabled={submitting} />
            </div>
            <div className="field">
              <label>Usuário de acesso</label>
              <input
                value={form.login}
                onChange={(e) => campo('login', e.target.value.toLowerCase().replace(/\s/g, ''))}
                required
                disabled={submitting}
                placeholder="ex.: marcia"
                data-testid="usuario-login"
              />
            </div>
            <div className="field">
              <label>E-mail (opcional)</label>
              <input
                type="email"
                value={form.email}
                onChange={(e) => campo('email', e.target.value)}
                disabled={submitting}
                placeholder="Opcional"
              />
            </div>
            <div className="field">
              <label>{modal === 'novo' ? 'Senha' : 'Nova senha (opcional)'}</label>
              <input
                type="password"
                value={form.password}
                onChange={(e) => campo('password', e.target.value)}
                required={modal === 'novo'}
                disabled={submitting}
                minLength={6}
                data-testid="usuario-senha"
              />
            </div>
            <div className="field">
              <label>Perfil</label>
              <select value={form.role} onChange={(e) => campo('role', e.target.value)} disabled={submitting}>
                <option value="operador">Operador</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div className="field">
              <label>
                <input
                  type="checkbox"
                  checked={form.ativo}
                  onChange={(e) => campo('ativo', e.target.checked)}
                  disabled={submitting || (modal !== 'novo' && modal.id === user?.id)}
                />{' '}
                Conta ativa
              </label>
            </div>
            <div className="modal-actions">
              <button type="submit" className="btn btn-accent w-full" disabled={submitting} data-testid="usuario-salvar">
                {submitting ? 'Processando…' : 'Salvar'}
              </button>
              <button type="button" className="btn btn-ghost w-full" disabled={submitting} onClick={() => setModal(null)}>
                Cancelar
              </button>
            </div>
          </form>
        </div>
      ) : null}
    </div>
  )
}
