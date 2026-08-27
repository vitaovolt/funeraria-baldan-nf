# Deploy — `funeraria-baldan-nf`

Padrão: [educraft-devkit/standards/DEPLOY-GITHUB.md](../../../../standards/DEPLOY-GITHUB.md)  
Self-hosted runner na EC2 + Deploy Key no disco. **Não** abrir SSH 22 para o mundo.

## Secrets (GitHub → Settings → Secrets)

| Secret | Obrigatório | Valor típico |
|--------|-------------|--------------|
| `DEPLOY_PATH` | sim | `/var/www/funeraria-baldan-nf` |
| `REPO_DEPLOY_KEY` | não | Fallback da privada (preferir chave só no disco) |

**Não** cadastrar: `SSH_HOST`, `SSH_USER`, `SSH_PRIVATE_KEY`.

## Uma vez na EC2

SSH admin (todas as máquinas Educraft — **não** usar AWS CLI):

```powershell
ssh -i "C:\Users\Admin\Documents\Chaves_\educraft.pem" ubuntu@<IP>
```

```bash
sudo mkdir -p /var/www/funeraria-baldan-nf
sudo chown -R ubuntu:ubuntu /var/www/funeraria-baldan-nf
cd /var/www/funeraria-baldan-nf
git clone git@github.com:vitaovolt/funeraria-baldan-nf.git .

ssh-keygen -t ed25519 -C "funeraria-baldan-nf-ec2-deploy" -f ~/.ssh/funeraria-baldan-nf_github -N ""
cat ~/.ssh/funeraria-baldan-nf_github.pub
# → GitHub Deploy keys (read-only)

GIT_SSH_COMMAND="ssh -i ~/.ssh/funeraria-baldan-nf_github -o IdentitiesOnly=yes" git fetch origin
```

Self-hosted runner (labels `self-hosted`, `Linux`, `X64`) apontando para este repo.

## App no servidor

1. `code/backend/.env` de produção (não no Git):
   - `APP_ENV=production`, `APP_DEBUG=false`
   - `APP_URL=https://api.…` (ou mesmo host da SPA + `/api`)
   - `FRONTEND_URL=https://pdv.…` (**obrigatório** — CORS)
   - `DB_*` PostgreSQL
   - `QUEUE_CONNECTION=database` (ou `redis`)
   - `FILESYSTEM_DISK=local` (XML/DANFE) ou `s3` se migrar
2. `php artisan key:generate` na 1ª vez
3. Nginx: ver `nginx-spa-api.conf.example`
4. Queue worker: ver `queue-worker.service.example` (fila `fiscal,default`)
5. Cron: `* * * * * cd /var/www/funeraria-baldan-nf/code/backend && php artisan schedule:run`

## Smoke pós-deploy

```bash
curl -sS https://<host>/api/v1/health
# esperado: checks.database = ok
```

Login SPA com seed (ou usuário prod): operador / admin.

## E-mail

MVP Baldan **não** depende de e-mail transacional no go-live (`MAIL_MAILER=log` ok).  
Se no futuro enviar e-mail: operador configura SES no console — [SES-CLOUDFLARE.md](../../../../standards/SES-CLOUDFLARE.md) (agente **não** usa `aws` CLI do PC).

## Security Group

| Porta | Origem |
|-------|--------|
| 22 | só IPv4 `/32` do admin |
| 80/443 | Cloudflare / público |
