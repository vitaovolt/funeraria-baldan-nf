# Capacidades do projeto — `funeraria-baldan-nf`

Marque cada pack: `nao` | `avaliar` | `sim`  
Catálogo: [../../capabilities/INDEX.md](../../capabilities/INDEX.md)

| Pack | Status | Notas (1 linha) |
|------|--------|-----------------|
| offline-sync | nao | Operação 100% online (Gold já é web) |
| queues | sim | Emissão NFC-e assíncrona + retry de webhook |
| multi-tenant | nao | MVP = uma empresa (Baldan); plano Focus Educraft é interno |
| realtime | nao | Painel com lista/status basta (polling ou refresh) |
| files | sim | XML e DANFE da NFC-e para download |
| fiscal | sim | Somente NFC-e no MVP |
| mobile | nao | Painel web |
| integrations | sim | Gold System envia venda; Educraft emite NFC-e |
| reporting | nao | Lista de notas no painel; sem BI |
| custom | nao | |

## Dependências detectadas

- fiscal=sim ⇒ queues=sim
- fiscal=sim ⇒ files=sim (XML/DANFE)
- integrations=sim ⇒ webhook Gold com autenticação + idempotência

## Preenchido em

- Etapa: e1
- Data: 17/08/2026
