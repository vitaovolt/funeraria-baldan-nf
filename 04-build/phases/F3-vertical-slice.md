# F3 Vertical slice núcleo

**Objetivo:** fluxo feliz T3–T6 (mapa-telas): abrir caixa → PDV → pagar e emitir NFC-e (mock) + Playwright.

## Escopo entregue

| Item | Detalhe |
|------|---------|
| Caixa | `GET /caixa/atual`, `POST /caixa/abrir`, `GET /caixa/vendas-do-dia` |
| Venda | `POST /vendas/finalizar`, `GET /vendas/{venda}` — baixa estoque + cria nota + dispatch job |
| NFC-e | Job `EmitirNfceJob` (fila `fiscal`) + `EmissorNfceFake` (XML/DANFE em Storage) |
| FE | `/caixa`, `/pdv`, `/notas` + toast + AppShell; Home com atalhos |
| E2E UI | Playwright `e2e/pdv.spec.js` (QUEUE_CONNECTION=sync no serve) |

**Fora do F3 (F4+):** consignado, sangria, fechar caixa/imprimir, NFC-e real SEFAZ, cadastros UI.

## Arquivos

- `code/backend/database/migrations/2026_08_26_180000_create_pdv_slice_tables.php`
- `code/backend/app/Actions/AbrirSessaoCaixa.php`, `FinalizarVenda.php`
- `code/backend/app/Jobs/EmitirNfceJob.php`
- `code/backend/app/Services/Fiscal/EmissorNfceFake.php`
- `code/backend/app/Http/Controllers/Api/CaixaController.php`, `VendaController.php`, `NotaNfceController.php`
- `code/backend/tests/Feature/PdvSliceTest.php`
- `code/frontend/src/pages/CaixaPage.jsx`, `PdvPage.jsx`, `NotasPage.jsx`
- `code/frontend/e2e/pdv.spec.js`, `playwright.config.js`

## Critério de done

- [x] Escopo da fase implementado
- [x] Suite E2E da fase **verde** (agente executou)
- [x] Roteiro manual preenchido (seeds, URLs, passos)
- [x] OpenAPI atualizado
- [x] LESSONS.md da fase + sync KB
- [x] Feature do slice afirma efeito (DB / fake), não só HTTP 200
- [x] Listagens do slice com `with()` se houver relação
- [x] UX do slice: CTA verbo, toast, submit “Processando…”

## Suite E2E (automática) — gate

| # | Cenário | Arquivo | OK? |
|---|---------|---------|-----|
| 1 | Abrir caixa → finalizar venda → baixa estoque + nota autorizada | PdvSliceTest | [x] |
| 2 | Sem caixa / estoque insuficiente → erro | PdvSliceTest | [x] |
| 3 | `Queue::fake` + job na fila `fiscal` | PdvSliceTest | [x] |
| 4 | PDV exige auth (401) | PdvSliceTest | [x] |
| 5 | Playwright T3–T6 (login → caixa → PDV Muleta → pagar → notas) | e2e/pdv.spec.js | [x] |
| 6 | Regressão F0–F2 | Auth / Dominio / Health / Schema / Cors | [x] |

**Comandos:**

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan test --filter="AuthTest|HealthTest|DominioApiTest|BootstrapSchemaTest|CorsBootstrapTest|PdvSliceTest"

cd ..\frontend
npx playwright test
```

**Resultado:** PHP **20 passed** / 0 failed (159 assertions); Playwright **1 passed** (13.6s).

## Como testar manualmente (só após E2E verde)

### O que é o smoke nesta fase

**Fluxo feliz UI:** caixa → PDV → pagar/emitir → venda do dia + lista de notas com NFC-e autorizada (fake).

### Preparar

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan migrate:fresh --seed --force
php artisan serve
```

Se `.env` tiver `QUEUE_CONNECTION=database`, em **outro** terminal:

```powershell
php artisan queue:work --queue=fiscal,default
```

(Alternativa: `QUEUE_CONNECTION=sync` só no smoke, como o Playwright.)

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\frontend
npm run dev
```

| Item | Valor |
|------|--------|
| URL FE | http://localhost:5173/login |
| Usuário | `operador@baldan.local` |
| Senha | `password` |

### Passos

1. Login → Home.
2. **Caixa** → **Abrir caixa** (se fechado) → status “Caixa aberto”.
3. **Ir ao PDV** → buscar “Muleta” → adicionar → **Pagar e emitir NFC-e**.
4. Esperado: toast de sucesso; volta ao caixa com venda do dia **NFC-e autorizada**.
5. Menu **Notas** → nota autorizada listada.
6. Botões de abrir/pagar mostram “Processando…” e não duplicam submit.

### Checklist

- [x] Abrir caixa OK
- [x] PDV + pagar emite e toast
- [x] Vendas do dia / Notas com autorizada
- [x] Submit único (sem duplo clique efetivo)

**Smoke operador:** OK (26/08/2026) — gate F3 fechado.
