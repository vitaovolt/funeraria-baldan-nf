import client, { getAuthToken } from '../api/client'

function apiBase() {
  const base = import.meta.env.VITE_API_URL || '/api/v1'
  if (base.startsWith('http')) {
    return base.replace(/\/$/, '')
  }
  const prefix = base.startsWith('/') ? base : `/${base}`
  return `${window.location.origin}${prefix}`.replace(/\/$/, '')
}

export async function abrirHtmlImpressao(path) {
  const res = await fetch(`${apiBase()}${path.startsWith('/') ? path : `/${path}`}`, {
    headers: {
      Accept: 'text/html',
      Authorization: getAuthToken() ? `Bearer ${getAuthToken()}` : '',
    },
  })
  if (!res.ok) {
    throw new Error('Não foi possível abrir a impressão.')
  }
  const html = await res.text()
  const janela = window.open('', '_blank', 'width=420,height=640')
  if (!janela) {
    throw new Error('Permita pop-ups para imprimir o comprovante.')
  }
  janela.document.open()
  janela.document.write(html)
  janela.document.close()
}

export async function abrirDanfe(notaId) {
  const res = await client.get(`/notas-nfce/${notaId}/danfe`, { responseType: 'blob' })
  const tipo = res.headers['content-type'] || 'application/pdf'
  const blob = new Blob([res.data], { type: tipo })
  const url = URL.createObjectURL(blob)
  const janela = window.open(url, '_blank')
  if (!janela) {
    URL.revokeObjectURL(url)
    throw new Error('Permita pop-ups para imprimir a NFC-e.')
  }
  janela.addEventListener('load', () => {
    try {
      janela.focus()
      janela.print()
    } catch {
      /* navegador bloqueou print automático */
    }
  })
}

export async function imprimirPosVenda(venda) {
  const nota = venda?.nota_nfce
  if (nota?.status === 'autorizada' && nota.id) {
    await abrirDanfe(nota.id)
    return
  }
  await abrirHtmlImpressao(`/vendas/${venda.id}/comprovante`)
}

export function soDigitos(valor) {
  return String(valor || '').replace(/\D/g, '')
}

export function maskCpfCnpj(raw) {
  const d = soDigitos(raw).slice(0, 14)
  if (d.length <= 11) {
    return d
      .replace(/^(\d{3})(\d)/, '$1.$2')
      .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
      .replace(/\.(\d{3})(\d)/, '.$1-$2')
  }
  return d
    .replace(/^(\d{2})(\d)/, '$1.$2')
    .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
    .replace(/\.(\d{3})(\d)/, '.$1/$2')
    .replace(/(\d{4})(\d)/, '$1-$2')
}
