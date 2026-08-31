<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdutoRequest;
use App\Http\Requests\UpdateProdutoRequest;
use App\Models\Produto;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $itens = Produto::query()
            ->with(['marca', 'categoria'])
            ->busca($request->query('q'))
            ->when($request->filled('categoria_id'), fn ($q) => $q->where('categoria_id', $request->query('categoria_id')))
            ->when($request->filled('marca_id'), fn ($q) => $q->where('marca_id', $request->query('marca_id')))
            ->when($request->has('ativo'), fn ($q) => $q->where('ativo', filter_var($request->query('ativo'), FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('descricao')
            ->paginate($this->perPage($request));

        return $this->okPage($itens);
    }

    public function store(StoreProdutoRequest $request): JsonResponse
    {
        $produto = Produto::query()->create($request->validated());

        return $this->ok($produto->load(['marca', 'categoria']), 'Produto criado', 201);
    }

    public function show(Produto $produto): JsonResponse
    {
        return $this->ok($produto->load(['marca', 'categoria']));
    }

    public function update(UpdateProdutoRequest $request, Produto $produto): JsonResponse
    {
        $produto->update($request->validated());

        return $this->ok($produto->fresh()->load(['marca', 'categoria']), 'Produto atualizado');
    }

    public function destroy(Produto $produto): JsonResponse
    {
        $produto->delete();

        return $this->ok(null, 'Produto excluído');
    }
}
