# Backend Laravel API — Funerária Baldan NF

DNA: servicoslocais desacoplado (sem Inertia).

- Controllers em `app/Http/Controllers/Api`
- Actions / Services — ver `educraft-devkit/standards/CODIGO-BACKEND.md`
- `routes/api.php` → `/api/v1`
- OpenAPI: `docs/openapi.yaml`
- Packs: `queues` (database), `files` (local → S3 na F6), `fiscal` (NFC-e nas fases seguintes)
- Health: `GET /api/v1/health` → service `funeraria-baldan-nf-api`
