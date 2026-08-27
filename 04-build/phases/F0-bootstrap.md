# F0 Bootstrap

**Objetivo:** Backend Laravel API + frontend SPA. CORS, Sanctum, OpenAPI, banco PostgreSQL via script.

## Automação (agente)

```powershell
# Se ensure-pgsql-db.ps1 falhar com ParserError no PS5, criar DB à mão:
# CREATE DATABASE funeraria_baldan_nf; CREATE DATABASE funeraria_baldan_nf_testing;
powershell -File educraft-devkit/scripts/ensure-pgsql-db.ps1 -BackendPath "educraft-devkit/projects/funeraria-baldan-nf/code/backend"
cd educraft-devkit/projects/funeraria-baldan-nf/code/backend
php artisan migrate --force
php artisan test --filter="HealthTest|BootstrapSchemaTest|CorsBootstrapTest"
cd ../frontend
npm run build
```

## Arquivos

- `code/backend/` — Laravel 12 API, Sanctum, `QUEUE_CONNECTION=database`, disco `local` (files)
- `code/frontend/` — React + Vite + Tailwind, página Bootstrap + proxy `/api`

## Critério de done

- [x] Escopo da fase implementado
- [x] Suite E2E da fase **verde** (agente executou)
- [x] Roteiro manual preenchido (seeds, URLs, passos)
- [x] OpenAPI atualizado (`docs/openapi.yaml` — `/health`)
- [x] LESSONS.md da fase + sync KB

## Packs nesta fase

| Pack | O que entrou no F0 |
|------|--------------------|
| queues | `QUEUE_CONNECTION=database` + tabelas `jobs` / `job_batches` / `failed_jobs` |
| files | `FILESYSTEM_DISK=local` (S3 na F6); pasta storage pronta |
| fiscal | Sem emissão ainda — só base API para NFC-e nas fases seguintes |

## Suite E2E (automática) — gate

Regra: `educraft-devkit/standards/TESTES-FASE.md`

| # | Cenário | Arquivo de teste | OK? |
|---|---------|------------------|-----|
| 1 | `GET /api/v1/health` envelope + headers | `tests/Feature/HealthTest.php` | [x] |
| 2 | Schema base (users, jobs, tokens, cache) | `tests/Feature/BootstrapSchemaTest.php` | [x] |
| 3 | CORS preflight 204 + prod sem FRONTEND_URL | `tests/Feature/CorsBootstrapTest.php` | [x] |
| 4 | Frontend build (`npm run build`) | — | [x] |

**Comandos (agente rodou):**

```bash
cd code/backend && php artisan test --filter="HealthTest|BootstrapSchemaTest|CorsBootstrapTest"
cd code/frontend && npm run build
```

**Resultado:** 4 passed / 0 failed (29 assertions); FE build OK.

## Como testar manualmente (só após E2E verde)

### O que é o smoke nesta fase

Confirmar que API e SPA sobem no PC e o health aparece na tela Bootstrap.

### Preparar

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan migrate --force
php artisan serve
```

Em outro terminal:

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\frontend
npm run dev
```

| Item | Valor |
|------|--------|
| URL API | http://localhost:8000/api/v1/health |
| URL FE | http://localhost:5173 |
| Usuário seed | — (F0 sem auth) |
| Senha seed | — |

### Passos

1. Abrir http://localhost:8000/api/v1/health no browser ou `curl`.
2. Esperado: JSON com `success: true`, `data.service: funeraria-baldan-nf-api`, `checks.database: ok`.
3. Abrir http://localhost:5173.
4. Esperado: página “Baldan NF” com bloco “Bootstrap OK” e o mesmo JSON.
5. Se a API estiver down: mensagem “Não foi possível falar com a API…”.

### Checklist

- [x] Health 200 com database ok
- [x] SPA mostra Bootstrap OK
- [x] Sem erro de CORS no DevTools (Network → health)

**Smoke operador:** OK (26/08/2026) — gate F0 fechado.
