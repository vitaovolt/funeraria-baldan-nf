# Respostas consolidadas — `funeraria-baldan-nf`

Fonte: proposta (12/08) + `00-intake/contexto.md` + coluna Resposta em `duvidas.md` (17/08, noite).

**P0 de produto:** fechadas. **Gold (contato #9):** aberto em paralelo, não bloqueia E2.

## Produto

| # | Decisão |
|---|---------|
| — | Ponte NFC-e Gold → Educraft. Não é ERP funerário. Somente NFC-e. |
| 1 | Painel: Márcia + 2 da funerária + 1 contador (~4 logins). |
| 2 | Filtro “emite / não emite” = **Gold**. Educraft emite tudo que receber. |
| 3 | Sem CPF: **emite**. |
| 4 | Correção no **Gold** + reenvio do mesmo id. Painel só reprocessa. |
| 5 | Cancelar/inutilizar: **não** no MVP. |
| 6 | ~50 notas/semana. |
| 7 | DANFE em **impressora de cupom fiscal (80 mm)**. |
| 8 | Gold **não** guarda XML. Recebe na hora: autorizada + **link do cupom** (impressão automática no Gold) **ou** recusada + aviso + **chamar de novo**. Painel: Imprimir / Tentar de novo. |
| 14–15 | Tela de **config da empresa** + **upload do A1** no MVP. CSC/IE/série reais no go-live, não no protótipo. |
| 19–25 | Sem PWA, multi-empresa, offline, WhatsApp, tempo real, BI; sem nome de provedor na UI. |

## Integração / fiscal

| # | Decisão |
|---|---------|
| 9 | Sem contato Gold ainda. Plano: Educraft manda o contrato quando houver nome/e-mail. |
| 10 | Educraft **define** o JSON (E2). Gold implementa depois. |
| 11 | **POST** no fechamento. Emissão **síncrona**. Retorno: link do cupom **ou** aviso + retentativa. Fila/job = serviço da nota fora + botão no painel. |
| 12 | Educraft define `Authorization` + HMAC + `Idempotency-Key` (id da venda). |
| 13 | Id estável da venda é **obrigatório** no contrato. |
| 16 | Sem contingência oficial NFC-e. **Sim** rotina periódica para reemitir quando a SEFAZ voltar. |
| 17 | Homolog ≠ produção (mesmo padrão dos outros sistemas Educraft; provedor só interno). |
| 18 | Worker/scheduler **24 h** para o retry. |

## O que ainda não temos (não bloqueia lo-fi)

- Nome, e-mail e WhatsApp do técnico Gold (#9)
- Payload real confirmado por eles (#10 — usamos o contrato Educraft até lá)
- UF, IE, CRT, CSC, série de produção (#14 — tela existe; valores depois)
