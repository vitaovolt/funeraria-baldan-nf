import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function HomePage() {
  const { user } = useAuth()

  return (
    <div data-testid="page-home">
      <h1 className="m-0 text-3xl font-extrabold text-[var(--brand-primary)]">
        Olá, {user?.name || 'operador'}
      </h1>
      <p className="mt-2 text-[var(--muted)]">Fluxo do dia: abrir caixa → PDV → pagar e emitir NFC-e.</p>
      <div className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <Link
          to="/caixa"
          className="rounded-lg bg-[var(--brand-primary)] px-4 py-4 font-bold text-white"
          data-testid="home-caixa"
        >
          Ir ao caixa
        </Link>
        <Link
          to="/pdv"
          className="rounded-lg bg-[var(--brand-accent)] px-4 py-3 font-bold text-[var(--brand-primary)]"
        >
          Abrir PDV
        </Link>
        {[
          ['/produtos', 'Produtos'],
          ['/clientes', 'Clientes'],
          ['/estoque', 'Estoque'],
          ['/consignado', 'Consignado'],
          ['/config', 'Configuração fiscal'],
          ['/marcas-categorias', 'Marcas e categorias'],
        ].map(([to, label]) => (
          <Link
            key={to}
            to={to}
            className="rounded-lg border border-[var(--line)] bg-white px-4 py-4 font-bold text-[var(--brand-primary)] hover:border-[var(--brand-primary)]"
          >
            {label}
          </Link>
        ))}
      </div>
    </div>
  )
}
