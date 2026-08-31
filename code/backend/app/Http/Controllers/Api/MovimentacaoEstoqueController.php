<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegistrarMovimentacaoEstoque;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMovimentacaoEstoqueRequest;
use App\Models\Produto;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class MovimentacaoEstoqueController extends Controller
{
    use ApiResponse;

    public function index(Request $request, Produto $produto): JsonResponse
    {
        $termo = $request->query('q');

        $itens = $produto->movimentacoes()
            ->with('usuario:id,name,email')
            ->when($termo, function ($q) use ($termo) {
                $q->where(function ($inner) use ($termo) {
                    $inner->where('tipo', 'ilike', '%'.$termo.'%')
                        ->orWhere('observacao', 'ilike', '%'.$termo.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->okPage($itens);
    }

    public function store(
        StoreMovimentacaoEstoqueRequest $request,
        Produto $produto,
        RegistrarMovimentacaoEstoque $action,
    ): JsonResponse {
        try {
            $mov = $action->handle($produto, $request->validated(), $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['estoque' => [$e->getMessage()]], 422);
        }

        return $this->ok([
            'movimentacao' => $mov,
            'produto' => $produto->fresh()->load(['marca', 'categoria']),
        ], 'Movimentação registrada', 201);
    }
}
