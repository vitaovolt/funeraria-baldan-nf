import { useEffect, useState } from 'react'
import { NavLink, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'

const NAV = [
  { to: '/', end: true, label: 'Início' },
  { to: '/caixa', label: 'Caixa' },
  { to: '/pdv', label: 'Venda' },
  { to: '/consignado', label: 'Consignado' },
  { to: '/produtos', label: 'Produtos' },
  { to: '/marcas-categorias', label: 'Marcas' },
  { to: '/clientes', label: 'Clientes' },
  { to: '/estoque', label: 'Estoque' },
  { to: '/notas', label: 'Notas' },
  { to: '/config', label: 'Config' },
]

const TITLES = {
  '/': 'Início',
  '/caixa': 'Caixa',
  '/pdv': 'Venda',
  '/consignado': 'Consignado',
  '/produtos': 'Produtos',
  '/marcas-categorias': 'Marcas',
  '/clientes': 'Clientes',
  '/estoque': 'Estoque',
  '/notas': 'Notas',
  '/config': 'Config',
}

function pageTitle(pathname) {
  if (TITLES[pathname]) return TITLES[pathname]
  const hit = Object.keys(TITLES).find((k) => k !== '/' && pathname.startsWith(k))
  return hit ? TITLES[hit] : 'Baldan'
}

export default function AppShell() {
  const { user, logout } = useAuth()
  const location = useLocation()
  const [menuOpen, setMenuOpen] = useState(false)
  const title = pageTitle(location.pathname)

  useEffect(() => {
    setMenuOpen(false)
  }, [location.pathname])

  useEffect(() => {
    document.body.classList.toggle('menu-open', menuOpen)
    return () => document.body.classList.remove('menu-open')
  }, [menuOpen])

  return (
    <div className="app-shell">
      <header className="mobile-bar">
        <button type="button" className="menu-btn" aria-label="Abrir menu" onClick={() => setMenuOpen(true)}>
          ☰
        </button>
        <img src="/brand/baldan_logo.jpg" alt="Baldan" />
        <span className="title">{title}</span>
      </header>

      <div
        className={`side-backdrop${menuOpen ? ' on' : ''}`}
        onClick={() => setMenuOpen(false)}
        aria-hidden={!menuOpen}
      />

      <aside className={`app-side${menuOpen ? ' open' : ''}`} id="side-menu">
        <button type="button" className="side-close" onClick={() => setMenuOpen(false)}>
          Fechar menu
        </button>
        <div className="brand-block">
          <img src="/brand/baldan_logo.jpg" alt="Baldan" />
          <p>SERVIÇOS HUMANIZADOS</p>
        </div>
        <nav aria-label="Principal">
          {NAV.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              className={({ isActive }) => `app-nav${isActive ? ' on' : ''}`}
            >
              {item.label}
            </NavLink>
          ))}
          <button type="button" className="app-nav" onClick={() => logout()} data-testid="logout-button">
            Sair
          </button>
        </nav>
        <div className="app-side-foot">
          {user?.name}
          <br />
          <span style={{ color: 'var(--brand-accent)' }}>{user?.role === 'admin' ? 'Admin' : 'Operador'}</span>
        </div>
      </aside>

      <main className="app-main">
        <Outlet />
      </main>
    </div>
  )
}
