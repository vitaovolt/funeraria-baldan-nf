<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoFiscal;
use App\Models\Consignado;
use App\Models\SessaoCaixa;
use App\Models\Venda;
use App\Support\ResumoCaixa;
use Illuminate\Http\Response;

class ImpressaoController extends Controller
{
    public function comprovanteVenda(Venda $venda): Response
    {
        $venda->load(['itens.produto', 'cliente', 'notaNfce']);
        $documento = $venda->documento_destinatario_nfce ?: $venda->cliente?->documento;

        return response()->view('impressao.comprovante-venda', [
            'venda' => $venda,
            'empresa' => $this->empresa(),
            'documento' => $documento,
        ]);
    }

    public function notinhaConsignado(Consignado $consignado): Response
    {
        $consignado->load(['cliente', 'itens.produto']);

        return response()->view('impressao.notinha-consignado', [
            'consignado' => $consignado,
            'empresa' => $this->empresa(),
        ]);
    }

    public function fechamentoCaixa(): Response
    {
        $aberta = SessaoCaixa::query()->abertas()->first();
        $caixa = $aberta ?: SessaoCaixa::query()->where('status', 'fechada')->orderByDesc('fechado_em')->first();
        abort_unless($caixa, 404, 'Não há caixa para imprimir.');

        $resumo = ResumoCaixa::montar($caixa, (bool) $aberta);

        return response()->view('impressao.fechamento-caixa', [
            'caixa' => $caixa,
            'resumo' => $resumo,
            'empresa' => $this->empresa(),
        ]);
    }

    private function empresa(): ConfiguracaoFiscal
    {
        return ConfiguracaoFiscal::query()->first() ?? new ConfiguracaoFiscal([
            'razao_social' => 'Funerária Baldan',
            'uf' => 'SP',
        ]);
    }
}
