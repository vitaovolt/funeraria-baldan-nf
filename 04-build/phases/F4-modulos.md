# F4 Módulos

**Objetivo:** Telas T7–T14 e T16 (mapa-telas) + completar caixa (sangria/fechar/imprimir) e consignado.

## Escopo entregue

| Módulo | API | UI |
|--------|-----|-----|
| T7 Sangria + T3 fechar/imprimir | `POST /caixa/sangria`, `POST /caixa/fechar`, `GET /caixa/fechamento` | CaixaPage |
| T8–T10 Produtos / marcas / cats | CRUD F1 | Produtos + MarcasCategorias |
| T11–T12 Clientes + deps | CRUD F1 | Clientes + form |
| T13 Consignado | criar / devolver / converter (+ NFC-e job) | ConsignadoPage |
| T14 Estoque | movimentações F1 | EstoquePage |
| T16 Config + A1 | PUT config + `POST …/certificado` (files) | ConfigPage |

## Arquivos

- Migration `2026_08_26_190000_create_f4_caixa_consignado_tables.php`
- Actions: `RegistrarSangria`, `FecharSessaoCaixa`, `CriarConsignado`, `DevolverConsignado`, `ConverterConsignadoEmVenda`
- Controllers: Caixa (estendido), Consignado, Config (upload A1)
- FE: páginas de módulos + `e2e/modules.spec.js`
- `tests/Feature/ModulosF4Test.php`

## Critério de done

- [x] Escopo da fase implementado
- [x] Suite E2E da fase **verde** (agente executou)
- [x] Roteiro manual preenchido
- [x] OpenAPI atualizado
- [x] LESSONS.md da fase + sync KB
- [x] Feature afirma efeito (DB / Storage / Queue)
- [x] Listagens com `with()`
- [x] UX: CTA verbo, toast, “Processando…”

## Suite E2E (automática) — gate

| # | Cenário | Arquivo | OK? |
|---|---------|---------|-----|
| 1 | Sangria + fechar + fechamento | ModulosF4Test | [x] |
| 2 | Consignado criar/devolver/converter + estoque | ModulosF4Test | [x] |
| 3 | Upload A1 em Storage | ModulosF4Test | [x] |
| 4 | 401 sem token | ModulosF4Test | [x] |
| 5 | Playwright módulos T7–T16 | e2e/modules.spec.js | [x] |
| 6 | Regressão PDV + F0–F3 | pdv.spec + PHP suite | [x] |

**Comandos:**

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan test --filter="AuthTest|HealthTest|DominioApiTest|BootstrapSchemaTest|CorsBootstrapTest|PdvSliceTest|ModulosF4Test"

cd ..\frontend
npx playwright test
```

**Resultado:** PHP **24 passed** / 0 failed; Playwright **2 passed**.

## Como testar manualmente (só após E2E verde)

### O que é o smoke nesta fase

**UI dos módulos:** cadastros, estoque, consignado, sangria/fechar caixa, config A1.

### Preparar

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan migrate:fresh --seed --force
php artisan serve
# se QUEUE_CONNECTION=database:
php artisan queue:work --queue=fiscal,default
```

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

1. **Produtos / Cadastros / Clientes** — listar, criar um item, Editar/Excluir visíveis.
2. **Estoque** — entrada em um produto; saldo muda.
3. **Caixa** — abrir → sangria → (opcional venda) → fechar → imprimir fechamento.
4. **Consignado** — criar com cliente+produto → devolver **ou** virar venda (caixa aberto).
5. **Config** — salvar razão social; enviar arquivo `.pfx` fake (armazenamento local).

### Checklist

- [x] Cadastros CRUD OK
- [x] Estoque ajuste OK
- [x] Sangria + fechar + imprimir
- [x] Consignado devolver/converter
- [x] Config + A1

**Smoke operador:** OK (26/08/2026) — gate F4 fechado.
