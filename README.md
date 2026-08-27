# Projeto `funeraria-baldan-nf`

PDV + estoque simples + consignado + NFC-e para a loja da Funerária Baldan.

Preview hi-fi: https://funerariabaldan.educraft.com.br  
**GitHub:** https://github.com/vitaovolt/funeraria-baldan-nf

## Stack

- `code/backend` — Laravel 12 API (Sanctum Bearer, PostgreSQL)
- `code/frontend` — React + Vite + Tailwind SPA

## Particularidades

Ver [`CAPABILITIES.md`](CAPABILITIES.md): **fiscal** + **queues** + **files**.

## Seeds locais

| Usuário | Senha | Papel |
|---------|-------|--------|
| `operador@baldan.local` | `password` | operador |
| `admin@baldan.local` | `password` | admin (config fiscal / A1) |

## Deploy (F6)

- Workflows: `.github/workflows/ci.yml` + `deploy.yml` (self-hosted)
- Handoff: [`docs/DEPLOY.md`](docs/DEPLOY.md)
- Worker NFC-e: [`docs/queue-worker.service.example`](docs/queue-worker.service.example)

Secret obrigatório no GitHub: `DEPLOY_PATH` (ex. `/var/www/funeraria-baldan-nf`).
