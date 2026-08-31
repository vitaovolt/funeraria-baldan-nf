import { useCallback, useRef, useState } from 'react'
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
import { Pagination, SearchBar } from '../components/ListToolbar'
import { useToast } from '../context/ToastContext'
import { usePagedList } from '../hooks/usePagedList'

function Cadastro({
  titulo,
  q,
  setQ,
  itens,
  meta,
  setPage,
  nome,
  setNome,
  editando,
  setEditando,
  salvar,
  excluir,
  testid,
}) {
  return (
    <section className="panel">
      <h2 className="m-0 text-lg font-extrabold text-[var(--brand-primary)]">{titulo}</h2>
      <form className="mt-3 flex flex-wrap gap-2" onSubmit={salvar}>
        <input
          className="min-w-0 flex-1 rounded-xl border border-[var(--brand-line)] px-3 py-2"
          value={nome}
          onChange={(e) => setNome(e.target.value)}
          placeholder="Nome"
          data-testid={`${testid}-nome`}
        />
        <button className="btn btn-primary" disabled={editando?.submitting} data-testid={`${testid}-salvar`}>
          {editando?.submitting ? 'Processando…' : editando?.id ? 'Atualizar' : 'Salvar'}
        </button>
      </form>
      {editando?.id ? (
        <button className="mt-2 text-sm underline" type="button" onClick={() => setEditando(null)}>
          Cancelar edição
        </button>
      ) : null}
      <div className="mt-4">
        <SearchBar value={q} onChange={setQ} placeholder={`Pesquisar ${titulo.toLowerCase()}`} />
        <div className="table-wrap">
          <table className="data" style={{ minWidth: 0 }}>
            <thead>
              <tr>
                <th>Nome</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              {itens.length === 0 ? (
                <tr>
                  <td colSpan={2} className="hint">
                    Nenhum registro.
                  </td>
                </tr>
              ) : (
                itens.map((item) => (
                  <tr key={item.id}>
                    <td>{item.nome}</td>
                    <td className="actions">
                      <button
                        type="button"
                        className="btn btn-ghost"
                        style={{ padding: '6px 12px', fontSize: '0.8rem' }}
                        onClick={() => {
                          setEditando({ id: item.id })
                          setNome(item.nome)
                        }}
                      >
                        Editar
                      </button>{' '}
                      <button
                        type="button"
                        className="btn btn-danger"
                        style={{ padding: '6px 12px', fontSize: '0.8rem' }}
                        onClick={() => excluir(item.id)}
                      >
                        Excluir
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
        <Pagination meta={meta} onPageChange={setPage} />
      </div>
    </section>
  )
}

export default function MarcasCategoriasPage() {
  const toast = useToast()
  const busyRef = useRef(false)
  const [marcaNome, setMarcaNome] = useState('')
  const [categoriaNome, setCategoriaNome] = useState('')
  const [marcaEditando, setMarcaEditando] = useState(null)
  const [categoriaEditando, setCategoriaEditando] = useState(null)

  const fetchMarcas = useCallback((params) => listMarcas(params), [])
  const fetchCategorias = useCallback((params) => listCategorias(params), [])
  const marcas = usePagedList(fetchMarcas)
  const categorias = usePagedList(fetchCategorias)

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
      await (marca ? marcas.reload() : categorias.reload())
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
      await (tipo === 'marca' ? marcas.reload() : categorias.reload())
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível excluir.')
    } finally {
      busyRef.current = false
    }
  }

  return (
    <div data-testid="page-marcas-categorias">
      <div className="page-head">
        <div>
          <h1>Marcas e categorias</h1>
          <p>Organização do catálogo de produtos.</p>
        </div>
      </div>
      <div className="mt-1 grid gap-5 md:grid-cols-2">
        <Cadastro
          titulo="Marcas"
          q={marcas.q}
          setQ={marcas.setQ}
          itens={marcas.items}
          meta={marcas.meta}
          setPage={marcas.setPage}
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
          q={categorias.q}
          setQ={categorias.setQ}
          itens={categorias.items}
          meta={categorias.meta}
          setPage={categorias.setPage}
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
