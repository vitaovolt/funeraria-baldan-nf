<?php

namespace App\Actions;

use App\Models\Cliente;
use App\Models\ItemVenda;
use App\Models\NotaNfce;
use App\Models\Produto;
use App\Models\SessaoCaixa;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinalizarVenda
{
    public function __construct(private EmitirNfceSincrono $emitirNfce) {}

    /**
     * @param  array{
     *   itens: list<array{produto_id:int, quantidade:float|int|string}>,
     *   cliente_id?: int|null,
     *   desconto_tipo?: string,
     *   desconto_valor?: float|int|string,
     *   forma_pagamento?: string,
     *   emitir_nfce?: bool,
     *   documento_nfce?: string|null,
     *   valor_recebido?: float|int|string|null,
     *   idempotency_key?: string|null
     * }  $dados
     */
    public function handle(User $usuario, array $dados): Venda
    {
        $itens = $dados['itens'] ?? [];
        if ($itens === []) {
            throw new InvalidArgumentException('Informe ao menos um item.');
        }

        $idemKey = isset($dados['idempotency_key']) && is_string($dados['idempotency_key'])
            ? trim($dados['idempotency_key'])
            : null;
        if ($idemKey === '') {
            $idemKey = null;
        }

        if ($idemKey !== null) {
            $existing = Venda::query()->where('idempotency_key', $idemKey)->first();
            if ($existing) {
                return $existing->load(['itens.produto', 'cliente', 'notaNfce']);
            }

            return Cache::lock('venda_idem:'.$idemKey, 30)->block(10, function () use ($usuario, $dados, $itens, $idemKey) {
                $existing = Venda::query()->where('idempotency_key', $idemKey)->first();
                if ($existing) {
                    return $existing->load(['itens.produto', 'cliente', 'notaNfce']);
                }

                return $this->criarEEmitir($usuario, $dados, $itens, $idemKey);
            });
        }

        return $this->criarEEmitir($usuario, $dados, $itens, null);
    }

    /**
     * @param  list<array{produto_id:int, quantidade:float|int|string}>  $itens
     */
    private function criarEEmitir(User $usuario, array $dados, array $itens, ?string $idemKey): Venda
    {
        $venda = $this->criarVenda($usuario, $dados, $itens, $idemKey);

        if ($this->deveEmitir($dados) && $venda->notaNfce) {
            $this->emitirNfce->handle($venda->notaNfce);
        }

        return $venda->fresh(['itens.produto', 'cliente', 'notaNfce']);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function deveEmitir(array $dados): bool
    {
        if (! array_key_exists('emitir_nfce', $dados)) {
            return true;
        }

        return filter_var($dados['emitir_nfce'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  list<array{produto_id:int, quantidade:float|int|string}>  $itens
     */
    private function criarVenda(User $usuario, array $dados, array $itens, ?string $idemKey): Venda
    {
        return DB::transaction(function () use ($usuario, $dados, $itens, $idemKey) {
            /** @var SessaoCaixa|null $caixa */
            $caixa = SessaoCaixa::query()->abertas()->lockForUpdate()->first();
            if (! $caixa) {
                throw new InvalidArgumentException('Abra o caixa antes de vender.');
            }

            $linhas = [];
            $subtotal = 0.0;

            foreach ($itens as $item) {
                $produtoId = (int) $item['produto_id'];
                $qtd = (float) $item['quantidade'];
                if ($qtd <= 0) {
                    throw new InvalidArgumentException('Quantidade inválida.');
                }

                /** @var Produto $produto */
                $produto = Produto::query()->whereKey($produtoId)->lockForUpdate()->firstOrFail();
                if ((float) $produto->estoque_atual < $qtd) {
                    throw new InvalidArgumentException("Estoque insuficiente para {$produto->descricao}.");
                }

                $preco = (float) $produto->preco_venda;
                $totalLinha = round($preco * $qtd, 2);
                $subtotal += $totalLinha;

                $linhas[] = [
                    'produto' => $produto,
                    'quantidade' => $qtd,
                    'preco_unitario' => $preco,
                    'custo_unitario' => (float) $produto->custo,
                    'total_linha' => $totalLinha,
                ];
            }

            $descontoTipo = $dados['desconto_tipo'] ?? 'nenhum';
            $descontoValor = (float) ($dados['desconto_valor'] ?? 0);
            $desconto = 0.0;
            if ($descontoTipo === 'percentual') {
                $desconto = round($subtotal * ($descontoValor / 100), 2);
            } elseif ($descontoTipo === 'valor') {
                $desconto = round($descontoValor, 2);
            }
            if ($desconto > $subtotal) {
                throw new InvalidArgumentException('Desconto maior que o subtotal.');
            }

            $total = round($subtotal - $desconto, 2);
            $clienteId = $dados['cliente_id'] ?? null;
            if ($clienteId) {
                Cliente::query()->whereKey($clienteId)->firstOrFail();
            }

            $documentoNfce = preg_replace('/\D/', '', (string) ($dados['documento_nfce'] ?? '')) ?? '';
            $valorRecebido = isset($dados['valor_recebido']) && $dados['valor_recebido'] !== '' && $dados['valor_recebido'] !== null
                ? round((float) $dados['valor_recebido'], 2)
                : null;

            $venda = Venda::query()->create([
                'sessao_caixa_id' => $caixa->id,
                'user_id' => $usuario->id,
                'cliente_id' => $clienteId,
                'status' => 'finalizada',
                'subtotal' => $subtotal,
                'desconto_tipo' => $descontoTipo,
                'desconto_valor' => $descontoValor,
                'total' => $total,
                'forma_pagamento' => $dados['forma_pagamento'] ?? 'dinheiro',
                'documento_destinatario_nfce' => $documentoNfce !== '' ? $documentoNfce : null,
                'valor_recebido' => $valorRecebido,
                'idempotency_key' => $idemKey,
                'finalizada_em' => now(),
            ]);

            foreach ($linhas as $linha) {
                ItemVenda::query()->create([
                    'venda_id' => $venda->id,
                    'produto_id' => $linha['produto']->id,
                    'quantidade' => $linha['quantidade'],
                    'preco_unitario' => $linha['preco_unitario'],
                    'custo_unitario' => $linha['custo_unitario'],
                    'total_linha' => $linha['total_linha'],
                ]);

                $produto = $linha['produto'];
                $produto->estoque_atual = (float) $produto->estoque_atual - $linha['quantidade'];
                $produto->save();
            }

            if ($this->deveEmitir($dados)) {
                NotaNfce::query()->create([
                    'venda_id' => $venda->id,
                    'status' => 'pendente',
                ]);
            }

            return $venda->load(['itens.produto', 'cliente', 'notaNfce']);
        });
    }
}
