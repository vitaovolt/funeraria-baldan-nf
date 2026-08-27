import { useCallback, useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { deleteProduto, listProdutos } from '../api/dominio'
import { useToast } from '../context/ToastContext'

export default function ProdutosPage() {
  const toast = useToast()
  const deletingRef = useRef(false)
  const [q, setQ] = useState('')
  const [produtos, setProdutos] = useState([])

  const carregar = useCallback(async () => {
    try {
      const res = await listProdutos({ q: q || undefined })
      setProdutos(res.data || [])
    } catch {
      toast.error('Não foi possível carregar os produtos.')
    }
  }, [q, toast])

  useEffect(() => {
    const timer = window.setTimeout(carregar, 200)
    return () => window.clearTimeout(timer)
  }, [carregar])

  async function excluir(id) {
    if (deletingRef.current || !window.confirm('Excluir este produto?')) return
    deletingRef.current = true
    try {
      await deleteProduto(id)
      toast.success('Produto excluído.')
      await carregar()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível excluir o produto.')
    } finally {
      deletingRef.current = false
    }
  }

  return (
    <div data-testid="page-produtos">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="m-0 text-2xl font-extrabold text-[var(--brand-primary)]">Produtos</h1>
        <Link to="/produtos/novo" className="rounded-lg bg-[var(--brand-accent)] px-4 py-2 font-bold text-[var(--brand-primary)]">
          Novo produto
        </Link>
      </div>
      <input
        className="mt-4 w-full rounded-lg border border-[var(--line)] px-3 py-2"
        placeholder="Buscar por descrição ou código"
        value={q}
        onChange={(e) => setQ(e.target.value)}
      />
      <div className="mt-4 overflow-x-auto rounded-[10px] border border-[var(--line)] bg-white">
        <table className="w-full text-left text-sm">
          <thead className="bg-[var(--brand-primary)] text-white">
            <tr><th className="p-3">Código</th><th className="p-3">Descrição</th><th className="p-3">Preço</th><th className="p-3">Estoque</th><th className="p-3">Ações</th></tr>
          </thead>
          <tbody>
            {produtos.map((p) => (
              <tr key={p.id} className="border-t border-[var(--line)]">
                <td className="p-3">{p.codigo_barras}</td>
                <td className="p-3 font-semibold">{p.descricao}</td>
                <td className="p-3">R$ {Number(p.preco_venda).toFixed(2)}</td>
                <td className="p-3">{p.estoque_atual}</td>
                <td className="p-3">
                  <Link className="mr-3 font-semibold underline" to={`/produtos/${p.id}`}>Editar</Link>
                  <button type="button" className="font-semibold text-red-700 underline" onClick={() => excluir(p.id)}>Excluir</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
