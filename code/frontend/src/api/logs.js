import client from './client'

export const listLogsAcoes = (params) => client.get('/logs-acoes', { params }).then((r) => r.data)
