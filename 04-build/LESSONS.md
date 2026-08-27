# Lições — rodada

**Slug:** funeraria-baldan-nf  
**Etapa / fase:** e4 / F6  
**Data:** 2026-08-27  

## O que funcionou

- Deploy self-hosted + Deploy Key `funeraria-baldan-nf_github`; secrets só `DEPLOY_PATH` (padrão DEPLOY-GITHUB).
- Handoff: `docs/DEPLOY.md` + nginx + systemd queue `fiscal,default` (packs fiscal/queues/files).
- `DeployArtifactsTest` valida workflows/docs + health `checks.database` sem precisar da EC2 no CI local.
- SES N/A no MVP (`MAIL_MAILER=log`).
- E2E: PHP 34 + Playwright 3 (regressão F0–F5 inclusa).

## O que falhou / atrito

- Path do project root no teste: `dirname(__DIR__, 4)` a partir de `tests/Feature` (Feature→tests→backend→code→project).

## Melhoria de processo

- Sem tip nova (self-hosted + Deploy Key já no KB/DEPLOY-GITHUB; F6 sem SES já documentado).

## Tags

`#e4` `#f6` `#deploy` `#self-hosted` `#fiscal` `#queues` `#files`

## Sync KB

- [x] Ficha `lessons/2026-08-27-funeraria-baldan-nf-e4-f6.md`
