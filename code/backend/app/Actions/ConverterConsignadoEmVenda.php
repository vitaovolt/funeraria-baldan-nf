<?php

namespace App\Actions;

use App\Jobs\EmitirNfceJob;
use App\Models\Consignado;
use App\Models\ItemConsignado;
use App\Models\ItemVenda;
use App\Models\NotaNfce;
use App\Models\SessaoCaixa;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ConverterConsignadoEmVenda
{
    /**
     * Converte quantidades pendentes em venda (estoque já baixado no consignado).
     *
     * @param  list<array{item_id:int, quantidade?:float|int|string}>|null  $itens
     *   null = todo o pendente
     */
    public function handle(Consignado $consignado, User $usuario, ?array $itens = null, string $formaPagamento = 'dinheiro'): Venda
    {
        if (! in_array($consignado->status, ['aberto', 'parcial'], true)) {
            throw new InvalidArgumentException('Consignado não está aberto para virar venda.');
        }

        return DB::transaction(function () use ($consignado, $usuario, $itens, $formaPagamento) {
            /** @var Consignado $travado */
            $travado = Consignado::query()->whereKey($consignado->id)->lockForUpdate()->firstOrFail();

            /** @var SessaoCaixa|null $caixa */
            $caixa = SessaoCaixa::query()->abertas()->lockForUpdate()->first();
            if (! $caixa) {
                throw new InvalidArgumentException('Abra o caixa antes de converter consignado em venda.');
            }

            $travado->load('itens');
            $mapa = [];
            if ($itens === null) {
                foreach ($travado->itens as $item) {
                    $pend = $item->quantidadePendente();
                    if ($pend > 0) {
                        $mapa[$item->id] = $pend;
                    }
                }
            } else {
                foreach ($itens as $linha) {
                    $mapa[(int) $linha['item_id']] = (float) ($linha['quantidade'] ?? 0);
                }
            }

            if ($mapa === []) {
                throw new InvalidArgumentException('Nada pendente para vender.');
            }

            $linhas = [];
            $subtotal = 0.0;

            foreach ($mapa as $itemId => $qtd) {
                if ($qtd <= 0) {
                    throw new InvalidArgumentException('Quantidade inválida.');
                }
                /** @var ItemConsignado $item */
                $item = ItemConsignado::query()
                    ->where('consignado_id', $travado->id)
                    ->whereKey($itemId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($qtd > $item->quantidadePendente()) {
                    throw new InvalidArgumentException('Quantidade maior que o pendente do item.');
                }

                $totalLinha = round((float) $item->preco_unitario * $qtd, 2);
                $subtotal += $totalLinha;
                $linhas[] = compact('item', 'qtd', 'totalLinha');
            }

            $venda = Venda::query()->create([
                'sessao_caixa_id' => $caixa->id,
                'user_id' => $usuario->id,
                'cliente_id' => $travado->cliente_id,
                'status' => 'finalizada',
                'subtotal' => $subtotal,
                'desconto_tipo' => 'nenhum',
                'desconto_valor' => 0,
                'total' => round($subtotal, 2),
                'forma_pagamento' => $formaPagamento,
                'finalizada_em' => now(),
            ]);

            foreach ($linhas as $linha) {
                /** @var ItemConsignado $item */
                $item = $linha['item'];
                ItemVenda::query()->create([
                    'venda_id' => $venda->id,
                    'produto_id' => $item->produto_id,
                    'quantidade' => $linha['qtd'],
                    'preco_unitario' => $item->preco_unitario,
                    'custo_unitario' => 0,
                    'total_linha' => $linha['totalLinha'],
                ]);
                $item->quantidade_vendida = (float) $item->quantidade_vendida + $linha['qtd'];
                $item->save();
            }

            $nota = NotaNfce::query()->create([
                'venda_id' => $venda->id,
                'status' => 'pendente',
            ]);
            EmitirNfceJob::dispatch($nota->id)->onQueue('fiscal');

            $travado->load('itens');
            $pendente = $travado->itens->sum(fn (ItemConsignado $i) => $i->quantidadePendente());
            $travado->status = $pendente <= 0.0001 ? 'vendido' : 'parcial';
            $travado->save();

            return $venda->load(['itens.produto', 'cliente', 'notaNfce']);
        });
    }
}
