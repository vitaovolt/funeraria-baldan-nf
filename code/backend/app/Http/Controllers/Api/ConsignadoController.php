<?php

namespace App\Http\Controllers\Api;

use App\Actions\ConverterConsignadoEmVenda;
use App\Actions\CriarConsignado;
use App\Actions\DevolverConsignado;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConverterConsignadoRequest;
use App\Http\Requests\DevolverConsignadoRequest;
use App\Http\Requests\StoreConsignadoRequest;
use App\Models\Consignado;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ConsignadoController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $termo = $request->query('q');

        $itens = Consignado::query()
            ->with(['cliente', 'itens.produto'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->query('abertos') === '1', fn ($q) => $q->abertos())
            ->when($termo, function ($q) use ($termo) {
                $q->where(function ($inner) use ($termo) {
                    if (ctype_digit((string) $termo)) {
                        $inner->where('id', (int) $termo);
                    }
                    $inner->orWhereHas('cliente', function ($c) use ($termo) {
                        $c->where('nome', 'ilike', '%'.$termo.'%')
                            ->orWhere('documento', 'ilike', '%'.$termo.'%');
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->okPage($itens);
    }

    public function store(StoreConsignadoRequest $request, CriarConsignado $action): JsonResponse
    {
        try {
            $consignado = $action->handle($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['consignado' => [$e->getMessage()]], 422);
        }

        return $this->ok($consignado, 'Consignado criado', 201);
    }

    public function show(Consignado $consignado): JsonResponse
    {
        return $this->ok($consignado->load(['cliente', 'itens.produto', 'usuario:id,name,email']));
    }

    public function devolver(
        DevolverConsignadoRequest $request,
        Consignado $consignado,
        DevolverConsignado $action
    ): JsonResponse {
        try {
            $atualizado = $action->handle($consignado, $request->validated('itens'), $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['consignado' => [$e->getMessage()]], 422);
        }

        return $this->ok($atualizado, 'Devolução registrada');
    }

    public function converter(
        ConverterConsignadoRequest $request,
        Consignado $consignado,
        ConverterConsignadoEmVenda $action
    ): JsonResponse {
        try {
            $emitir = ! array_key_exists('emitir_nfce', $request->validated())
                || filter_var($request->validated('emitir_nfce'), FILTER_VALIDATE_BOOLEAN);
            $venda = $action->handle(
                $consignado,
                $request->user(),
                $request->validated('itens'),
                $request->validated('forma_pagamento') ?? 'dinheiro',
                $emitir,
                $request->validated('documento_nfce') ?: null,
            );
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['consignado' => [$e->getMessage()]], 422);
        }

        return $this->ok($venda, 'Consignado convertido em venda', 201);
    }
}
