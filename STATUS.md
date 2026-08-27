# STATUS — `funeraria-baldan-nf`

| Campo | Valor |
|-------|--------|
| slug | `funeraria-baldan-nf` |
| etapa_atual | `e4` |
| fase_e4 | `F6` |
| status | `aguardando_aprovacao` |
| proximo_passo | Confirmar smoke no domínio → fechar gate E4 (commit/push do código ainda pendente para CI) |
| skill | `educraft-dev-e4-build` |

## Capacidades ativas

Ver [CAPABILITIES.md](CAPABILITIES.md). Resumo: **PDV + estoque + clientes + consignado + NFC-e**.

| Pack | Status |
|------|--------|
| fiscal | sim |
| integrations | nao |
| queues | sim |
| files | sim |

## Gate E1–E3 / F0–F5

- [x] Fechados (F5 em 27/08/2026)

## Gate atual (E4 / F6)

- [x] Deploy + workflows + handoff
- [x] E2E da fase verde (PHP 34 + Playwright 3)
- [x] Roteiro “Como testar manualmente”
- [x] LESSONS + sync
- [x] App no ar em `https://funerariabaldan.educraft.com.br` (health + HTTPS)
- [x] Runner self-hosted + Deploy Key + `DEPLOY_PATH`
- [ ] Smoke operador (login SPA) + commit/push do código para o loop CI/CD

## Links úteis

- Fase F6: `04-build/phases/F6-deploy.md`
- Deploy: `docs/DEPLOY.md`
- FE local: http://localhost:5173/login
- Operador: `operador@baldan.local` / `password`
- Admin: `admin@baldan.local` / `password`
- GitHub: https://github.com/vitaovolt/funeraria-baldan-nf

## Histórico rápido

| Data | Evento |
|------|--------|
| 27/08/2026 | **Smoke F5 OK · gate F5 fechado** → F6 |
| 27/08/2026 | F6 deploy self-hosted + handoff; PHP 34 + Playwright 3; `aguardando_aprovacao` |
| 27/08/2026 | Bootstrap EC2 `34.224.58.173` + HTTPS `funerariabaldan.educraft.com.br`; health ok |
