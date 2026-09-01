<?php

namespace App\Actions;

use App\Models\SangriaCaixa;
use App\Models\SessaoCaixa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarSuprimento
{
    public function handle(User $usuario, float $valor, ?string $motivo = null): SangriaCaixa
    {
        if ($valor <= 0) {
            throw new InvalidArgumentException('Valor do suprimento deve ser maior que zero.');
        }

        return DB::transaction(function () use ($usuario, $valor, $motivo) {
            /** @var SessaoCaixa|null $caixa */
            $caixa = SessaoCaixa::query()->abertas()->lockForUpdate()->first();
            if (! $caixa) {
                throw new InvalidArgumentException('Abra o caixa antes de registrar suprimento.');
            }

            return SangriaCaixa::query()->create([
                'sessao_caixa_id' => $caixa->id,
                'user_id' => $usuario->id,
                'valor' => round($valor, 2),
                'tipo' => 'suprimento',
                'motivo' => $motivo,
            ]);
        });
    }
}
