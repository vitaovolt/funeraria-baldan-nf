<?php

namespace App\Actions;

use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarMovimentacaoEstoque
{
    /**
     * @param  array{tipo: string, quantidade: float|int|string, observacao?: string|null}  $dados
     */
    public function handle(Produto $produto, array $dados, ?User $usuario = null): MovimentacaoEstoque
    {
        $tipo = $dados['tipo'];
        $quantidade = (float) $dados['quantidade'];

        if ($quantidade <= 0) {
            throw new InvalidArgumentException('Quantidade deve ser maior que zero.');
        }

        if (! in_array($tipo, ['entrada', 'saida', 'ajuste'], true)) {
            throw new InvalidArgumentException('Tipo de movimentação inválido.');
        }

        return DB::transaction(function () use ($produto, $tipo, $quantidade, $dados, $usuario) {
            /** @var Produto $travado */
            $travado = Produto::query()->whereKey($produto->id)->lockForUpdate()->firstOrFail();
            $saldoAnterior = (float) $travado->estoque_atual;

            $saldoPosterior = match ($tipo) {
                'entrada' => $saldoAnterior + $quantidade,
                'saida' => $saldoAnterior - $quantidade,
                'ajuste' => $quantidade, // quantidade = novo saldo absoluto
            };

            if ($saldoPosterior < 0) {
                throw new InvalidArgumentException('Estoque insuficiente para a saída.');
            }

            $travado->estoque_atual = $saldoPosterior;
            $travado->save();

            return MovimentacaoEstoque::query()->create([
                'produto_id' => $travado->id,
                'user_id' => $usuario?->id,
                'tipo' => $tipo,
                'quantidade' => $tipo === 'ajuste' ? abs($saldoPosterior - $saldoAnterior) : $quantidade,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'observacao' => $dados['observacao'] ?? null,
            ]);
        });
    }
}
