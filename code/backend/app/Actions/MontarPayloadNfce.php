<?php

namespace App\Actions;

use App\Models\Cliente;
use App\Models\ConfiguracaoFiscal;
use App\Models\Venda;

class MontarPayloadNfce
{
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

            $itens[] = [
                'numero_item' => $index + 1,
                'codigo_produto' => $item->produto?->codigo_barras ?: (string) $item->produto_id,
                'descricao' => $item->produto?->descricao ?: 'Item',
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
            'data_emissao' => now()->format('Y-m-d H:i:s'),
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

        $this->anexarDestinatario($payload, $venda->cliente, $homologacao);

        return $payload;
    }

    /** @param  array<string, mixed>  $payload */
    private function anexarDestinatario(array &$payload, ?Cliente $cliente, bool $homologacao): void
    {
        if ($homologacao) {
            $payload['nome_destinatario'] = 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';
        } elseif ($cliente) {
            $payload['nome_destinatario'] = $cliente->nome;
        }

        if (! $cliente?->documento) {
            return;
        }

        $doc = preg_replace('/\D/', '', $cliente->documento) ?? '';
        if (strlen($doc) === 11) {
            $payload['cpf_destinatario'] = $doc;
        } elseif (strlen($doc) >= 14) {
            $payload['cnpj_destinatario'] = $doc;
        }
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
            'cartao' => '03',
            default => '01',
        };
    }
}
