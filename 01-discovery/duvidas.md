# Dúvidas — rodada 1 (retorno 17/08)

Prioridade: **P0 bloqueia E2** | P1 importante | P2 nice  
Status: `aberta` | `fechada`  
Quem: `interno` | `Baldan` | `Gold` | `contador`

Responda na coluna **Resposta** ou em `respostas.md` (os dois valem; consolidado fica em `respostas.md`).

## Negócio

| # | Dúvida | Pri | Quem | Status | Resposta |
|---|--------|-----|------|--------|----------|
| 1 | Quem usa o painel no dia a dia (Márcia + quantas pessoas)? Quantos logins? | P0 | Baldan | fechada | Márcia + 2 da funerária + 1 do contador (~4 logins). |
| 2 | Toda venda fechada no Gold vira NFC-e automaticamente, ou só algumas? | P0 | Baldan | fechada | Contador: nem toda venda emite. **Regra fica no Gold.** Educraft só emite o que receber. |
| 3 | Consumidor sem CPF: emite ou bloqueia? | P0 | Baldan + contador | fechada | **Emite sem CPF** normalmente. |
| 4 | Erro: corrige no Gold e reenvia, ou edita no painel Educraft? | P0 | Baldan + interno | fechada | **Corrige no Gold e reenvia o mesmo id.** Painel só “tentar de novo”, sem cadastro paralelo. |
| 5 | Cancelar ou inutilizar NFC-e no painel no MVP? **sim / não** | P0 | Baldan | fechada | **Não.** MVP = listar + baixar + reprocessar rejeição. |
| 6 | Volume estimado (notas por semana)? | P1 | Baldan | fechada | ~50 vendas/semana. |
| 7 | DANFE: PDF A4 ou cupom 80 mm? | P1 | Baldan | fechada | **Cupom fiscal (80 mm).** Configurar para impressora de cupom. |
| 8 | A Gold precisa receber de volta chave/XML da nota? **sim / não** | P1 | Gold + interno | fechada | **Não** precisa guardar XML. Recebe na hora: autorizada + **link do cupom**, ou recusada + **aviso** + **chamar de novo**. Painel continua com as mesmas ações (E2 F4/F5). |

## Técnicas (integração + fiscal)

| # | Dúvida | Pri | Quem | Status | Resposta |
|---|--------|-----|------|--------|----------|
| 9 | Contato técnico Gold (nome, e-mail, WhatsApp) e roadmap do envio? | P1 | interno / Gold | aberta | Sem conversa formal até 17/08. **Não bloqueia E2** (Educraft define o contrato; Gold em paralelo). |
| 10 | Contrato de dados: quais campos a Gold envia? | P0 | Gold | fechada | **Plano:** Educraft define o JSON na E2; Gold implementa depois. Lo-fi do painel não depende do payload final. |
| 11 | Gold envia com **POST** no fechamento? **sim / não** — fila vs síncrono | P0 | Gold + interno | fechada | **Sim, POST.** Emissão e retorno **síncronos** no mesmo request (Gold espera o resultado). Fila/job = só retry quando a SEFAZ estiver fora (#16) e “tentar de novo” no painel. |
| 12 | Educraft define auth (token/HMAC) e a Gold implementa? **sim / não** | P0 | interno / Gold | fechada | **Sim.** `Authorization` + HMAC do body + `Idempotency-Key` = id da venda. |
| 13 | Venda tem **id único e estável** no Gold? **sim / não** | P0 | Gold | fechada | **Obrigatório** no contrato (senão duplica NFC-e). Gold confirma quando houver contato. |
| 14 | UF, IE, CRT, CSC, série/número — temos? | P0 | Baldan + contador | fechada | Valores reais **não** bloqueiam o protótipo. MVP precisa de **tela de config da empresa** + upload do certificado. |
| 15 | Como o A1 chega (`.pfx` + senha + validade)? | P0 | Baldan | fechada | **Upload na tela de config** (não manda por WhatsApp no fluxo definitivo). |
| 16 | Contingência NFC-e (layout SEFAZ offline)? **sim / não** | P0 | Baldan + interno | fechada | **Não** (contingência oficial). **Sim** job periódico para reemitir quando a SEFAZ voltar. |
| 17 | Homologação: CSC/série de teste ≠ produção? **sim / não** | P1 | contador | fechada | **Sim.** Cadastro no provedor interno como nos outros sistemas Educraft. Nome do provedor **não** vai à UI. |
| 18 | Worker 24 h? **sim / não** | P1 | interno | fechada | **Sim** (retry/reprocessar). Venda de funerária não espera horário comercial. |

## Particularidades (sim / não)

| # | Dúvida | Pri | Quem | Status | Resposta |
|---|--------|-----|------|--------|----------|
| 19 | App / PWA? **sim / não** | P0 | Baldan | fechada | **Não.** Painel web no computador. |
| 20 | Multi-empresa? **sim / não** | P0 | interno | fechada | **Não.** Só Baldan. |
| 21 | Offline / caixa sem internet? **sim / não** | P0 | interno | fechada | **Não.** |
| 22 | WhatsApp quando autorizar? **sim / não** | P2 | Baldan | fechada | **Não** no MVP. |
| 23 | Tempo real sem F5? **sim / não** | P2 | interno | fechada | **Não.** Atualizar / polling leve. |
| 24 | Relatórios / BI? **sim / não** | P2 | interno | fechada | **Não.** |
| 25 | Citar provedor fiscal na UI? **sim / não** | P0 | interno | fechada | **Não.** “Emissão pela Educraft” / NFC-e. |
