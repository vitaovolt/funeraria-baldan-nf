<?php

namespace App\Actions;

use App\Models\Consignado;
use App\Models\ItemConsignado;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DevolverConsignado
{
    /**
     * @param  list<array{item_id:int, quantidade:float|int|string}>  $itens
     */
    public function handle(Consignado $consignado, array $itens, User $usuario): Consignado
    {
        if ($itens === []) {
            throw new InvalidArgumentException('Informe itens para devolver.');
        }

        if (! in_array($consignado->status, ['aberto', 'parcial'], true)) {
            throw new InvalidArgumentException('Consignado não está aberto para devolução.');
        }

        return DB::transaction(function () use ($consignado, $itens, $usuario) {
            /** @var Consignado $travado */
            $travado = Consignado::query()->whereKey($consignado->id)->lockForUpdate()->firstOrFail();
            $estoque = app(RegistrarMovimentacaoEstoque::class);

            foreach ($itens as $linha) {
                /** @var ItemConsignado $item */
                $item = ItemConsignado::query()
                    ->where('consignado_id', $travado->id)
                    ->whereKey((int) $linha['item_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $qtd = (float) $linha['quantidade'];
                if ($qtd <= 0) {
                    throw new InvalidArgumentException('Quantidade inválida.');
                }
                if ($qtd > $item->quantidadePendente()) {
                    throw new InvalidArgumentException('Devolução maior que o pendente do item.');
                }

                $produto = Produto::query()->whereKey($item->produto_id)->lockForUpdate()->firstOrFail();
                $estoque->handle($produto, [
                    'tipo' => 'entrada',
                    'quantidade' => $qtd,
                    'observacao' => "Devolução consignado #{$travado->id}",
                ], $usuario);

                $item->quantidade_devolvida = (float) $item->quantidade_devolvida + $qtd;
                $item->save();
            }

            $this->atualizarStatus($travado);

            return $travado->fresh()->load(['cliente', 'itens.produto']);
        });
    }

    private function atualizarStatus(Consignado $consignado): void
    {
        $consignado->load('itens');
        $pendente = 0.0;
        $teveMovimento = false;
        foreach ($consignado->itens as $item) {
            $pendente += $item->quantidadePendente();
            if ((float) $item->quantidade_devolvida > 0 || (float) $item->quantidade_vendida > 0) {
                $teveMovimento = true;
            }
        }

        if ($pendente <= 0.0001) {
            $soDevolvido = $consignado->itens->every(
                fn (ItemConsignado $i) => (float) $i->quantidade_vendida <= 0.0001
            );
            $consignado->status = $soDevolvido ? 'devolvido' : 'vendido';
        } elseif ($teveMovimento) {
            $consignado->status = 'parcial';
        } else {
            $consignado->status = 'aberto';
        }
        $consignado->save();
    }
}
