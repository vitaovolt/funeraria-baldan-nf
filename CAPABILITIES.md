# Capacidades do projeto — `funeraria-baldan-nf`

Marque cada pack: `nao` | `avaliar` | `sim`  
Catálogo: [../../capabilities/INDEX.md](../../capabilities/INDEX.md)

| Pack | Status | Notas (1 linha) |
|------|--------|-----------------|
| offline-sync | nao | Operação 100% online (Gold já é web) |
| queues | sim | POST da Gold é **síncrono**; fila/job = retry 24 h + reprocessar no painel |
| multi-tenant | nao | MVP = uma empresa (Baldan); plano fiscal Educraft é interno |
| realtime | nao | Lista/status com polling ou refresh |
| files | sim | XML + DANFE **cupom 80 mm** (link para a Gold + impressão no painel) |
| fiscal | sim | Somente NFC-e; tela de config + upload A1; homolog ≠ prod |
| mobile | nao | Painel web |
| integrations | sim | POST síncrono: link do cupom no sucesso; aviso + retentativa no erro; painel = plano B |
| reporting | nao | Lista de notas; sem BI |
| custom | nao | |

## Dependências detectadas

- fiscal=sim ⇒ files=sim (XML/DANFE cupom)
- fiscal=sim + serviço da nota instável ⇒ queues=sim (retry), mesmo com emissão síncrona no POST
- integrations=sim ⇒ resposta com link de cupom / retentativa + idempotência; painel não substitui o Gold
- ~4 usuários no painel (Márcia, 2 equipe, 1 contador)

## Preenchido em

- Etapa: e2 (retorno F4/F5)
- Data: 18/08/2026
