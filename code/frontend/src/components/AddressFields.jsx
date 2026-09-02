import { useRef, useState } from 'react'
import { consultarCep } from '../api/cep'
import { maskCep, soDigitosCep } from '../utils/cepMask'

/**
 * Endereço BR com ViaCEP (padrão Educraft).
 * mode=full → cep + logradouro/número/complemento/bairro/cidade/uf
 * mode=localidade → cep + município/uf (+ codigo_ibge se includeCodigoIbge)
 */
export default function AddressFields({
  values,
  onChange,
  disabled = false,
  mode = 'full',
  includeCodigoIbge = false,
  onLookupError,
  onLookupSuccess,
}) {
  const buscandoRef = useRef(false)
  const [buscando, setBuscando] = useState(false)
  const [hint, setHint] = useState('')
  const cidadeKey = Object.prototype.hasOwnProperty.call(values, 'municipio') ? 'municipio' : 'cidade'

  function campo(nome, valor) {
    onChange(nome, valor)
  }

  async function buscar(cepOverride) {
    if (buscandoRef.current || disabled) return
    const cep = soDigitosCep(cepOverride ?? values.cep)
    if (cep.length !== 8) {
      setHint('Informe um CEP com 8 dígitos.')
      return
    }
    buscandoRef.current = true
    setBuscando(true)
    setHint('Consultando CEP…')
    try {
      const res = await consultarCep(cep)
      const data = res.data || {}
      campo('cep', maskCep(data.cep || cep))
      if (data.uf) campo('uf', data.uf)
      if (data.cidade) campo(cidadeKey, data.cidade)
      if (mode === 'full') {
        if (data.logradouro) campo('logradouro', data.logradouro)
        if (data.bairro) campo('bairro', data.bairro)
        if (data.complemento && !values.complemento) campo('complemento', data.complemento)
      }
      if (includeCodigoIbge && data.codigo_ibge) {
        campo('codigo_ibge', data.codigo_ibge)
      }
      setHint('Endereço preenchido pelo CEP.')
      onLookupSuccess?.(data)
    } catch (err) {
      const msg = err.response?.data?.message || 'CEP não encontrado.'
      setHint(msg)
      onLookupError?.(msg)
    } finally {
      buscandoRef.current = false
      setBuscando(false)
    }
  }

  function onCepChange(raw) {
    const masked = maskCep(raw)
    campo('cep', masked)
    setHint('')
    if (soDigitosCep(masked).length === 8) {
      void buscar(masked)
    }
  }

  return (
    <div className="grid gap-4 md:grid-cols-2" data-testid="address-fields">
      <div className="field" style={{ margin: 0 }}>
        <label htmlFor="addr-cep">CEP</label>
        <div className="flex gap-2">
          <input
            id="addr-cep"
            value={values.cep || ''}
            onChange={(e) => onCepChange(e.target.value)}
            onBlur={() => {
              if (soDigitosCep(values.cep).length === 8) void buscar()
            }}
            disabled={disabled || buscando}
            placeholder="00000-000"
            inputMode="numeric"
            autoComplete="postal-code"
            data-testid="addr-cep"
          />
          <button
            type="button"
            className="btn btn-ghost"
            style={{ whiteSpace: 'nowrap' }}
            disabled={disabled || buscando || soDigitosCep(values.cep).length !== 8}
            onClick={buscar}
            data-testid="addr-cep-buscar"
          >
            {buscando ? '…' : 'Buscar'}
          </button>
        </div>
        {hint ? <p className="hint m-0 mt-1">{hint}</p> : (
          <p className="hint m-0 mt-1">Ao informar o CEP, o endereço é preenchido automaticamente (ViaCEP).</p>
        )}
      </div>

      {mode === 'full' ? (
        <>
          <div className="field" style={{ margin: 0 }}>
            <label htmlFor="addr-logradouro">Logradouro</label>
            <input
              id="addr-logradouro"
              value={values.logradouro || ''}
              onChange={(e) => campo('logradouro', e.target.value)}
              disabled={disabled}
              autoComplete="address-line1"
            />
          </div>
          <div className="field" style={{ margin: 0 }}>
            <label htmlFor="addr-numero">Número</label>
            <input
              id="addr-numero"
              value={values.numero || ''}
              onChange={(e) => campo('numero', e.target.value)}
              disabled={disabled}
            />
          </div>
          <div className="field" style={{ margin: 0 }}>
            <label htmlFor="addr-complemento">Complemento</label>
            <input
              id="addr-complemento"
              value={values.complemento || ''}
              onChange={(e) => campo('complemento', e.target.value)}
              disabled={disabled}
            />
          </div>
          <div className="field" style={{ margin: 0 }}>
            <label htmlFor="addr-bairro">Bairro</label>
            <input
              id="addr-bairro"
              value={values.bairro || ''}
              onChange={(e) => campo('bairro', e.target.value)}
              disabled={disabled}
            />
          </div>
        </>
      ) : null}

      <div className="field" style={{ margin: 0 }}>
        <label htmlFor="addr-cidade">{cidadeKey === 'municipio' ? 'Município' : 'Cidade'}</label>
        <input
          id="addr-cidade"
          value={values[cidadeKey] || ''}
          onChange={(e) => campo(cidadeKey, e.target.value)}
          disabled={disabled}
        />
      </div>
      <div className="field" style={{ margin: 0 }}>
        <label htmlFor="addr-uf">UF</label>
        <input
          id="addr-uf"
          value={values.uf || ''}
          maxLength={2}
          onChange={(e) => campo('uf', e.target.value.toUpperCase())}
          disabled={disabled}
        />
      </div>
      {includeCodigoIbge ? (
        <div className="field" style={{ margin: 0 }}>
          <label htmlFor="addr-ibge">Código IBGE</label>
          <input
            id="addr-ibge"
            value={values.codigo_ibge || ''}
            onChange={(e) => campo('codigo_ibge', e.target.value.replace(/\D/g, '').slice(0, 7))}
            disabled={disabled}
            placeholder="3517306"
          />
        </div>
      ) : null}
    </div>
  )
}
