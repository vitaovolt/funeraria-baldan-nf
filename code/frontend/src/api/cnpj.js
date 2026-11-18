import client from './client'

export const consultarCnpj = (cnpj) =>
  client.get(`/cnpj/${String(cnpj || '').replace(/\D/g, '')}`).then((r) => r.data)
