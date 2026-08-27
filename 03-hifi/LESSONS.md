# Lições — rodada

**Slug:** funeraria-baldan-nf  
**Etapa / fase:** e3  
**Data:** 2026-08-26  
**Rodada:** 5 (feedback hi-fi aprovado)

## O que funcionou

- A5: hi-fi com sidebar Baldan, home em cartões, PDV com busca/carrinho/resumo — sem F-keys nem chrome de ERP terceiro.
- Textos da UI e docs ativos limpos de menção a sistema concorrente.
- A8 + preview: cliente validou logo, paleta, tipografia e todas as telas H1–H13 em uma rodada (zero Ajustar/Bloquear).

## O que falhou / atrito

- Rodada A4 ainda imitava o PDV do terceiro (F-keys). Cliente pediu visual moderno próprio.
- A8: assets no EC2 sem permissão de leitura para o nginx — logo “sumiu” no HTTPS até chmod.

## Melhoria de processo

- Prints de terceiro = só referência de **fluxo/campos**, nunca de layout na UI entregue ao cliente.
- Após SCP de brand assets no proto: garantir perms legíveis pelo www-data (já tipado no KB).

## Tags

`#e3` `#hi-fi` `#pdv` `#identidade` `#ajuste` `#feedback`

## Sync KB

- [x] Projeto A5; dica A4/A8 já no KB
- [x] Aprovação feedback 26/08 — sem dica nova de processo
- [x] Gate E3: ficha `lessons/2026-08-26-funeraria-baldan-nf-e3.md` + linha no INDEX
