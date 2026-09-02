import client from './client'

export const getCaixaAtual = () => client.get('/caixa/atual').then((r) => r.data)
export const abrirCaixa = (valor_abertura = 0) =>
  client.post('/caixa/abrir', { valor_abertura }).then((r) => r.data)
export const registrarSangria = (payload) => client.post('/caixa/sangria', payload).then((r) => r.data)
export const registrarSuprimento = (payload) => client.post('/caixa/suprimento', payload).then((r) => r.data)
export const fecharCaixa = () => client.post('/caixa/fechar').then((r) => r.data)
export const getFechamento = () => client.get('/caixa/fechamento').then((r) => r.data)
export const listVendasDoDia = (params) =>
  client.get('/caixa/vendas-do-dia', { params }).then((r) => r.data)
export const finalizarVenda = (payload, { idempotencyKey } = {}) =>
  client
    .post('/vendas/finalizar', payload, {
      headers: idempotencyKey ? { 'Idempotency-Key': idempotencyKey } : undefined,
      timeout: 120000,
    })
    .then((r) => r.data)
export const getVenda = (id) => client.get(`/vendas/${id}`).then((r) => r.data)
export const listVendas = (params) => client.get('/vendas', { params }).then((r) => r.data)
export const emitirNfceVenda = (id, payload = {}) =>
  client.post(`/vendas/${id}/emitir-nfce`, payload, { timeout: 120000 }).then((r) => r.data)
export const emitirNfeVenda = (id, payload = {}) =>
  client.post(`/vendas/${id}/emitir-nfe`, payload, { timeout: 120000 }).then((r) => r.data)
export const listNotasNfce = (params) => client.get('/notas-nfce', { params }).then((r) => r.data)
export const reemitirNotaNfce = (id) =>
  client.post(`/notas-nfce/${id}/reemitir`, {}, { timeout: 120000 }).then((r) => r.data)
export const baixarXmlNota = (id) =>
  client.get(`/notas-nfce/${id}/xml`, { responseType: 'blob' }).then((r) => r.data)
