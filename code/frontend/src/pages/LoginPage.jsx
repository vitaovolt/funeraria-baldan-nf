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
    <div className="login" data-testid="page-login">
      <form className="login-card" onSubmit={onSubmit} data-testid="form-login">
        <img src="/brand/baldan_logo.jpg" alt="Baldan" />
        <h1>Entrar</h1>
        <p className="tagline">o melhor plano para sua família</p>

        <div className="field">
          <label htmlFor="login-email">E-mail</label>
          <input
            id="login-email"
            type="email"
            autoComplete="username"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            data-testid="login-email"
          />
        </div>

        <div className="field">
          <label htmlFor="login-password">Senha</label>
          <input
            id="login-password"
            type="password"
            autoComplete="current-password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            data-testid="login-password"
          />
        </div>

        {error ? (
          <p className="m-0 mb-3 text-sm font-semibold text-[var(--danger)]" data-testid="login-error">
            {error}
          </p>
        ) : null}

        <div className="mt-2 flex flex-wrap justify-center gap-2.5">
          <button className="btn btn-primary" type="submit" disabled={submitting} data-testid="login-submit">
            {submitting ? 'Entrando…' : 'Entrar'}
          </button>
        </div>
      </form>
    </div>
  )
}
