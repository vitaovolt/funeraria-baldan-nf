# F6 Deploy + handoff

**Objetivo:** Deploy API + FE (self-hosted), docs, ficha catalog, lições finais.

## Escopo entregue

| Item | Detalhe |
|------|---------|
| CI | `.github/workflows/ci.yml` — PHPUnit + build FE (já existente; validado) |
| Deploy | `.github/workflows/deploy.yml` — `self-hosted` + Deploy Key `~/.ssh/funeraria-baldan-nf_github` |
| Secrets | Só `DEPLOY_PATH` (obrigatório); `REPO_DEPLOY_KEY` opcional — **sem** `SSH_*` |
| Docs | `docs/DEPLOY.md`, `nginx-spa-api.conf.example`, `queue-worker.service.example` |
| Queue | Restart `funeraria-baldan-nf-queue` (fila `fiscal,default`) — packs fiscal/queues |
| Files | XML/DANFE em disco local (ou S3 se migrar) — pack files |
| Catalog | `educraft-devkit/catalog/funeraria-baldan-nf.md` + linha no INDEX |
| SES | N/A no MVP (`MAIL_MAILER=log`) |

## Critério de done

- [x] Escopo da fase implementado
- [x] Suite E2E da fase **verde** (agente executou)
- [x] Roteiro manual preenchido (operacional + regressão local)
- [x] OpenAPI N/A nesta fase (sem mudança de contrato)
- [x] LESSONS.md da fase + sync KB
- [x] Self-hosted + Deploy Key no disco ([DEPLOY-GITHUB.md](../../../../standards/DEPLOY-GITHUB.md))
- [x] Packs fiscal/queues/files refletidos no handoff (worker + storage)

## Suite E2E (automática) — gate

| # | Cenário | Arquivo | OK? |
|---|---------|---------|-----|
| 1 | ci.yml + deploy.yml self-hosted, KEYFILE, sem SSH_PRIVATE_KEY | DeployArtifactsTest | [x] |
| 2 | docs DEPLOY + nginx + queue-worker | DeployArtifactsTest | [x] |
| 3 | Health com `checks.database` | DeployArtifactsTest | [x] |
| 4 | Regressão API F0–F5 | PHPUnit filters | [x] |
| 5 | Regressão UI | Playwright (3 specs) | [x] |

**Comandos:**

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan test --filter="DeployArtifactsTest|HardeningF5Test|AuthTest|DominioApiTest|PdvSliceTest|ModulosF4Test|HealthTest|CorsBootstrapTest|BootstrapSchemaTest"

cd ..\frontend
npm run build
npx playwright test
```

**Resultado:** PHP **34 passed** / 0 failed; FE build OK; Playwright **3 passed**.

## Como testar manualmente (só após E2E verde)

### O que é o smoke nesta fase

**Operacional (EC2/GitHub)** — artefatos de deploy + health em produção. Setup EC2/runner é do operador; agente entrega workflows/docs + teste de artefatos.

### Checklist operacional (produção)

1. Secret `DEPLOY_PATH` no GitHub (ex.: `/var/www/funeraria-baldan-nf`).
2. Runner self-hosted **Idle** (`self-hosted`, `Linux`, `X64`) neste repo.
3. Chave `~/.ssh/funeraria-baldan-nf_github` + Deploy Key no GitHub.
4. `.env` prod em `code/backend` (`FRONTEND_URL`, DB, `QUEUE_CONNECTION`).
5. Nginx + systemd `funeraria-baldan-nf-queue` (ver `docs/`).
6. Push/`workflow_dispatch` → Actions Deploy success.
7. `curl -sS https://<host>/api/v1/health` → `checks.database` = `ok`.
8. Login SPA (operador/admin).

Detalhe: [docs/DEPLOY.md](../../docs/DEPLOY.md).

### Regressão local (opcional)

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\educraft-devkit\projects\funeraria-baldan-nf\code\backend
php artisan migrate:fresh --seed --force
php artisan serve
# outro terminal, se fila database:
php artisan queue:work --queue=fiscal,default
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

Fluxo feliz: Caixa → PDV → Pagar → NFC-e autorizada (job).

### Checklist

- [x] Secret `DEPLOY_PATH` + runner Idle/online
- [x] Deploy Key + KEYFILE no disco
- [x] Worker queue ativo
- [x] `curl` health OK em prod
- [ ] Login SPA pós-deploy (operador)
- [x] **Commit/push do código F0–F6** para `main` (CI/CD seguro)

**Smoke agente (27/08/2026):** health `checks.database=ok` em `https://funerariabaldan.educraft.com.br/api/v1/health`; SPA 200; HTTPS Let’s Encrypt; queue ativa; runner + Deploy Key + `DEPLOY_PATH` ok.

**Smoke operador:** login SPA no domínio (gate F6) — pendente confirmação.
