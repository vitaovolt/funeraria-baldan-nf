# Capacidades do projeto — `funeraria-baldan-nf`

Marque cada pack: `nao` | `avaliar` | `sim`  
Catálogo: [../../capabilities/INDEX.md](../../capabilities/INDEX.md)

| Pack | Status | Notas (1 linha) |
|------|--------|-----------------|
| offline-sync | nao | Operação online (PDV web) |
| queues | sim | Retry NFC-e 24 h + reprocessar no painel |
| multi-tenant | nao | MVP = uma empresa (Baldan) |
| realtime | nao | Refresh / polling |
| files | sim | XML + DANFE cupom 80 mm |
| fiscal | sim | NFC-e via Focus NFe no fechamento da venda; CSC/A1 no painel Focus |
| mobile | nao | Desktop (caixa) |
| integrations | nao | Sem integração com ERP terceiro no MVP |
| reporting | nao | Lista de notas / movimentos; sem BI |
| custom | nao | |

## Dependências detectadas

- fiscal=sim ⇒ files=sim
- fiscal=sim ⇒ queues=sim (retry SEFAZ)
- PDV + estoque + clientes + consignado = núcleo do produto
- ~4 usuários (Márcia, 2 equipe, 1 contador)

## Preenchido em

- Etapa: e3 (A7 — áudios 25/08)
- Data: 25/08/2026
