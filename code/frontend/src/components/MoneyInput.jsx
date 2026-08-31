import { maskBrlInput, parseBrl } from '../utils/moneyMask'

/**
 * Campo dinheiro pt-BR (centavos + R$). Valor controlado = string mascarada ou ''.
 * onValueChange(number|null) — número decimal para API; null se vazio.
 */
export default function MoneyInput({
  id,
  value,
  onChange,
  onValueChange,
  placeholder = '0,00',
  disabled = false,
  'data-testid': testId,
  className = '',
}) {
  function handleChange(e) {
    const masked = maskBrlInput(e.target.value)
    onChange?.(masked)
    onValueChange?.(parseBrl(masked))
  }

  return (
    <div className={`money-input ${className}`.trim()}>
      <span className="money-prefix" aria-hidden="true">
        R$
      </span>
      <input
        id={id}
        type="text"
        inputMode="numeric"
        autoComplete="off"
        placeholder={placeholder}
        value={value ?? ''}
        onChange={handleChange}
        disabled={disabled}
        data-testid={testId}
      />
    </div>
  )
}
