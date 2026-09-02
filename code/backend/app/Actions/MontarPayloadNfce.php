<?php

namespace App\Actions;

use App\Models\ConfiguracaoFiscal;
use App\Models\Venda;

class MontarPayloadNfce
{
    private const NOME_HOMOLOGACAO = 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';

    private const ITEM_HOMOLOGACAO = 'NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';

    private const NOME_CONSUMIDOR = 'Consumidor';

    /**
     * @return array<string, mixed>
     */
    public function handle(Venda $venda, ConfiguracaoFiscal $config, int $numero, int $serie): array
    {
        $venda->loadMissing(['itens.produto', 'cliente']);
        $cnpj = preg_replace('/\D/', '', (string) $config->cnpj) ?? '';
        $homologacao = ($config->ambiente_nfce ?? 'homologacao') === 'homologacao';

        $descontoRestante = $venda->desconto_tipo === 'nenhum'
            ? 0.0
            : round(max(0, (float) $venda->subtotal - (float) $venda->total), 2);

        $itens = [];
        foreach ($venda->itens as $index => $item) {
            $qtd = (int) $item->quantidade;
            $unitario = number_format((float) $item->preco_unitario, 2, '.', '');
            $descontoItem = 0.0;
            if ($index === 0 && $descontoRestante > 0) {
                $descontoItem = min($descontoRestante, (float) $item->total_linha);
            }

            $descricao = $item->produto?->descricao ?: 'Item';
            if ($homologacao && $index === 0) {
                $descricao = self::ITEM_HOMOLOGACAO;
            }

            $itens[] = [
                'numero_item' => $index + 1,
                'codigo_produto' => $item->produto?->codigo_barras ?: (string) $item->produto_id,
                'descricao' => $descricao,
                'codigo_ncm' => $this->ncm($item->produto?->ncm),
                'cfop' => (string) config('focusnfe.cfop_padrao', '5102'),
                'unidade_comercial' => 'UN',
                'unidade_tributavel' => 'UN',
                'quantidade_comercial' => (string) $qtd,
                'quantidade_tributavel' => (string) $qtd,
                'valor_unitario_comercial' => $unitario,
                'valor_unitario_tributavel' => $unitario,
                'valor_desconto' => number_format($descontoItem, 2, '.', ''),
                'icms_origem' => '0',
                'icms_situacao_tributaria' => (string) config('focusnfe.csosn_padrao', '102'),
            ];
        }

        $payload = [
            'cnpj_emitente' => $cnpj,
            'data_emissao' => now('America/Sao_Paulo')->format('Y-m-d H:i:s'),
            'natureza_operacao' => 'Venda de mercadorias',
            'serie' => $serie,
            'numero' => $numero,
            'presenca_comprador' => '1',
            'modalidade_frete' => '9',
            'local_destino' => '1',
            'indicador_inscricao_estadual_destinatario' => '9',
            'itens' => $itens,
            'formas_pagamento' => [[
                'forma_pagamento' => $this->codigoPagamento($venda->forma_pagamento),
                'valor_pagamento' => number_format((float) $venda->total, 2, '.', ''),
            ]],
        ];

        $this->anexarDestinatario($payload, $venda, $homologacao);
        $this->anexarIbsCbs($payload, $config);

        return $payload;
    }

    /**
     * Destino só entra com CPF/CNPJ real (padrão clínica 2V / exemplo Focus).
     * Prioridade: documento informado na finalização; senão, cadastro do cliente.
     * Sem documento = consumidor não identificado — não inventar CPF nem mandar só o nome.
     *
     * @param  array<string, mixed>  $payload
     */
    private function anexarDestinatario(array &$payload, Venda $venda, bool $homologacao): void
    {
        $doc = preg_replace('/\D/', '', (string) $venda->documento_destinatario_nfce) ?? '';
        if ($doc === '') {
            $doc = preg_replace('/\D/', '', (string) $venda->cliente?->documento) ?? '';
        }
        if (strlen($doc) === 11) {
            $payload['cpf_destinatario'] = $doc;
        } elseif (strlen($doc) >= 14) {
            $payload['cnpj_destinatario'] = $doc;
        } else {
            return;
        }

        $payload['nome_destinatario'] = $homologacao
            ? self::NOME_HOMOLOGACAO
            : ($venda->cliente?->nome ?: self::NOME_CONSUMIDOR);
    }

