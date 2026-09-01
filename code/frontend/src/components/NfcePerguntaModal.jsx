import { maskCpfCnpj, soDigitos } from '../utils/impressao'

export default function NfcePerguntaModal({
  open,
  documento,
  onDocumento,
  submitting,
  onConfirmar,
  onCancelar,
}) {
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
          <input
            id="cpf-nfce"
            value={documento}
            onChange={(e) => onDocumento(maskCpfCnpj(e.target.value))}
            placeholder="Deixe em branco para consumidor não identificado"
            autoComplete="off"
            disabled={submitting}
            data-testid="cpf-nfce"
          />
        </div>
        <div className="modal-actions">
          <button
            type="button"
            className="btn btn-accent w-full"
            disabled={submitting}
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
