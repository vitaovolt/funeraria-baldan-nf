<?php

namespace App\Actions;

use App\Models\ConfiguracaoFiscal;
use App\Models\Venda;
use InvalidArgumentException;

class MontarPayloadNfe
{
    private const NOME_HOMOLOGACAO = 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';

    private const ITEM_HOMOLOGACAO = 'NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';

    /**
     * Payload Focus NF-e modelo 55 (saída) — alinhado à Lindinha / clínica 2V.
     *
     * @return array<string, mixed>
     */
    public function handle(Venda $venda, ConfiguracaoFiscal $config, int $numero, int $serie): array
    {
        $venda->loadMissing(['itens.produto', 'cliente']);
        $cnpj = preg_replace('/\D/', '', (string) $config->cnpj) ?? '';
        $homologacao = ($config->ambiente_nfce ?? 'homologacao') === 'homologacao';

        if (! $venda->cliente) {
            throw new InvalidArgumentException('NF-e de saída exige cliente cadastrado na venda.');
        }

        $descontoRestante = $venda->desconto_tipo === 'nenhum'
            ? 0.0
            : round(max(0, (float) $venda->subtotal - (float) $venda->total), 2);

        $items = [];
        foreach ($venda->itens as $index => $item) {
            $qtd = (int) $item->quantidade;
            $unitario = number_format((float) $item->preco_unitario, 2, '.', '');
            $totalLinha = number_format((float) $item->total_linha, 2, '.', '');
            $descontoItem = 0.0;
            if ($index === 0 && $descontoRestante > 0) {
                $descontoItem = min($descontoRestante, (float) $item->total_linha);
            }

            $descricao = $item->produto?->descricao ?: 'Item';
            if ($homologacao && $index === 0) {
                $descricao = self::ITEM_HOMOLOGACAO;
            }

            $linha = [
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
                'pis_situacao_tributaria' => '99',
                'pis_base_calculo' => $totalLinha,
                'pis_aliquota' => '0.0000',
                'pis_valor' => '0.00',
                'cofins_situacao_tributaria' => '99',
                'cofins_base_calculo' => $totalLinha,
                'cofins_aliquota' => '0.0000',
                'cofins_valor' => '0.00',
            ];

            $items[] = $linha;
        }

        $payload = [
            'cnpj_emitente' => $cnpj,
            'natureza_operacao' => 'Venda de mercadorias',
            'data_emissao' => now('America/Sao_Paulo')->format('Y-m-d\TH:i:sP'),
            'tipo_documento' => 1,
            'finalidade_emissao' => 1,
            'consumidor_final' => 1,
            'presenca_comprador' => 1,
            'local_destino' => 1,
            'modalidade_frete' => 9,
            'serie' => $serie,
            'numero' => $numero,
            'items' => $items,
            'formas_pagamento' => [[
                'forma_pagamento' => $this->codigoPagamento($venda->forma_pagamento),
                'valor_pagamento' => number_format((float) $venda->total, 2, '.', ''),
            ]],
        ];

        foreach ($this->destinatario($venda, $homologacao) as $campo => $valor) {
            if ($valor !== null && $valor !== '') {
                $payload[$campo] = $valor;
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function destinatario(Venda $venda, bool $homologacao): array
    {
        $cliente = $venda->cliente;
        $doc = preg_replace('/\D/', '', (string) ($venda->documento_destinatario_nfce ?: $cliente->documento)) ?? '';
        if (strlen($doc) !== 11 && strlen($doc) !== 14) {
            throw new InvalidArgumentException('Informe CPF (11) ou CNPJ (14) do destinatário para a NF-e.');
        }

        $dados = [
            'nome_destinatario' => $homologacao
                ? self::NOME_HOMOLOGACAO
                : ($cliente->nome ?: 'Consumidor'),
            'indicador_inscricao_estadual_destinatario' => 9,
        ];

        if (strlen($doc) === 11) {
            $dados['cpf_destinatario'] = $doc;
        } else {
            $dados['cnpj_destinatario'] = $doc;
            $ie = preg_replace('/\D/', '', (string) $cliente->inscricao_estadual) ?? '';
            if ($ie !== '') {
                $dados['inscricao_estadual_destinatario'] = $ie;
                $dados['indicador_inscricao_estadual_destinatario'] = 1;
            }
        }

        if ($cliente->telefone) {
            $dados['telefone_destinatario'] = preg_replace('/\D/', '', (string) $cliente->telefone);
        }
        if ($cliente->email) {
            $dados['email_destinatario'] = $cliente->email;
        }

        $logra = trim((string) ($cliente->logradouro ?? ''));
        $cidade = trim((string) ($cliente->cidade ?? ''));
        $uf = strtoupper(trim((string) ($cliente->uf ?? '')));
        $cep = preg_replace('/\D/', '', (string) ($cliente->cep ?? '')) ?? '';
        if ($logra === '' || $cidade === '' || strlen($uf) !== 2 || strlen($cep) !== 8) {
            throw new InvalidArgumentException(
                'Complete o endereço do cliente (logradouro, cidade, UF e CEP com 8 dígitos) para emitir a NF-e.'
            );
        }

        $dados['logradouro_destinatario'] = $logra;
        $dados['numero_destinatario'] = trim((string) ($cliente->numero ?? '')) ?: 'S/N';
        if (trim((string) ($cliente->complemento ?? '')) !== '') {
            $dados['complemento_destinatario'] = $cliente->complemento;
        }
        $dados['bairro_destinatario'] = trim((string) ($cliente->bairro ?? '')) ?: 'Centro';
        $dados['municipio_destinatario'] = $cidade;
        $dados['uf_destinatario'] = $uf;
        $dados['cep_destinatario'] = $cep;
        $dados['pais_destinatario'] = 'Brasil';

        return $dados;
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
