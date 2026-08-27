# Ajustes do operador — `funeraria-baldan-nf`

Use entre etapas/fases (ou no meio delas).  
Cada item vira trabalho + candidatura à base central (`educraft-devkit/lessons/`).

## Como registrar

No chat:

```
Use educraft-dev-orchestrator.
ajuste funeraria-baldan-nf
[cole aqui a dica ou o que quer mudar]
```

Ou escreva abaixo e diga `processar ajustes funeraria-baldan-nf`.

## Fila

| ID | Data | Etapa/fase | Pedido | Status | Foi para KB? |
|----|------|------------|--------|--------|--------------|
| A1 | 17/08/2026 | e1 | Renomear slug `funeraria-baldan` → `funeraria-baldan-nf` | feito | não (só projeto) |
| A2 | 17/08/2026 | e1 | Ligar o projeto ao GitHub (`vitaovolt/funeraria-baldan-nf`) antes da E1 | feito | sim: tips/2026-08-17-iniciar-github-no-slug.md |
| A3 | 18/08/2026 | e3 | Identidade = logo + flyer Baldan (`assets/brand`); sem print Gold, mesmo layout da marca | feito | sim: tips/2026-08-18-funeraria-baldan-nf-dica.md |
| A4 | 20/08/2026 | e3 | Cancelar integração ERP terceiro; MVP = PDV + produtos/clientes/estoque + NFC-e | feito | sim: tips/2026-08-20-funeraria-baldan-nf-pivot-pdv.md |
| A5 | 20/08/2026 | e3 | Layout moderno Baldan (não copiar ERP); retirar menções a Gold da UI/docs ativos | feito | não (só projeto) |
| A6 | 20/08/2026 | e3 | Mobile: menu lateral recolhível (não cobrir a tela toda) | feito | não (só projeto) |
| A7 | 25/08/2026 | e3 | Áudios: desconto livre; consignado MVP; custo+preço; vendas do dia; imprimir caixa; sangria; dependentes; import Excel TBD | feito | não (só projeto) |
| A8 | 25/08/2026 | e3 | Logo sumiu; PDV: cliente em dropdown com busca + consignar na caixa; sangria só no Caixa; UX venda | feito | sim: tips/2026-08-25-proto-assets-chmod-apos-scp.md |

Status: `aberto` | `em_andamento` | `feito` | `descartado`

## Notas

- Ajuste de **produto/projeto** → altera artefatos desta pasta  
- Ajuste de **processo/framework** → também entra em `lessons/process/MELHORIAS-PENDENTES.md` (se ainda não existir)
- A1: pasta `projects/funeraria-baldan-nf/` + STATUS/README/CAPABILITIES. Proposta comercial (PDF/JSON) permanece com o nome do cliente.
- A3: `baldan_logo.jpg` + `cores_baldan.jpg` (cópia em `00-intake/brand/`). Paleta navy/amarelo.
- A4: MVP = PDV próprio (prints de referência só no intake).
- A5: hi-fi moderno; UI e docs ativos sem citar sistema terceiro.
- A7: retorno áudios 25/08 — ver `00-intake/whatsapp-2026-08-25/TRANSCRICAO.md`.
- A8: logo — `assets/` e `brand/` estavam `700` na EC2 (www-data não lia → fallback HTML). PDV: cliente/consignar dentro do painel da venda.
