# Mapa de telas (MVP congelado) — `funeraria-baldan-nf`

Produto: ponte NFC-e. A venda **não** nasce neste painel.

A simulação Gold no hi-fi **não** é o sistema Pegasus real (ainda sem print). Visual = marca Baldan + faixa “simulação”. Serve para alinhar o contrato: o que sai da venda, o que a ponte faz, o que volta (link do cupom ou aviso + chamar de novo).

| ID | Tela | Perfil | Prioridade MVP |
|----|------|--------|----------------|
| T1 | Simulação Gold — fechar venda (marcar emitir NFC-e) | Equipe no Gold | sim |
| T2 | Simulação Gold — retorno autorizada + imprimir cupom (link) | Gold → Educraft → Gold | sim |
| T3 | Simulação Gold — retorno recusada + tentar de novo (mesmo id) | Gold → Educraft → Gold | sim |
| T4 | Entrar (painel Baldan) | Equipe / contador | sim |
| T5 | Lista de notas (Ver / Imprimir / Tentar de novo) | Equipe / contador | sim |
| T6 | Detalhe autorizada + cupom 80 mm + XML | Equipe / contador | sim |
| T7 | Detalhe recusada + Tentar de novo | Equipe / contador | sim |
| T8 | Configuração empresa + certificado A1 | Contador | sim |

## Fora do MVP (não desenhar como tela)

- Cadastros/produtos do Gold
- NF-e, cancelar/inutilizar nota
- Nova nota no painel Baldan
- App / WhatsApp / BI
- Nome de provedor fiscal

## Fluxo feliz (congelado)

1. T1 Finalizar venda (NFC-e marcada) → espera na tela Gold  
2. T2 Autorizada + **Imprimir cupom** (Gold chamou o link)  
3. T5 a mesma nota aparece no painel (plano B)

## Fluxo de recusa (congelado)

1. T1 com cenário recusa → T3 aviso + **Tentar de novo** no Gold  
2. Mesmo pedido G-1042; sem segunda nota  
3. T5/T7 ações iguais no painel
