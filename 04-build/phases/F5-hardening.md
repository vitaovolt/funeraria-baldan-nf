# F5 Hardening

**Objetivo:** Checklist segurança na API (policies, rate limit, CORS prod), submit único e idempotência em mutações críticas.

## Escopo entregue

| Item | Detalhe |
|------|---------|
| User | `ativo` + `role` (`operador`\|`admin`); login bloqueia inativo |
| Middleware | `EnsureUserAtivo` → 403 + revoga token |
| Policy | `ConfiguracaoFiscalPolicy` — GET qualquer ativo; PUT/upload só admin |
| Idempotência | Header `Idempotency-Key` em `POST /vendas/finalizar` + unique + lock |
| Caixa | `AbrirSessaoCaixa` com `lockForUpdate` + unique index 1 caixa aberta (PG) |
| Rate limit | `mutations` 30/min em prod (caixa/venda/consignado) |
| CORS | `Idempotency-Key` permitido; prod fail-closed sem `FRONTEND_URL` |
| Headers | SecurityHeaders (já F0) + handler 403 JSON |
| FE | PDV/Login `useRef` submit único; Config só escrita para admin |
| Seed | `operador@baldan.local` + `admin@baldan.local` / `password` |

## Arquivos

- `database/migrations/2026_08_27_080000_f5_hardening_users_idempotency.php`
- `app/Policies/ConfiguracaoFiscalPolicy.php`
- `app/Http/Middleware/EnsureUserAtivo.php`
- `app/Actions/FinalizarVenda.php`, `AbrirSessaoCaixa.php`
- `tests/Feature/HardeningF5Test.php`
- `e2e/hardening.spec.js`

## Critério de done

- [x] Escopo da fase implementado
- [x] Suite E2E da fase **verde** (agente executou)
- [x] Roteiro manual preenchido
- [x] OpenAPI atualizado
- [x] LESSONS.md da fase + sync KB
- [x] Submits mutáveis com guarda anti-reenvio (FE + BE idempotência venda)
- [x] Camadas: `auth:sanctum` + Policy + `EnsureUserAtivo`
- [x] Feature: 401 anônimo + 403 policy/inativo

## Suite E2E (automática) — gate

| # | Cenário | Arquivo | OK? |
|---|---------|---------|-----|
| 1 | Login inativo 422 sem token | HardeningF5Test | [x] |
| 2 | Token + conta inativa → 403 | HardeningF5Test | [x] |
| 3 | Operador 403 em PUT/upload config | HardeningF5Test | [x] |
| 4 | Admin altera config | HardeningF5Test | [x] |
| 5 | Idempotency-Key não duplica venda/estoque | HardeningF5Test | [x] |
| 6 | 401 mutações + security headers | HardeningF5Test | [x] |
| 7 | Playwright: config leitura + PDV | e2e/hardening.spec.js | [x] |
| 8 | Regressão modules + pdv | Playwright | [x] |

**Comandos:**

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan test --filter="HardeningF5Test|AuthTest|DominioApiTest|PdvSliceTest|ModulosF4Test|HealthTest|CorsBootstrapTest|BootstrapSchemaTest"

cd ..\frontend
npx playwright test
```

**Resultado:** PHP **31 passed** / 0 failed; Playwright **3 passed**.

## Como testar manualmente (só após E2E verde)

### O que é o smoke nesta fase

**API + UI de segurança:** roles, config admin-only, submit único no PDV, sem venda duplicada.

### Preparar

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan migrate:fresh --seed --force
php artisan serve
```

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\frontend
npm run dev
```

| Item | Valor |
|------|--------|
| URL FE | http://localhost:5173/login |
| Operador | `operador@baldan.local` / `password` |
| Admin | `admin@baldan.local` / `password` |

### Passos

1. Login **operador** → Config: mensagem somente leitura; sem botão Salvar.
2. Caixa → abrir → PDV → Muleta → Pagar: botão “Processando…”; uma venda; toast OK.
3. Duplo clique rápido no Pagar (nova venda): não deve criar duas (ref + Idempotency-Key).
4. Sair → login **admin** → Config: salvar município + enviar `.pfx` fake OK.
5. Opcional API: `PUT /configuracao-fiscal` com token operador → 403.

### Checklist

- [x] Operador: config leitura
- [x] Admin: config + A1
- [x] PDV submit único / uma NFC-e
- [x] 403 operador na API de config (opcional)

**Smoke operador:** OK (27/08/2026) — gate F5 fechado.
