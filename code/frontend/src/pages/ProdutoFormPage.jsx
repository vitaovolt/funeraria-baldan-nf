import { useEffect, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import {
  createProduto,
  getProduto,
  listCategorias,
  listMarcas,
  updateProduto,
} from '../api/dominio'
import MoneyInput from '../components/MoneyInput'
import { useToast } from '../context/ToastContext'
import { formatBrlNumber, parseBrl } from '../utils/moneyMask'

const inicial = {
  codigo_barras: '',
  descricao: '',
  ncm: '',
  custo: '',
  preco_venda: '',
  estoque_atual: '',
  marca_id: '',
  categoria_id: '',
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
          setForm({
            codigo_barras: p.data.codigo_barras ?? '',
            descricao: p.data.descricao ?? '',
            ncm: p.data.ncm ?? '',
            custo: formatBrlNumber(p.data.custo),
            preco_venda: formatBrlNumber(p.data.preco_venda),
            estoque_atual: p.data.estoque_atual != null ? String(Math.trunc(Number(p.data.estoque_atual))) : '',
            marca_id: p.data.marca_id ?? '',
            categoria_id: p.data.categoria_id ?? '',
          })
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
      custo: parseBrl(form.custo) || 0,
      preco_venda: parseBrl(form.preco_venda) || 0,
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

  return (
    <form className="mx-auto max-w-3xl" onSubmit={salvar} data-testid="produto-form">
      <div className="page-head">
        <div>
          <h1>{id ? 'Editar produto' : 'Novo produto'}</h1>
          <p>Cadastro com NCM, custo e preço para a nota.</p>
        </div>
      </div>
      <div className="panel grid gap-4 md:grid-cols-2">
        {[
          ['codigo_barras', 'Código de barras'],
          ['descricao', 'Descrição'],
          ['ncm', 'NCM'],
        ].map(([nome, label]) => (
          <div key={nome} className="field" style={{ margin: 0 }}>
            <label>{label}</label>
            <input value={form[nome]} onChange={(e) => campo(nome, e.target.value)} />
          </div>
        ))}
        <div className="field" style={{ margin: 0 }}>
          <label>Custo</label>
          <MoneyInput value={form.custo} onChange={(v) => campo('custo', v)} />
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Preço de venda</label>
          <MoneyInput value={form.preco_venda} onChange={(v) => campo('preco_venda', v)} />
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Estoque atual</label>
          <input
            type="number"
            min="0"
            step="1"
            value={form.estoque_atual}
            onChange={(e) => campo('estoque_atual', e.target.value)}
          />
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Marca</label>
          <select value={form.marca_id} onChange={(e) => campo('marca_id', e.target.value)}>
            <option value="">Sem marca</option>
            {marcas.map((m) => (
              <option key={m.id} value={m.id}>
                {m.nome}
              </option>
            ))}
          </select>
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Categoria</label>
          <select value={form.categoria_id} onChange={(e) => campo('categoria_id', e.target.value)}>
            <option value="">Sem categoria</option>
            {categorias.map((c) => (
              <option key={c.id} value={c.id}>
                {c.nome}
              </option>
            ))}
          </select>
        </div>
      </div>
      <button className="btn btn-primary mt-4" disabled={submitting} data-testid="produto-salvar">
        {submitting ? 'Processando…' : 'Salvar produto'}
      </button>
    </form>
  )
}
