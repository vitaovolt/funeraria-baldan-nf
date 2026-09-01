/**
 * Barra de busca + paginação para listas CRUD.
 */
export function SearchBar({ value, onChange, placeholder = 'Pesquisar…', testId, onKeyDown, inputRef }) {
  return (
    <div className="search-bar">
      <input
        ref={inputRef}
        type="search"
        placeholder={placeholder}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        onKeyDown={onKeyDown}
        data-testid={testId}
        aria-label={placeholder}
      />
    </div>
  )
}

export function Pagination({ meta, onPageChange }) {
  if (!meta || meta.last_page <= 1) {
    return meta?.total != null ? (
      <p className="list-meta hint">{meta.total} registro{meta.total === 1 ? '' : 's'}</p>
    ) : null
  }

  const page = meta.current_page
  const last = meta.last_page

  return (
    <div className="pagination" data-testid="pagination">
      <span className="hint">
        Página {page} de {last} · {meta.total} registro{meta.total === 1 ? '' : 's'}
      </span>
      <div className="pagination-actions">
        <button
          type="button"
          className="btn btn-ghost"
          disabled={page <= 1}
          onClick={() => onPageChange(page - 1)}
          data-testid="pagination-prev"
        >
          Anterior
        </button>
        <button
          type="button"
          className="btn btn-ghost"
          disabled={page >= last}
          onClick={() => onPageChange(page + 1)}
          data-testid="pagination-next"
        >
          Próxima
        </button>
      </div>
    </div>
  )
}
