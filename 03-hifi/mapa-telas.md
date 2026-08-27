# Mapa de telas (MVP) — `funeraria-baldan-nf`

Produto: **PDV + cadastros + estoque simples + NFC-e + consignado**.

| ID | Tela | Perfil | Prioridade MVP |
|----|------|--------|----------------|
| T1 | Entrar | Equipe / contador | sim |
| T2 | Início | Equipe | sim |
| T3 | Caixa — abrir / fechar / vendas do dia / imprimir fechamento | Equipe | sim |
| T4 | Venda (PDV) — carrinho + desconto (% ou R$) | Equipe | sim |
| T5 | Buscar produto / código de barras | Equipe | sim |
| T6 | Pagamento (+ emitir NFC-e) | Equipe | sim |
| T7 | Sangria (no Caixa — saída de dinheiro) | Equipe | sim |
| T8 | Produtos — lista (custo + preço + NCM) | Equipe | sim |
| T9 | Produto — novo / editar | Equipe | sim |
| T10 | Marcas / categorias | Equipe | sim |
| T11 | Clientes — titulares + dependentes + plano | Equipe | sim |
| T12 | Cliente — novo / editar (+ hint import Excel) | Equipe | sim |
| T13 | Consignado — lista / devolver / virar venda | Equipe | sim |
| T14 | Estoque — saldos + ajuste | Equipe | sim |
| T15 | Notas NFC-e | Equipe / contador | sim |
| T16 | Configuração empresa + A1 | Contador | sim |

## Fora do MVP

- Módulos funerários (óbito, cremação, ambulância, etc.)
- NF-e, cancelar nota, compras, BI
- App / WhatsApp / multi-empresa
- Fiado separado (é o **consignado**)
- Import Excel de titulares/dependentes (formato **ainda a definir**)

## Fluxo feliz

1. Abrir caixa → Venda  
2. Buscar / código → desconto opcional → Pagar e emitir nota → cupom  
3. Estoque baixa; nota em Notas; venda aparece no caixa do dia  

## Fluxo consignado

1. Cliente leva produto para provar → notinha consignado  
2. Devolve → tira do consignado / volta estoque **ou** finaliza compra (vira venda)

## Fluxo fechar o dia

1. Caixa → Fechar caixa → **imprimir fechamento** → conferência
