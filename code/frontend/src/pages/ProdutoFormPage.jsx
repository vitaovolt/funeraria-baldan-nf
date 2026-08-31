import { useEffect, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import {
  createProduto,
  getProduto,
  listCategorias,
  listMarcas,
  updateProduto,
} from '../api/dominio'
import { useToast } from '../context/ToastContext'

const inicial = {
  codigo_barras: '', descricao: '', ncm: '', custo: '', preco_venda: '',
  estoque_atual: '', marca_id: '', categoria_id: '',
}

export default function ProdutoFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const submittingRef = useRef(false)
  const [form, setForm] = useState(inicial)
  const [marcas, setMarcas] = useState([])
  const [categorias, setCategorias] = useState([])
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    Promise.all([
      listMarcas({ per_page: 100 }),
      listCategorias({ per_page: 100 }),
      id ? getProduto(id) : Promise.resolve(null),
    ])
      .then(([m, c, p]) => {
        setMarcas(m.data || [])
        setCategorias(c.data || [])
        if (p?.data) {
          setForm(Object.fromEntries(Object.keys(inicial).map((key) => [key, p.data[key] ?? ''])))
        }
      })
      .catch(() => toast.error('Não foi possível carregar o formulário.'))
  }, [id, toast])

  function campo(nome, valor) {
    setForm((atual) => ({ ...atual, [nome]: valor }))
  }

  async function salvar(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!form.codigo_barras.trim() || !form.descricao.trim()) {
      toast.error('Informe código de barras e descrição.')
      return
    }
    submittingRef.current = true
    setSubmitting(true)
    const payload = {
      ...form,
      marca_id: form.marca_id || null,
      categoria_id: form.categoria_id || null,
      custo: Number(form.custo || 0),
      preco_venda: Number(form.preco_venda || 0),
      estoque_atual: Math.trunc(Number(form.estoque_atual || 0)),
    }
    try {
      await (id ? updateProduto(id, payload) : createProduto(payload))
      toast.success('Produto salvo.')
      navigate('/produtos')
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      toast.error(err.response?.data?.message || 'Não foi possível salvar o produto.')
    }
  }

  const inputClass = 'rounded-lg border border-[var(--line)] px-3 py-2'
  return (
    <form className="mx-auto max-w-3xl" onSubmit={salvar} data-testid="produto-form">
      <h1 className="m-0 text-2xl font-extrabold text-[var(--brand-primary)]">{id ? 'Editar produto' : 'Novo produto'}</h1>
      <div className="mt-5 grid gap-4 rounded-[10px] border border-[var(--line)] bg-white p-5 md:grid-cols-2">
        {[
          ['codigo_barras', 'Código de barras', 'text', undefined],
          ['descricao', 'Descrição', 'text', undefined],
          ['ncm', 'NCM', 'text', undefined],
          ['custo', 'Custo', 'number', '0.01'],
          ['preco_venda', 'Preço de venda', 'number', '0.01'],
          ['estoque_atual', 'Estoque atual', 'number', '1'],
        ].map(([nome, label, type, step]) => (
          <label key={nome} className="grid gap-1 text-sm font-semibold">
            {label}
            <input
              className={inputClass}
              type={type}
              min={nome === 'estoque_atual' ? '0' : undefined}
              step={step}
              value={form[nome]}
              onChange={(e) => campo(nome, e.target.value)}
            />
          </label>
        ))}
        <label className="grid gap-1 text-sm font-semibold">Marca
          <select className={inputClass} value={form.marca_id} onChange={(e) => campo('marca_id', e.target.value)}>
            <option value="">Sem marca</option>{marcas.map((m) => <option key={m.id} value={m.id}>{m.nome}</option>)}
          </select>
        </label>
        <label className="grid gap-1 text-sm font-semibold">Categoria
          <select className={inputClass} value={form.categoria_id} onChange={(e) => campo('categoria_id', e.target.value)}>
            <option value="">Sem categoria</option>{categorias.map((c) => <option key={c.id} value={c.id}>{c.nome}</option>)}
          </select>
        </label>
      </div>
      <button className="mt-4 rounded-lg bg-[var(--brand-primary)] px-5 py-3 font-bold text-white disabled:opacity-60" disabled={submitting} data-testid="produto-salvar">
        {submitting ? 'Processando…' : 'Salvar produto'}
      </button>
    </form>
  )
}
