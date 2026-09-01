<?php

namespace App\Actions;

use App\Models\SessaoCaixa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FecharSessaoCaixa
{
    public function handle(User $usuario): SessaoCaixa
    {
        return DB::transaction(function () use ($usuario) {
            /** @var SessaoCaixa|null $caixa */
            $caixa = SessaoCaixa::query()->abertas()->lockForUpdate()->first();
            if (! $caixa) {
                throw new InvalidArgumentException('Não há caixa aberto para fechar.');
            }

            $totalVendas = (float) $caixa->vendas()->sum('total');
            $totalSangrias = (float) $caixa->sangrias()->sum('valor');
            $totalSuprimentos = (float) $caixa->suprimentos()->sum('valor');
            $vendasDinheiro = (float) $caixa->vendas()->where('forma_pagamento', 'dinheiro')->sum('total');
            $esperado = round((float) $caixa->valor_abertura + $vendasDinheiro + $totalSuprimentos - $totalSangrias, 2);

            $caixa->update([
                'status' => 'fechada',
                'fechado_em' => now(),
                'total_vendas' => round($totalVendas, 2),
                'total_sangrias' => round($totalSangrias, 2),
                'total_dinheiro_esperado' => $esperado,
            ]);

            return $caixa->fresh()->load(['usuario:id,name,email', 'sangrias', 'vendas']);
        });
    }
}
