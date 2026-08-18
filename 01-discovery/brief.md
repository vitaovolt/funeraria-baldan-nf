# Brief — `funeraria-baldan-nf`

**Produto certo?** Sim: uma **ponte fiscal NFC-e**, não um ERP funerário. A Baldan já vende no Gold; o gargalo é a nota. Cadastro, estoque ou “sistema da funerária” seria o produto errado.

**Gate E2:** info mínima ok. Educraft define o contrato do POST; contato Gold segue **em paralelo** (não trava o lo-fi do painel).

## Problema / gargalo

A Funerária Baldan opera no **Gold System (Pegasus)**. O Gold **não emite NFC-e**. A venda não vira nota sozinha: retrabalho, atraso e risco na hora de entregar o comprovante à família.

Nem toda venda deve virar nota (orientação do contador). Essa escolha fica **no Gold**. A Educraft só emite o que receber.

A Gold desenvolve o envio do lado deles (cobrança à parte). Até 17/08/2026 ainda **não havia conversa formal** de integração.

## Objetivo (30–60–90 dias)

| Horizonte | Meta |
|-----------|------|
| 30 dias | Painel + config + POST síncrono em homologação; contrato enviado à Gold |
| 60 dias | Go-live: venda no Gold → NFC-e na hora; cupom na impressora; retry se SEFAZ cair |
| 90 dias | Operação estável; NF-e **não** entra |

Prazo comercial: 3–4 semanas após alinhamento Gold, sujeito ao lado deles.

## Perfis de usuário

| Perfil | Papel | Logins |
|--------|-------|--------|
| Márcia + 2 da funerária | Vendem no Gold; no painel consultam, imprimem cupom, reprocessam | 3 |
| Contador Baldan | Acessa o painel (consulta / config fiscal) | 1 |
| Operação Educraft | Suporte, monitoramento, homologação | interno |
| Gold System | Envia a venda; recebe link do cupom ou aviso+retentativa; **não** usa o SPA | — |

~4 logins do cliente no painel.

## Escopo MVP

1. **POST** da venda (Gold → Educraft): auth + HMAC + id estável; Educraft **emite na hora**. Resposta: autorizada + **link para imprimir o cupom**, ou recusada + **aviso** + **chamar de novo** o mesmo pedido.
2. **NFC-e** com A1 da Baldan, via plano fiscal Educraft (provedor interno **não** aparece para o cliente). Sem CPF: emite mesmo assim.
3. **Painel web (plano B):** listar; status; XML; **DANFE cupom 80 mm**; **Imprimir cupom** e **Tentar de novo** na lista e no detalhe (se a equipe não usar o Gold).
4. **Config da empresa:** dados fiscais + **upload do certificado A1**.
5. **Retry periódico** se o serviço da nota estiver fora (não é contingência oficial NFC-e).
6. Homologação (ambiente ≠ produção) + guia curto de uso.

Arquitetura: `backend/` API Laravel + `frontend/` SPA. Mutação (receber venda / reprocessar / emitir): **uma vez por intenção** (idempotência no backend + UI bloqueada).

## Fora de escopo

- Desenvolvimento ou manutenção do **Gold System** (incluindo a regra “esta venda emite ou não”)
- **NF-e**, cancelamento/inutilização SEFAZ, contingência oficial offline
- Cadastros/produtos **dentro** do Gold
- Assessoria contábil
- App/PWA, WhatsApp, BI, tempo real, multi-empresa, outros sistemas
- Editar itens/valores da venda no painel Educraft

## Integrações / restrições

| Peça | Decisão |
|------|---------|
| Gold | Única origem. Contrato JSON = **Educraft define**. Resposta síncrona: link do cupom **ou** aviso + retentativa. |
| POST | Síncrono. Timeout alto + idempotência. |
| Retry | Gold chama de novo o mesmo id **e** botão no painel **e** job 24 h. |
| Impressão | Cupom 80 mm: Gold usa o link do retorno; painel imprime de novo se quiser. |
| Provedor fiscal | Só interno (mesmo padrão dos outros sistemas). UI: “Educraft” / NFC-e. |
| Certificado | Upload na config (A1 já existe). |
| Volume | ~50 notas/semana. |

Comercial (interno): R$ 2.480 (até 10×) + R$ 197/mês. Lado Gold à parte.

## Particularidades (capacidades)

Ver `../CAPABILITIES.md`.

| Necessidade | Pack | Notas |
|-------------|------|-------|
| NFC-e | `fiscal` = sim | Sem NF-e; homolog ≠ prod |
| POST síncrono + retry SEFAZ | `queues` = sim | Fila **não** segura o POST da Gold; job para retry 24 h |
| XML + DANFE 80 mm | `files` = sim | Link do cupom para a Gold + impressão no painel |
| Gold → Educraft | `integrations` = sim | POST síncrono: cupom no sucesso, aviso+retentativa no erro |
| Offline / PWA / multi-empresa / tempo real / BI | `nao` | Painel web + refresh |

## Critérios de sucesso

- POST de teste devolve autorizada **com link do cupom**, ou recusa **com aviso** — na mesma chamada.
- Gold consegue imprimir pelo link e chamar de novo o mesmo pedido, **sem** segunda nota.
- No painel: Imprimir cupom e Tentar de novo (lista + detalhe).
- SEFAZ fora: nota fica pendente e o job (ou “tentar de novo”) conclui sem duplicar.
- Contador e equipe consultam sem a Educraft no fluxo feliz.
- Zero nome de provedor fiscal na UI ou no material do cliente.
