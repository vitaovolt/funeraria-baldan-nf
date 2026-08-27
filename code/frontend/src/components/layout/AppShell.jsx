import { NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'

const linkClass = ({ isActive }) =>
  `rounded-lg px-3 py-2 text-sm font-semibold ${
    isActive ? 'bg-[var(--brand-accent)] text-[var(--brand-primary)]' : 'text-white/90 hover:bg-white/10'
  }`

export default function AppShell() {
  const { user, logout } = useAuth()

  return (
    <div className="min-h-screen bg-[var(--bg)]">
      <header className="bg-[var(--brand-primary)] text-white">
        <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
          <div>
            <p className="m-0 text-xs font-extrabold tracking-[0.12em] uppercase opacity-80">Baldan NF</p>
            <p className="m-0 text-sm opacity-90">{user?.name}</p>
          </div>
          <nav className="flex flex-wrap gap-1" aria-label="Principal">
            <NavLink to="/" end className={linkClass}>
              Início
            </NavLink>
            <NavLink to="/caixa" className={linkClass}>
              Caixa
            </NavLink>
            <NavLink to="/pdv" className={linkClass}>
              PDV
            </NavLink>
            <NavLink to="/notas" className={linkClass}>
              Notas
            </NavLink>
            <NavLink to="/produtos" className={linkClass}>
              Produtos
            </NavLink>
            <NavLink to="/clientes" className={linkClass}>
              Clientes
            </NavLink>
            <NavLink to="/estoque" className={linkClass}>
              Estoque
            </NavLink>
            <NavLink to="/consignado" className={linkClass}>
              Consignado
            </NavLink>
            <NavLink to="/config" className={linkClass}>
              Config
            </NavLink>
            <NavLink to="/marcas-categorias" className={linkClass}>
              Cadastros
            </NavLink>
          </nav>
          <button
            type="button"
            className="rounded-lg border border-white/30 px-3 py-1.5 text-sm font-semibold"
            onClick={() => logout()}
            data-testid="logout-button"
          >
            Sair
          </button>
        </div>
      </header>
      <div className="mx-auto max-w-5xl px-4 py-6">
        <Outlet />
      </div>
    </div>
  )
}
