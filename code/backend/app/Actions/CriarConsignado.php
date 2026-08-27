<?php

namespace App\Actions;

use App\Models\Cliente;
use App\Models\Consignado;
use App\Models\ItemConsignado;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CriarConsignado
{
    /**
     * @param  array{
     *   cliente_id: int,
     *   observacao?: string|null,
     *   itens: list<array{produto_id:int, quantidade:float|int|string}>
     * }  $dados
     */
    public function handle(User $usuario, array $dados): Consignado
    {
        $itens = $dados['itens'] ?? [];
        if ($itens === []) {
            throw new InvalidArgumentException('Informe ao menos um item.');
        }

        Cliente::query()->whereKey($dados['cliente_id'])->firstOrFail();

        return DB::transaction(function () use ($usuario, $dados, $itens) {
            $consignado = Consignado::query()->create([
                'cliente_id' => $dados['cliente_id'],
                'user_id' => $usuario->id,
                'status' => 'aberto',
                'observacao' => $dados['observacao'] ?? null,
            ]);

            $estoque = app(RegistrarMovimentacaoEstoque::class);

            foreach ($itens as $item) {
                $produtoId = (int) $item['produto_id'];
                $qtd = (float) $item['quantidade'];
                if ($qtd <= 0) {
                    throw new InvalidArgumentException('Quantidade inválida.');
                }

                /** @var Produto $produto */
                $produto = Produto::query()->whereKey($produtoId)->lockForUpdate()->firstOrFail();

                $estoque->handle($produto, [
                    'tipo' => 'saida',
                    'quantidade' => $qtd,
                    'observacao' => "Consignado #{$consignado->id}",
                ], $usuario);

                ItemConsignado::query()->create([
                    'consignado_id' => $consignado->id,
                    'produto_id' => $produto->id,
                    'quantidade' => $qtd,
                    'quantidade_devolvida' => 0,
                    'quantidade_vendida' => 0,
                    'preco_unitario' => (float) $produto->preco_venda,
                ]);
            }

            return $consignado->load(['cliente', 'itens.produto']);
        });
    }
}
