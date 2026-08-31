<?php

namespace App\Http\Controllers\Api;

use App\Actions\AbrirSessaoCaixa;
use App\Actions\FecharSessaoCaixa;
use App\Actions\RegistrarSangria;
use App\Http\Controllers\Controller;
use App\Http\Requests\AbrirCaixaRequest;
use App\Http\Requests\RegistrarSangriaRequest;
use App\Models\SessaoCaixa;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CaixaController extends Controller
{
    use ApiResponse;

    public function atual(): JsonResponse
    {
        $caixa = SessaoCaixa::query()
            ->abertas()
            ->with(['usuario:id,name,email', 'sangrias'])
            ->first();

        return $this->ok($caixa);
    }

    public function abrir(AbrirCaixaRequest $request, AbrirSessaoCaixa $action): JsonResponse
    {
        try {
            $caixa = $action->handle(
                $request->user(),
                (float) ($request->validated('valor_abertura') ?? 0)
            );
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['caixa' => [$e->getMessage()]], 422);
        }

        return $this->ok($caixa->load('usuario:id,name,email'), 'Caixa aberto', 201);
    }

    public function sangria(RegistrarSangriaRequest $request, RegistrarSangria $action): JsonResponse
    {
        try {
            $sangria = $action->handle(
                $request->user(),
                (float) $request->validated('valor'),
                $request->validated('motivo')
            );
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['sangria' => [$e->getMessage()]], 422);
        }

        return $this->ok($sangria->load('usuario:id,name,email'), 'Sangria registrada', 201);
    }

    public function fechar(FecharSessaoCaixa $action): JsonResponse
    {
        try {
            $caixa = $action->handle(request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['caixa' => [$e->getMessage()]], 422);
        }

        return $this->ok($caixa, 'Caixa fechado');
    }

    public function fechamento(): JsonResponse
    {
        $caixa = SessaoCaixa::query()
            ->where('status', 'fechada')
            ->with(['usuario:id,name,email', 'sangrias', 'vendas.notaNfce'])
            ->orderByDesc('fechado_em')
            ->first();

        if (! $caixa) {
            $aberta = SessaoCaixa::query()->abertas()->with(['sangrias', 'vendas'])->first();
            if (! $aberta) {
                return $this->ok(null);
            }

            $totalVendas = (float) $aberta->vendas->sum('total');
            $totalSangrias = (float) $aberta->sangrias->sum('valor');
            $vendasDinheiro = (float) $aberta->vendas->where('forma_pagamento', 'dinheiro')->sum('total');

            return $this->ok([
                'sessao' => $aberta,
                'preview' => true,
                'total_vendas' => round($totalVendas, 2),
                'total_sangrias' => round($totalSangrias, 2),
                'total_dinheiro_esperado' => round((float) $aberta->valor_abertura + $vendasDinheiro - $totalSangrias, 2),
            ]);
        }

        return $this->ok([
            'sessao' => $caixa,
            'preview' => false,
            'total_vendas' => (float) $caixa->total_vendas,
            'total_sangrias' => (float) $caixa->total_sangrias,
            'total_dinheiro_esperado' => (float) $caixa->total_dinheiro_esperado,
        ]);
    }

    public function vendasDoDia(Request $request): JsonResponse
    {
        $caixa = SessaoCaixa::query()->abertas()->first();
        if (! $caixa) {
            return $this->ok([], 'Operação realizada com sucesso', 200, [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $this->perPage($request),
                'total' => 0,
            ]);
        }

        $termo = $request->query('q');

        $vendas = $caixa->vendas()
            ->with(['cliente', 'itens.produto', 'notaNfce'])
            ->when($termo, function ($q) use ($termo) {
                $q->where(function ($inner) use ($termo) {
                    if (ctype_digit((string) $termo)) {
                        $inner->where('id', (int) $termo);
                    }
                    $inner->orWhereHas('cliente', function ($c) use ($termo) {
                        $c->where('nome', 'ilike', '%'.$termo.'%');
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->okPage($vendas);
    }
}
