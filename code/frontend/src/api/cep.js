import client from './client'

/** @returns {Promise<{ data: { cep: string, logradouro?: string, complemento?: string, bairro?: string, cidade?: string, uf?: string, codigo_ibge?: string } }>} */
export const consultarCep = (cep) =>
  client.get(`/cep/${String(cep || '').replace(/\D/g, '')}`).then((r) => r.data)
