import client from './client'

export const listConsignados = (params) =>
  client.get('/consignados', { params }).then((r) => r.data)
export const createConsignado = (payload) =>
  client.post('/consignados', payload).then((r) => r.data)
export const getConsignado = (id) => client.get(`/consignados/${id}`).then((r) => r.data)
export const devolverConsignado = (id, itens) =>
  client.post(`/consignados/${id}/devolver`, { itens }).then((r) => r.data)
export const converterConsignado = (id, payload = {}) =>
  client.post(`/consignados/${id}/converter`, payload).then((r) => r.data)
