# Brief — `funeraria-baldan-nf`

**Produto certo?** Sim (A4–A7, ago/2026): **PDV + estoque + NFC-e + consignado**, identidade Baldan.

## Problema / gargalo

A Baldan vende produtos na loja (incl. ortopédicos) e precisa emitir NFC-e, controlar estoque, clientes/planos e consignado (provar produto).

## Escopo MVP

1. **PDV:** abrir/fechar caixa; vender; **desconto livre (% ou R$)**; pagamento; **sangria**; NFC-e; cupom 80 mm.
2. **Produtos:** barra, descrição, marca/categoria, **custo + preço de venda**, estoque, referência, NCM.
3. **Clientes:** titular + **dependentes**; flag/consulta de **plano**; import Excel de titulares/dependentes (**formato a definir**).
4. **Consignado (MVP):** notinha; fica no cadastro; **devolver** ou **virar venda**. Não é fiado separado.
5. **Estoque simples** + baixa na venda / consignado.
6. **Caixa:** listagem de **vendas do dia** (com cliente) + **imprimir fechamento**.
7. **Notas NFC-e** + **Config A1**.

## Fora de escopo (MVP)

- Fiado separado do consignado
- Import Excel já implementado (só preparado; layout da planilha TBD)
- Módulos funerários completos, NF-e, BI, app/WhatsApp

## Critérios de sucesso

- Venda com desconto e NFC-e; fechamento de caixa imprimível.
- Produto com custo e preço; consignado rastreável por outro operador.
- Cliente titular/dependentes; plano visível.
