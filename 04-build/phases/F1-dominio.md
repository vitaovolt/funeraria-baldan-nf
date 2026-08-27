# F1 Domínio + dados

**Objetivo:** Migrations, models PT, seed e API CRUD dos cadastros mestres (sem fluxo de venda/caixa).

## Escopo entregue

| Recurso | Tabelas / API |
|---------|----------------|
| Config fiscal | `configuracoes_fiscais` · `GET/PUT /api/v1/configuracao-fiscal` |
| Marcas | CRUD `/api/v1/marcas` |
| Categorias | CRUD `/api/v1/categorias` |
| Produtos | CRUD `/api/v1/produtos` (+ `q`, `categoria_id`, `marca_id`, `ativo`) |
| Clientes | CRUD `/api/v1/clientes` (titular + flag plano) |
| Dependentes | nested `/clientes/{id}/dependentes` + `/dependentes/{id}` |
| Estoque manual | `POST/GET /produtos/{id}/movimentacoes` (Action `RegistrarMovimentacaoEstoque`) |

**Fora do F1 (F3+):** sessões de caixa, vendas, consignado, emissão NFC-e.

## Arquivos

- `code/backend/database/migrations/2026_08_26_170000_create_dominio_baldan_tables.php`
- `code/backend/app/Models/*` (PT)
- `code/backend/app/Actions/RegistrarMovimentacaoEstoque.php`
- `code/backend/app/Http/Controllers/Api/*`
- `code/backend/docs/openapi.yaml`
- `code/frontend/src/api/dominio.js`

## Critério de done

- [x] Escopo da fase implementado
- [x] Suite E2E da fase **verde** (agente executou)
- [x] Roteiro manual preenchido (seeds, URLs, passos)
- [x] OpenAPI atualizado
- [x] LESSONS.md da fase + sync KB
- [x] Índices nas colunas de filtro/ordem reais
- [x] CRUD sem regra: sem DTO/VO/Repository; estoque = Action

## Packs nesta fase

| Pack | Impacto F1 |
|------|------------|
| fiscal | Tabela + API config A1/NFC-e (sem emitir) |
| queues | Sem job ainda (base F0); retry NFC-e na F4 |
| files | Certificado path metadata (arquivo na F4/F6) |

## Suite E2E (automática) — gate

| # | Cenário | Arquivo | OK? |
|---|---------|---------|-----|
| 1 | Health + CORS + schema F1 | Health / Cors / BootstrapSchema | [x] |
| 2 | 401 sem token | DominioApiTest | [x] |
| 3 | CRUD marca/categoria/produto + filtro | DominioApiTest | [x] |
| 4 | Barcode/documento duplicado 422 | DominioApiTest | [x] |
| 5 | Cliente + dependentes | DominioApiTest | [x] |
| 6 | Movimentação altera `estoque_atual` | DominioApiTest | [x] |
| 7 | Config fiscal GET/PUT | DominioApiTest | [x] |
| 8 | Seed Baldan | DominioApiTest | [x] |

**Comandos:**

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan test --filter="HealthTest|BootstrapSchemaTest|CorsBootstrapTest|DominioApiTest"
```

**Resultado:** 11 passed / 0 failed (92 assertions).

## Como testar manualmente (só após E2E verde)

### O que é o smoke nesta fase

Só **API** (ainda sem telas de cadastro). Confirmar seed + token + listagens autenticadas.

### Preparar

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan migrate:fresh --seed --force
php artisan serve
```

Em outro terminal, gerar token:

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan baldan:token
```

| Item | Valor |
|------|--------|
| URL API | http://localhost:8000/api/v1 |
| Usuário seed | `operador@baldan.local` |
| Senha seed | `password` (login na F2; smoke F1 usa token) |

### Passos

1. Guardar o token do `baldan:token` em `$t`.
2. `Invoke-RestMethod -Headers @{ Authorization = "Bearer $t"; Accept = "application/json" } http://localhost:8000/api/v1/produtos`
3. Esperado: `success=true` e lista com ≥ 8 produtos (ex.: Muleta, Urna).
4. Mesmo header em `/api/v1/clientes` — Maria Silva com `tem_plano=true` e dependentes.
5. `GET /api/v1/configuracao-fiscal` — razão social Baldan, ambiente homologacao.
6. Sem token: `GET /api/v1/produtos` → **401** (não 500).

### Checklist

- [x] Seed + token OK
- [x] Produtos e clientes listam com Bearer
- [x] Config fiscal presente
- [x] 401 sem token

**Smoke operador:** OK (26/08/2026) — gate F1 fechado.
