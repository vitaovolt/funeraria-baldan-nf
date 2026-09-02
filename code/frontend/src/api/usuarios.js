import client from './client'

export const listUsuarios = (params) => client.get('/usuarios', { params }).then((r) => r.data)
export const getUsuario = (id) => client.get(`/usuarios/${id}`).then((r) => r.data)
export const createUsuario = (payload) => client.post('/usuarios', payload).then((r) => r.data)
export const updateUsuario = (id, payload) => client.put(`/usuarios/${id}`, payload).then((r) => r.data)
export const deleteUsuario = (id) => client.delete(`/usuarios/${id}`).then((r) => r.data)
