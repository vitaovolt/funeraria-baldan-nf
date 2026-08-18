# Lições — rodada

**Slug:** funeraria-baldan-nf  
**Etapa / fase:** e2  
**Data:** 2026-08-18  
**Rodada:** 2 (processar-retorno F4/F5)

## O que funcionou

- F4/F5: dois caminhos no mesmo contrato — Gold recebe link/aviso; painel replica as ações na lista.

## O que falhou / atrito

- E1 #8 (“Gold não precisa de XML”) foi lido como “não devolve nada útil”. O ajuste era **link do cupom + retentativa**, não persistir XML.

## Melhoria de processo (acionável)

- Lo-fi de ponte: no retorno síncrono, perguntar/mostrar **o que o terceiro faz com sucesso e com erro** (imprimir, tentar de novo), não só “status na lista”.

## Padrão candidato (standards/catalog?)

- Pack `integrations` + `files`: resposta do webhook pode carregar **link de arquivo** (cupom) sem o terceiro guardar o blob.

## Tags

`#e2` `#lo-fi` `#fiscal` `#nfce` `#integrations` `#files`

## Sync KB

- [x] Ficha `2026-08-18-funeraria-baldan-nf-e2.md` atualizada (rodada 2)
- [x] Linha em `lessons/INDEX.md` (já existia)
- [x] Item em `MELHORIAS-PENDENTES.md`
