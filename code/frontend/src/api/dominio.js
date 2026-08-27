import client from './client'

export const listMarcas = (params) => client.get('/marcas', { params }).then((r) => r.data)
export const getMarca = (id) => client.get(`/marcas/${id}`).then((r) => r.data)
export const createMarca = (payload) => client.post('/marcas', payload).then((r) => r.data)
export const updateMarca = (id, payload) => client.put(`/marcas/${id}`, payload).then((r) => r.data)
export const deleteMarca = (id) => client.delete(`/marcas/${id}`).then((r) => r.data)

export const listCategorias = (params) => client.get('/categorias', { params }).then((r) => r.data)
export const getCategoria = (id) => client.get(`/categorias/${id}`).then((r) => r.data)
export const createCategoria = (payload) => client.post('/categorias', payload).then((r) => r.data)
export const updateCategoria = (id, payload) =>
  client.put(`/categorias/${id}`, payload).then((r) => r.data)
export const deleteCategoria = (id) => client.delete(`/categorias/${id}`).then((r) => r.data)

export const listProdutos = (params) => client.get('/produtos', { params }).then((r) => r.data)
export const getProduto = (id) => client.get(`/produtos/${id}`).then((r) => r.data)
export const createProduto = (payload) => client.post('/produtos', payload).then((r) => r.data)
export const updateProduto = (id, payload) =>
  client.put(`/produtos/${id}`, payload).then((r) => r.data)
export const deleteProduto = (id) => client.delete(`/produtos/${id}`).then((r) => r.data)

export const listClientes = (params) => client.get('/clientes', { params }).then((r) => r.data)
export const getCliente = (id) => client.get(`/clientes/${id}`).then((r) => r.data)
export const createCliente = (payload) => client.post('/clientes', payload).then((r) => r.data)
export const updateCliente = (id, payload) =>
  client.put(`/clientes/${id}`, payload).then((r) => r.data)
export const deleteCliente = (id) => client.delete(`/clientes/${id}`).then((r) => r.data)

export const listDependentes = (clienteId) =>
  client.get(`/clientes/${clienteId}/dependentes`).then((r) => r.data)
export const createDependente = (clienteId, payload) =>
  client.post(`/clientes/${clienteId}/dependentes`, payload).then((r) => r.data)
export const updateDependente = (id, payload) =>
  client.put(`/dependentes/${id}`, payload).then((r) => r.data)
export const deleteDependente = (id) => client.delete(`/dependentes/${id}`).then((r) => r.data)

export const getConfiguracaoFiscal = () => client.get('/configuracao-fiscal').then((r) => r.data)
export const updateConfiguracaoFiscal = (payload) =>
  client.put('/configuracao-fiscal', payload).then((r) => r.data)
export const uploadCertificado = (formData) =>
  client.post('/configuracao-fiscal/certificado', formData).then((r) => r.data)

export const listMovimentacoes = (produtoId) =>
  client.get(`/produtos/${produtoId}/movimentacoes`).then((r) => r.data)
export const criarMovimentacao = (produtoId, payload) =>
  client.post(`/produtos/${produtoId}/movimentacoes`, payload).then((r) => r.data)
