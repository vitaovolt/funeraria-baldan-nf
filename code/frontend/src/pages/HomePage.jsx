import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

const CARDS = [
  { to: '/caixa', mark: '$', label: 'Caixa', desc: 'Abrir, vendas do dia e fechar', testId: 'home-caixa' },
  { to: '/pdv', mark: 'V', label: 'Nova venda', desc: 'PDV com desconto e NFC-e' },
  { to: '/consignado', mark: 'C', label: 'Consignado', desc: 'Provar produto · devolver ou vender' },
  { to: '/produtos', mark: 'P', label: 'Produtos', desc: 'Custo, preço, NCM' },
  { to: '/clientes', mark: '👤', label: 'Clientes', desc: 'Titular, dependentes e plano' },
  { to: '/estoque', mark: 'E', label: 'Estoque', desc: 'Saldos e ajustes' },
  { to: '/notas', mark: 'N', label: 'Notas', desc: 'Cupom e retentativa' },
  { to: '/config', mark: 'A1', label: 'Configuração', desc: 'Empresa e certificado' },
  { to: '/marcas-categorias', mark: 'M', label: 'Marcas', desc: 'Marcas e categorias' },
]

export default function HomePage() {
  const { user } = useAuth()
  const firstName = (user?.name || 'operador').split(' ')[0]

  return (
    <div data-testid="page-home">
      <div className="home-hero">
        <h1>Olá, {firstName}</h1>
        <p>Venda de produtos, estoque e nota fiscal em um só lugar — com a identidade Baldan.</p>
      </div>
      <div className="home-cards">
        {CARDS.map((c) => (
          <Link key={c.to} to={c.to} className="home-card" data-testid={c.testId}>
            <div className="mark">{c.mark}</div>
            <div className="label">{c.label}</div>
            <div className="desc">{c.desc}</div>
          </Link>
        ))}
      </div>
    </div>
  )
}
