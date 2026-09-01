<?php

namespace App\Http\Controllers\Api;

use App\Actions\EmitirNfceSincrono;
use App\Actions\FinalizarVenda;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmitirNfceVendaRequest;
use App\Http\Requests\FinalizarVendaRequest;
use App\Models\NotaNfce;
use App\Models\Venda;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class VendaController extends Controller
{
    use ApiResponse;

    public function finalizar(FinalizarVendaRequest $request, FinalizarVenda $action): JsonResponse
    {
        $dados = $request->validated();
        $idemKey = $request->header('Idempotency-Key');
        if (is_string($idemKey) && trim($idemKey) !== '') {
            $dados['idempotency_key'] = trim($idemKey);
        }

        $jaExistia = ! empty($dados['idempotency_key'])
            && Venda::query()->where('idempotency_key', $dados['idempotency_key'])->exists();

        try {
            $venda = $action->handle($request->user(), $dados);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['venda' => [$e->getMessage()]], 422);
        }

        $venda->load(['itens.produto', 'cliente', 'notaNfce']);

        return $this->ok($venda, 'Venda finalizada', $jaExistia ? 200 : 201);
    }

    public function show(Venda $venda): JsonResponse
    {
        return $this->ok($venda->load(['itens.produto', 'cliente', 'notaNfce', 'usuario:id,name']));
    }

    public function emitirNfce(EmitirNfceVendaRequest $request, Venda $venda, EmitirNfceSincrono $emitir): JsonResponse
    {
        $venda->load('notaNfce');
        if ($venda->notaNfce?->status === 'autorizada') {
            return $this->ok($venda->fresh(['itens.produto', 'cliente', 'notaNfce']), 'NFC-e já autorizada');
        }

        $doc = $request->validated('documento_nfce') ?? '';
        if ($doc !== '') {
            $venda->documento_destinatario_nfce = $doc;
            $venda->save();
        }

        $nota = $venda->notaNfce ?: NotaNfce::query()->create([
            'venda_id' => $venda->id,
            'status' => 'pendente',
        ]);

        $nota = $emitir->handle($nota);

        return $this->ok(
            $venda->fresh(['itens.produto', 'cliente', 'notaNfce']),
            $nota->status === 'autorizada' ? 'NFC-e autorizada' : 'NFC-e não autorizada'
        );
    }
}
