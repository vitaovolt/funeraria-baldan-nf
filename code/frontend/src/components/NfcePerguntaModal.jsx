import { useEffect, useState } from 'react'
import { consultarCnpj } from '../api/cnpj'
import { maskCpfCnpj, soDigitos } from '../utils/impressao'

export default function NfcePerguntaModal({
  open,
  documento,
  onDocumento,
  submitting,
  onConfirmar,
  onCancelar,
}) {
  const [consultando, setConsultando] = useState(false)
  const [erroConsulta, setErroConsulta] = useState('')

  useEffect(() => {
    if (!open) return undefined
    const digitos = soDigitos(documento)
    if (digitos.length !== 14) {
      setConsultando(false)
      setErroConsulta('')
      return undefined
    }

    let ativo = true
    setConsultando(true)
    setErroConsulta('')
    consultarCnpj(digitos)
      .catch(() => {
        if (ativo) setErroConsulta('Não foi possível consultar este CNPJ.')
      })
      .finally(() => {
        if (ativo) setConsultando(false)
      })

    return () => {
      ativo = false
    }
  }, [documento, open])

  if (!open) return null

  return (
    <div className="modal-backdrop" role="presentation" onMouseDown={submitting ? undefined : onCancelar}>
      <div
        className="modal-card panel"
        role="dialog"
        aria-labelledby="nfce-titulo"
        data-testid="modal-nfce"
        onMouseDown={(e) => e.stopPropagation()}
      >
        <h2 id="nfce-titulo">Gerar NFC-e?</h2>
        <p className="hint">
          A NFC-e é a nota oficial da SEFAZ. Se escolher não gerar, sai só o comprovante da venda (sem valor
          fiscal).
        </p>
        <div className="field">
          <label htmlFor="cpf-nfce">CPF/CNPJ do consumidor (opcional)</label>
          <div className="cnpj-linha">
            <input
              id="cpf-nfce"
              value={documento}
              onChange={(e) => onDocumento(maskCpfCnpj(e.target.value))}
              placeholder="Deixe em branco para consumidor não identificado"
              autoComplete="off"
              disabled={submitting}
              data-testid="cpf-nfce"
            />
            {consultando ? <span className="cnpj-spinner" role="status" aria-label="Consultando CNPJ" /> : null}
          </div>
          {erroConsulta ? <p className="hint">{erroConsulta}</p> : null}
        </div>
        <div className="modal-actions">
          <button
            type="button"
            className="btn btn-accent w-full"
            disabled={submitting || consultando}
            onClick={() => onConfirmar(true, soDigitos(documento))}
            data-testid="nfce-sim"
          >
            {submitting ? 'Processando…' : 'Sim, gerar NFC-e'}
          </button>
          <button
            type="button"
            className="btn btn-primary w-full"
            disabled={submitting}
            onClick={() => onConfirmar(false, soDigitos(documento))}
            data-testid="nfce-nao"
          >
            {submitting ? 'Processando…' : 'Não, só comprovante'}
          </button>
          <button type="button" className="btn btn-ghost w-full" disabled={submitting} onClick={onCancelar}>
            Voltar
          </button>
        </div>
      </div>
    </div>
  )
}
