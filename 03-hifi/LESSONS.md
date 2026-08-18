# Lições — rodada

**Slug:** funeraria-baldan-nf  
**Etapa / fase:** e3  
**Data:** 2026-08-18  
**Rodada:** 2 (A3)

## O que funcionou

- A3: tokens navy/amarelo extraídos do JPG + flyer; hi-fi e kit passam a usar `baldan_logo.jpg`.
- Sem print do Gold: mesma pele Baldan + faixa “simulação — não é o programa real” (contrato de ida/volta permanece).

## O que falhou / atrito

- Rodada 1 gerou mahogany/ouro/serifa (“Baldan Notas”) e chrome cinza de ERP. Os JPGs já estavam em `03-hifi/assets/brand/` (17:36) **antes** do kit (17:42); Glob/indexação não listou os binários e a E3 tratou como “sem material”.

## Melhoria de processo (acionável)

- E3: listar jpg/png/svg em `00-intake/` e `03-hifi/assets/brand/` (shell se o Glob voltar vazio) **antes** de gerar identidade.
- Terceiro sem print: layout da marca do cliente + faixa simulação; não inventar pele de ERP genérico.

## Padrão candidato (standards/catalog?)

- `IDENTIDADE-VISUAL.md` + skill e3: checklist de arquivos de marca; regra de simulação de terceiro.

## Tags

`#e3` `#hi-fi` `#identidade` `#fiscal` `#nfce` `#integrations`

## Sync KB

- [x] Ficha `educraft-devkit/lessons/2026-08-18-funeraria-baldan-nf-e3.md` (atualizada A3)
- [x] Linha em `lessons/INDEX.md`
- [x] Item em `MELHORIAS-PENDENTES.md` + dica `tips/2026-08-18-funeraria-baldan-nf-dica.md`
