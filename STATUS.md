# STATUS — `funeraria-baldan-nf`

| Campo | Valor |
|-------|--------|
| slug | `funeraria-baldan-nf` |
| etapa_atual | `e3` |
| fase_e4 | — |
| status | `aguardando_cliente` |
| proximo_passo | Conferir https://funerariabaldan.educraft.com.br e preencher `03-hifi/feedback.md` |
| skill | `educraft-dev-e3-hifi` |

## Capacidades ativas

Ver [CAPABILITIES.md](CAPABILITIES.md). Resumo: ponte NFC-e (POST síncrono Gold → Educraft; link do cupom + retentativa), retry em fila, DANFE cupom 80 mm.

| Pack | Status |
|------|--------|
| fiscal | sim |
| integrations | sim |
| queues | sim |
| files | sim |

## Gate E1 (fechado em 18/08/2026)

- [x] Brief, dúvidas P0, CAPABILITIES, LESSONS

## Gate E2 (fechado em 18/08/2026)

- [x] `02-lofi/fluxos.md`
- [x] `02-lofi/prototipo-lofi.html`
- [x] `02-lofi/feedback.md` (F1–F9 OK; zero Ajustar/Bloquear)
- [x] `02-lofi/LESSONS.md` + sync `lessons/INDEX.md`
- [x] Playbook: artefatos + lições + API+SPA + packs `sim` no pretótipo

## Gate atual (E3)

- [x] `03-hifi/identidade-visual.html` + `identidade.md` + `assets/brand/` (A3: logo + flyer do cliente)
- [x] `03-hifi/prototipo-hifi.html` (navy/amarelo; Gold simulado no layout Baldan + faixa)
- [x] `03-hifi/mapa-telas.md` (MVP congelado T1–T8)
- [ ] `03-hifi/feedback.md` (I1–I4 + H1–H8 — aguardando)
- [x] `03-hifi/LESSONS.md` + sync `lessons/INDEX.md`

## Links úteis

- Como usar o framework: `educraft-devkit/COMECE-AQUI.md`
- Brief: `01-discovery/brief.md`
- Capacidades: `CAPABILITIES.md`
- Lo-fi: `02-lofi/prototipo-lofi.html`
- Identidade: `03-hifi/identidade-visual.html`
- Hi-fi: `03-hifi/prototipo-hifi.html`
- Preview: https://funerariabaldan.educraft.com.br
- Proposta comercial: `marketing/propostas/Proposta_Educraft_Funeraria_Baldan_2026-08-12_simples.pdf`
- GitHub: https://github.com/vitaovolt/funeraria-baldan-nf

## Histórico rápido

| Data | Evento |
|------|--------|
| 12/08/2026 | Proposta comercial aceita (ponte NFC-e, R$ 2.480 + R$ 197/mês) |
| 17/08/2026 | Projeto criado a partir do skeleton; Gold ainda sem conversa formal de integração |
| 17/08/2026 | A1: slug renomeado de `funeraria-baldan` para `funeraria-baldan-nf` |
| 17/08/2026 | A2: repositório GitHub `vitaovolt/funeraria-baldan-nf` |
| 17/08/2026 | E1 rodada 1: brief + dúvidas + LESSONS; aguardando P0 |
| 17/08/2026 | E1 retorno: P0 produto fechadas; POST síncrono; cupom 80 mm; config+A1; Gold P1 |
| 18/08/2026 | Gate E1 fechado → E2 |
| 18/08/2026 | E2 rodada 1: fluxos + lo-fi desktop; aguardando feedback |
| 18/08/2026 | E2 retorno F4/F5: link do cupom + retentativa no POST Gold; ações no painel |
| 18/08/2026 | Gate E2 fechado → E3 |
| 18/08/2026 | E3: identidade Baldan Notas + hi-fi com simulação Gold (ida e volta) |
| 18/08/2026 | A3: identidade alinhada ao logo/flyer (`assets/brand`); Gold simulado no layout Baldan |
| 18/08/2026 | Preview hi-fi em https://funerariabaldan.educraft.com.br (EC2, pasta isolada) |
