<?php

namespace App\Support;

use App\Models\SessaoCaixa;

class ResumoCaixa
{
    /**
     * @return array<string, mixed>
     */
    public static function montar(SessaoCaixa $caixa, bool $preview): array
    {
        $caixa->loadMissing(['usuario:id,name,email', 'vendas.notaNfce', 'sangrias', 'suprimentos']);

        $vendas = $caixa->vendas;
        $porForma = [
            'dinheiro' => round((float) $vendas->where('forma_pagamento', 'dinheiro')->sum('total'), 2),
            'pix' => round((float) $vendas->where('forma_pagamento', 'pix')->sum('total'), 2),
            'cartao' => round((float) $vendas->where('forma_pagamento', 'cartao')->sum('total'), 2),
        ];
        $totalVendas = $preview
            ? round((float) $vendas->sum('total'), 2)
            : (float) $caixa->total_vendas;
        $totalSangrias = $preview
            ? round((float) $caixa->sangrias->sum('valor'), 2)
            : (float) $caixa->total_sangrias;
        $totalSuprimentos = round((float) $caixa->suprimentos->sum('valor'), 2);
        $esperado = $preview
            ? round((float) $caixa->valor_abertura + $porForma['dinheiro'] + $totalSuprimentos - $totalSangrias, 2)
            : (float) $caixa->total_dinheiro_esperado;

        return [
            'sessao' => $caixa,
            'preview' => $preview,
            'total_vendas' => $totalVendas,
            'total_sangrias' => $totalSangrias,
            'total_suprimentos' => $totalSuprimentos,
            'total_dinheiro_esperado' => $esperado,
            'totais_forma' => $porForma,
            'valor_abertura' => (float) $caixa->valor_abertura,
        ];
    }
}
