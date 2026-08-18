# Lições — rodada

**Slug:** funeraria-baldan-nf  
**Etapa / fase:** e1  
**Data:** 2026-08-17  
**Rodada:** 2 (processar-retorno)

## O que funcionou

- Operador respondeu na coluna de `duvidas.md` (não em `respostas.md`) — o fluxo “aceitar nos dois e espelhar” pegou.
- Decisões de produto vieram mesmo sem contrato Gold: filtro no Gold, POST síncrono, cupom 80 mm, tela de config+A1.

## O que falhou / atrito

- “Emite na fila” gerou dúvida. Operador quer **emissão e retorno síncronos** no POST da Gold. Fila = só retry SEFAZ.
- Contato Gold (#9) continua vazio; não bloqueia lo-fi se Educraft dona o contrato.

## Melhoria de processo (acionável)

- Pack `fiscal`/`integrations` E1: pergunta explícita **sim/não** — “o terceiro espera o resultado fiscal **no mesmo POST**?” (síncrono vs 202+fila).
- CSC/IE/série: se a resposta for “não precisa para o protótipo”, fechar P0 com **tela de config** no MVP — não deixar a dúvida aberta.

## Padrão candidato (standards/catalog?)

- NFC-e ponte: POST síncrono + job de retry ≠ contingência oficial SEFAZ.
- DANFE: perguntar A4 vs cupom 80 mm (não assumir PDF A4).

## Tags

`#e1` `#fiscal` `#nfce` `#integrations` `#queues` `#files`

## Sync KB

- [x] Ficha `educraft-devkit/lessons/2026-08-17-funeraria-baldan-nf-e1.md` atualizada (rodada 2)
- [x] Linha em `lessons/INDEX.md` (já existia)
- [x] Item novo em `MELHORIAS-PENDENTES.md` (POST síncrono vs fila)
