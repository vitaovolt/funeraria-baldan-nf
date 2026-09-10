# STATUS — `funeraria-baldan-nf`

| Campo | Valor |
|-------|--------|
| slug | `funeraria-baldan-nf` |
| etapa_atual | `e4` |
| fase_e4 | `F6` |
| status | `aguardando_aprovacao` |
| proximo_passo | Acompanhar o deploy e conferir NF-e de saída em `https://funerariabaldan.educraft.com.br` |
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
- [x] Commit/push do código para o loop CI/CD (`774cd27` em `origin/main`)
- [ ] Conferir no domínio após o deploy (menu Fiscal + NF-e saída)

## Links úteis

- Fase F6: `04-build/phases/F6-deploy.md`
- Deploy: `docs/DEPLOY.md`
- FE local: http://localhost:5173/login
- Operador: `operador` / `password` (também `operador@baldan.local`)
- Admin: `admin` / `password` (também `admin@baldan.local`)
- GitHub: https://github.com/vitaovolt/funeraria-baldan-nf

## Histórico rápido

| Data | Evento |
|------|--------|
| 27/08/2026 | **Smoke F5 OK · gate F5 fechado** → F6 |
| 10/09/2026 | **Push A17** `774cd27` → `origin/main` (CI/CD) |
| 10/09/2026 | **Smoke A17 OK** · menu Fiscal + NF-e de saída dos produtos (Focus `/v2/nfe`) |
| 27/08/2026 | F6 deploy self-hosted + handoff; PHP 34 + Playwright 3; `aguardando_aprovacao` |
| 27/08/2026 | Bootstrap EC2 `34.224.58.173` + HTTPS `funerariabaldan.educraft.com.br`; health ok |
