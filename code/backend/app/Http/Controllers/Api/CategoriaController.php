<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use App\Models\Categoria;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $itens = Categoria::query()
            ->busca($request->query('q'))
            ->when($request->has('ativo'), fn ($q) => $q->where('ativo', filter_var($request->query('ativo'), FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('nome')
            ->get();

        return $this->ok($itens);
    }

    public function store(StoreCategoriaRequest $request): JsonResponse
    {
        $categoria = Categoria::query()->create($request->validated());

        return $this->ok($categoria, 'Categoria criada', 201);
    }

    public function show(Categoria $categoria): JsonResponse
    {
        return $this->ok($categoria);
    }

    public function update(UpdateCategoriaRequest $request, Categoria $categoria): JsonResponse
    {
        $categoria->update($request->validated());

        return $this->ok($categoria->fresh(), 'Categoria atualizada');
    }

    public function destroy(Categoria $categoria): JsonResponse
    {
        $categoria->delete();

        return $this->ok(null, 'Categoria excluída');
    }
}
