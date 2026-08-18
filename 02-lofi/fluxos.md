# Fluxos por perfil — `funeraria-baldan-nf`

Lo-fi valida o **produto certo**: ponte NFC-e. A venda continua no Gold. O painel é o plano B (consultar, imprimir, tentar de novo).

## Mapa (todos os perfis)

```
Gold                                           Painel Educraft
──────────────────────────────────────────     ────────────────────────────
Fecha a venda                                  —
POST ─────────────────────────────────────►    Emite na hora
◄── autorizada + link do cupom                 Lista / Imprimir cupom / XML
    (Gold chama o link → impressora)           
◄── recusada + aviso + chamar de novo          Tentar de novo
    (mesmo id, sem segunda nota)               
Job 24 h se o serviço da nota cair             Status “Tentando de novo”
                                               Config empresa + certificado
```

Dois caminhos, mesmo resultado: **Gold faz sozinho** (impressão/retentativa no retorno) **ou** a equipe faz no painel.

## Perfil: Equipe Baldan (Márcia + 2)

### Fluxo principal — cupom pela Gold (feliz)

1. Fecha a venda no Gold (se aquela venda emite).
2. Gold espera a nota na hora, recebe o **link do cupom** e imprime.
3. Se precisar de novo: entra no painel → lista → **Imprimir cupom**.

### Fluxo no painel — consultar e imprimir de novo

1. Entra (e-mail + senha).
2. Vê autorizada / recusada / tentando de novo.
3. Na linha: **Ver**, **Imprimir cupom** ou **Tentar de novo**.
4. Na nota: cupom 80 mm + XML.

### Fluxos alternativos / erros

- **Recusada:** Gold já viu o aviso e pode chamar de novo o mesmo pedido. No painel: **Tentar de novo** (Processando…). Não edita venda aqui.
- **Serviço da nota fora:** “Tentando de novo”; job 24 h; botão no painel também.
- **Mesmo pedido duas vezes:** não gera segunda nota.
- **Sem CPF:** emite.

## Perfil: Contador Baldan

Igual à equipe na lista. Extra: **Configuração** (empresa + A1 + Teste/Oficial).

## Perfil: Gold System (sem tela neste produto)

1. POST da venda.
2. Resposta **na hora**:
   - autorizada → situação + **link para imprimir o cupom**;
   - recusada → **aviso** + **chamar de novo** o mesmo pedido.
3. A nota já está na lista da Baldan.

## Perfil: Operação Educraft (interno)

Homologação e job 24 h. Sem nome de provedor na UI da Baldan.

## Telas do pretótipo

| Tela | Para quê |
|------|----------|
| Entrar | Login |
| Notas | Lista com Ver / Imprimir / Tentar de novo |
| Nota autorizada | Cupom 80 mm + XML (Gold já tem o link) |
| Nota recusada | Motivo + Tentar de novo |
| Configuração | Empresa + A1 |
| Chegada da venda | O que o Gold recebe — **não** é a tela deles |

## Packs no fluxo

| Pack | Onde |
|------|------|
| `fiscal` | Status NFC-e, config, Teste/Oficial |
| `integrations` | POST síncrono: link do cupom + aviso/retentativa |
| `queues` | Tentando de novo + job 24 h |
| `files` | Cupom 80 mm (link Gold + painel) + XML + upload .pfx |
