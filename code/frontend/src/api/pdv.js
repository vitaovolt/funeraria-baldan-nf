import client from './client'

export const getCaixaAtual = () => client.get('/caixa/atual').then((r) => r.data)
export const abrirCaixa = (valor_abertura = 0) =>
  client.post('/caixa/abrir', { valor_abertura }).then((r) => r.data)
export const registrarSangria = (payload) => client.post('/caixa/sangria', payload).then((r) => r.data)
export const fecharCaixa = () => client.post('/caixa/fechar').then((r) => r.data)
export const getFechamento = () => client.get('/caixa/fechamento').then((r) => r.data)
export const listVendasDoDia = () => client.get('/caixa/vendas-do-dia').then((r) => r.data)
export const finalizarVenda = (payload, { idempotencyKey } = {}) =>
  client
    .post('/vendas/finalizar', payload, {
      headers: idempotencyKey ? { 'Idempotency-Key': idempotencyKey } : undefined,
    })
    .then((r) => r.data)
export const getVenda = (id) => client.get(`/vendas/${id}`).then((r) => r.data)
export const listNotasNfce = (params) => client.get('/notas-nfce', { params }).then((r) => r.data)
