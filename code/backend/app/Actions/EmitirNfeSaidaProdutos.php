<?php

namespace App\Actions;

use App\Models\ConfiguracaoFiscal;
use App\Models\NotaNfce;
use App\Models\User;
use App\Models\Venda;
use InvalidArgumentException;

class EmitirNfeSaidaProdutos
{
    public function __construct(
        private FinalizarVenda $finalizarVenda,
        private EmitirNfeSincrono $emitirNfe,
    ) {}

    /**
     * Cria a venda dos produtos e emite a NF-e de saída (Focus `/v2/nfe`).
     *
     * @param  array{
     *   itens: list<array{produto_id:int, quantidade:int}>,
     *   cliente_id: int,
     *   forma_pagamento?: string,
     *   documento_destinatario?: string|null,
     *   idempotency_key?: string|null
     * }  $dados
     * @return array{venda: Venda, nota: NotaNfce}
     */
    public function handle(User $usuario, array $dados): array
    {
        if (! ConfiguracaoFiscal::moduloAtivo()) {
            throw new InvalidArgumentException('Habilite o módulo fiscal para emitir NF-e de saída.');
        }

        $venda = $this->finalizarVenda->handle($usuario, [
            'itens' => $dados['itens'],
            'cliente_id' => $dados['cliente_id'],
            'forma_pagamento' => $dados['forma_pagamento'] ?? 'dinheiro',
            'emitir_nfce' => false,
            'documento_nfce' => $dados['documento_destinatario'] ?? null,
            'idempotency_key' => $dados['idempotency_key'] ?? null,
        ]);

        $venda->loadMissing(['itens.produto', 'cliente', 'notaNfe']);

        if ($venda->notaNfe?->status === 'autorizada') {
            return ['venda' => $venda, 'nota' => $venda->notaNfe];
        }

        $nota = $venda->notaNfe ?: NotaNfce::query()->create([
            'venda_id' => $venda->id,
            'tipo' => 'nfe',
            'status' => 'pendente',
        ]);

        $nota = $this->emitirNfe->handle($nota);
        $venda = $venda->fresh(['itens.produto', 'cliente', 'notaNfce', 'notaNfe']);

        return ['venda' => $venda, 'nota' => $nota];
    }
}
