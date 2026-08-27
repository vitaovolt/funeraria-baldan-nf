import { useCallback, useEffect, useRef, useState } from 'react'
import {
  createCategoria,
  createMarca,
  deleteCategoria,
  deleteMarca,
  listCategorias,
  listMarcas,
  updateCategoria,
  updateMarca,
} from '../api/dominio'
import { useToast } from '../context/ToastContext'

function Cadastro({ titulo, itens, nome, setNome, editando, setEditando, salvar, excluir, testid }) {
  return (
    <section className="rounded-[10px] border border-[var(--line)] bg-white p-4">
      <h2 className="m-0 text-lg font-extrabold text-[var(--brand-primary)]">{titulo}</h2>
      <form className="mt-3 flex gap-2" onSubmit={salvar}>
        <input
          className="min-w-0 flex-1 rounded-lg border border-[var(--line)] px-3 py-2"
          value={nome}
          onChange={(e) => setNome(e.target.value)}
          placeholder="Nome"
          data-testid={`${testid}-nome`}
        />
        <button
          className="rounded-lg bg-[var(--brand-primary)] px-4 py-2 font-bold text-white disabled:opacity-60"
          disabled={editando?.submitting}
          data-testid={`${testid}-salvar`}
        >
          {editando?.submitting ? 'Processando…' : editando?.id ? 'Atualizar' : 'Salvar'}
        </button>
      </form>
      {editando?.id ? (
        <button className="mt-2 text-sm underline" type="button" onClick={() => setEditando(null)}>
          Cancelar edição
        </button>
      ) : null}
      <ul className="mt-4 divide-y divide-[var(--line)]">
        {itens.map((item) => (
          <li key={item.id} className="flex items-center justify-between gap-2 py-2 text-sm">
            <span>{item.nome}</span>
            <span className="flex gap-3">
              <button
                type="button"
                className="font-semibold text-[var(--brand-primary)] underline"
                onClick={() => {
                  setEditando({ id: item.id })
                  setNome(item.nome)
                }}
              >
                Editar
              </button>
              <button type="button" className="font-semibold text-red-700 underline" onClick={() => excluir(item.id)}>
                Excluir
              </button>
            </span>
          </li>
        ))}
      </ul>
    </section>
  )
}

export default function MarcasCategoriasPage() {
  const toast = useToast()
  const busyRef = useRef(false)
  const [marcas, setMarcas] = useState([])
  const [categorias, setCategorias] = useState([])
  const [marcaNome, setMarcaNome] = useState('')
  const [categoriaNome, setCategoriaNome] = useState('')
  const [marcaEditando, setMarcaEditando] = useState(null)
  const [categoriaEditando, setCategoriaEditando] = useState(null)

  const carregar = useCallback(async () => {
    try {
      const [m, c] = await Promise.all([listMarcas(), listCategorias()])
      setMarcas(m.data || [])
      setCategorias(c.data || [])
    } catch {
      toast.error('Não foi possível carregar marcas e categorias.')
    }
  }, [toast])

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    carregar()
  }, [carregar])

  async function salvar(tipo, event) {
    event.preventDefault()
    if (busyRef.current) return
    const marca = tipo === 'marca'
    const nome = (marca ? marcaNome : categoriaNome).trim()
    const editando = marca ? marcaEditando : categoriaEditando
    if (!nome) return toast.error('Informe o nome.')
    busyRef.current = true
    const setEditando = marca ? setMarcaEditando : setCategoriaEditando
    setEditando({ ...(editando || {}), submitting: true })
    try {
      if (editando?.id) {
        await (marca ? updateMarca(editando.id, { nome }) : updateCategoria(editando.id, { nome }))
      } else {
        await (marca ? createMarca({ nome }) : createCategoria({ nome }))
      }
      toast.success(`${marca ? 'Marca' : 'Categoria'} salva.`)
      ;(marca ? setMarcaNome : setCategoriaNome)('')
      setEditando(null)
      await carregar()
    } catch (err) {
      busyRef.current = false
      setEditando(editando)
      toast.error(err.response?.data?.message || 'Não foi possível salvar.')
      return
    }
    busyRef.current = false
  }

  async function excluir(tipo, id) {
    if (busyRef.current || !window.confirm('Excluir este registro?')) return
    busyRef.current = true
    try {
      await (tipo === 'marca' ? deleteMarca(id) : deleteCategoria(id))
      toast.success('Registro excluído.')
      await carregar()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível excluir.')
    } finally {
      busyRef.current = false
    }
  }

  return (
    <div data-testid="page-marcas-categorias">
      <h1 className="m-0 text-2xl font-extrabold text-[var(--brand-primary)]">Marcas e categorias</h1>
      <div className="mt-5 grid gap-5 md:grid-cols-2">
        <Cadastro
          titulo="Marcas"
          itens={marcas}
          nome={marcaNome}
          setNome={setMarcaNome}
          editando={marcaEditando}
          setEditando={setMarcaEditando}
          salvar={(e) => salvar('marca', e)}
          excluir={(id) => excluir('marca', id)}
          testid="marca"
        />
        <Cadastro
          titulo="Categorias"
          itens={categorias}
          nome={categoriaNome}
          setNome={setCategoriaNome}
          editando={categoriaEditando}
          setEditando={setCategoriaEditando}
          salvar={(e) => salvar('categoria', e)}
          excluir={(id) => excluir('categoria', id)}
          testid="categoria"
        />
      </div>
    </div>
  )
}