    /**
     * Reforma 2026: alíquotas-teste nacionais (IBS 0,1% + CBS 0,9%). Homolog SP rejeita 1115 sem o grupo.
     *
     * @param  array<string, mixed>  $payload
     */
    private function anexarIbsCbs(array &$payload, ConfiguracaoFiscal $config): void
    {
        if (! config('focusnfe.reforma_tributaria.habilitar_ibs_cbs', true)) {
            return;
        }

        $cbsAliquota = max((float) config('focusnfe.reforma_tributaria.cbs.aliquota', 0.9), 0);
        $ibsUf = max((float) config('focusnfe.reforma_tributaria.ibs.uf_aliquota', 0), 0);
        $ibsMun = max((float) config('focusnfe.reforma_tributaria.ibs.municipio_aliquota', 0), 0);
        $ibsTotal = max((float) config('focusnfe.reforma_tributaria.ibs.aliquota', 0.1), 0);
        if ($ibsUf + $ibsMun <= 0 && $ibsTotal > 0) {
            $ibsUf = $ibsTotal;
        }

        $cst = (string) config('focusnfe.reforma_tributaria.ibs_cbs_situacao_tributaria', '000');
        $classificacao = (string) config('focusnfe.reforma_tributaria.ibs_cbs_classificacao_tributaria', '000001');

        $somaBase = 0;
        $somaCbs = 0;
        $somaIbsUf = 0;
        $somaIbsMun = 0;
        $somaIbs = 0;

        foreach ($payload['itens'] as $index => $item) {
            $base = round(
                (float) $item['quantidade_comercial'] * (float) $item['valor_unitario_comercial'] - (float) $item['valor_desconto'],
                2
            );
            if ($base <= 0) {
                continue;
            }

            $cbs = round($base * ($cbsAliquota / 100), 2);
            $vIbsUf = round($base * ($ibsUf / 100), 2);
            $vIbsMun = round($base * ($ibsMun / 100), 2);
            $vIbs = round($vIbsUf + $vIbsMun, 2);

            $payload['itens'][$index]['ibs_cbs_situacao_tributaria'] = $cst;
            $payload['itens'][$index]['ibs_cbs_classificacao_tributaria'] = $classificacao;
            $payload['itens'][$index]['ibs_cbs_base_calculo'] = number_format($base, 2, '.', '');
            $payload['itens'][$index]['cbs_aliquota'] = number_format($cbsAliquota, 4, '.', '');
            $payload['itens'][$index]['cbs_valor'] = number_format($cbs, 2, '.', '');
            $payload['itens'][$index]['ibs_uf_aliquota'] = number_format($ibsUf, 4, '.', '');
            $payload['itens'][$index]['ibs_uf_valor'] = number_format($vIbsUf, 2, '.', '');
            $payload['itens'][$index]['ibs_mun_aliquota'] = number_format($ibsMun, 4, '.', '');
            $payload['itens'][$index]['ibs_mun_valor'] = number_format($vIbsMun, 2, '.', '');
            $payload['itens'][$index]['ibs_valor_total'] = number_format($vIbs, 2, '.', '');

            $somaBase += $base;
            $somaCbs += $cbs;
            $somaIbsUf += $vIbsUf;
            $somaIbsMun += $vIbsMun;
            $somaIbs += $vIbs;
        }

        if ($somaBase <= 0) {
            return;
        }

        $ibge = preg_replace('/\D/', '', (string) $config->codigo_ibge) ?? '';
        if (strlen($ibge) === 7) {
            $payload['ibs_cbs_municipio'] = $ibge;
        }

        $payload['ibs_cbs_base_calculo'] = number_format($somaBase, 2, '.', '');
        $payload['cbs_valor_total'] = number_format($somaCbs, 2, '.', '');
        $payload['ibs_valor_total'] = number_format($somaIbs, 2, '.', '');
        $payload['ibs_uf_valor_total'] = number_format($somaIbsUf, 2, '.', '');
        if ($somaIbsMun > 0) {
            $payload['ibs_mun_valor_total'] = number_format($somaIbsMun, 2, '.', '');
        }
        $payload['ibs_cbs_is_valor_total'] = number_format($somaIbs + $somaCbs, 2, '.', '');
    }

    private function ncm(?string $ncm): string
    {
        $limpo = preg_replace('/\D/', '', (string) $ncm) ?? '';

        return $limpo !== '' ? $limpo : '00000000';
    }

    private function codigoPagamento(?string $forma): string
    {
        return match ($forma) {
            'pix' => '17',
            'cartao', 'cartao_credito' => '03',
            'cartao_debito' => '04',
            default => '01',
        };
    }
}
