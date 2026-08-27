import { useRef, useState } from 'react'
import { Navigate, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function LoginPage() {
  const { login, isAuthenticated, loading } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const submittingRef = useRef(false)
  const [email, setEmail] = useState('operador@baldan.local')
  const [password, setPassword] = useState('password')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  if (!loading && isAuthenticated) {
    return <Navigate to="/" replace />
  }

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return

    setError('')
    submittingRef.current = true
    setSubmitting(true)
    try {
      await login(email.trim().toLowerCase(), password)
      const to = location.state?.from?.pathname || '/'
      navigate(to, { replace: true })
      // sucesso com redirect: mantém bloqueado
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg =
        err.response?.data?.errors?.email?.[0] ||
        err.response?.data?.message ||
        'Não foi possível entrar. Verifique e-mail e senha.'
      setError(msg)
    }
  }

  return (
    <main className="mx-auto flex min-h-screen max-w-md flex-col justify-center px-5 py-12" data-testid="page-login">
      <p className="m-0 text-xs font-extrabold tracking-[0.14em] uppercase text-[var(--brand-primary)]">
        Serviços humanizados
      </p>
      <h1 className="mt-2 text-4xl font-extrabold tracking-tight text-[var(--brand-primary)]">
        Baldan<span className="text-[var(--brand-accent)]"> NF</span>
      </h1>
      <p className="mt-2 text-[var(--muted)]">Entre para acessar o PDV e os cadastros.</p>

      <form
        className="mt-8 space-y-4 rounded-[10px] border border-[var(--line)] bg-[var(--surface)] p-5 shadow-[0_10px_30px_rgba(13,42,92,0.08)]"
        onSubmit={onSubmit}
        data-testid="form-login"
      >
        <h2 className="m-0 text-xl font-extrabold text-[var(--brand-primary)]">Entrar</h2>

        <label className="block">
          <span className="text-sm font-semibold text-[var(--muted)]">E-mail</span>
          <input
            className="mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2 outline-none focus:border-[var(--brand-primary)]"
            type="email"
            autoComplete="username"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            data-testid="login-email"
          />
        </label>

        <label className="block">
          <span className="text-sm font-semibold text-[var(--muted)]">Senha</span>
          <input
            className="mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2 outline-none focus:border-[var(--brand-primary)]"
            type="password"
            autoComplete="current-password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            data-testid="login-password"
          />
        </label>

        {error ? (
          <p className="m-0 text-sm font-semibold text-[#b42318]" data-testid="login-error">
            {error}
          </p>
        ) : null}

        <button
          className="w-full rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 font-bold text-white disabled:opacity-60"
          type="submit"
          disabled={submitting}
          data-testid="login-submit"
        >
          {submitting ? 'Entrando…' : 'Entrar'}
        </button>
      </form>
    </main>
  )
}
