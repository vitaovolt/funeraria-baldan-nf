<?php

namespace App\Actions;

use App\Models\SessaoCaixa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AbrirSessaoCaixa
{
    public function handle(User $usuario, float $valorAbertura = 0): SessaoCaixa
    {
        return DB::transaction(function () use ($usuario, $valorAbertura) {
            $aberta = SessaoCaixa::query()->abertas()->lockForUpdate()->first();
            if ($aberta) {
                throw new InvalidArgumentException('Já existe um caixa aberto.');
            }

            return SessaoCaixa::query()->create([
                'user_id' => $usuario->id,
                'aberto_em' => now(),
                'valor_abertura' => $valorAbertura,
                'status' => 'aberta',
            ]);
        });
    }
}
