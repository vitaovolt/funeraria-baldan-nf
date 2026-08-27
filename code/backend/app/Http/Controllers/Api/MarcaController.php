<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMarcaRequest;
use App\Http\Requests\UpdateMarcaRequest;
use App\Models\Marca;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $itens = Marca::query()
            ->busca($request->query('q'))
            ->when($request->has('ativo'), fn ($q) => $q->where('ativo', filter_var($request->query('ativo'), FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('nome')
            ->get();

        return $this->ok($itens);
    }

    public function store(StoreMarcaRequest $request): JsonResponse
    {
        $marca = Marca::query()->create($request->validated());

        return $this->ok($marca, 'Marca criada', 201);
    }

    public function show(Marca $marca): JsonResponse
    {
        return $this->ok($marca);
    }

    public function update(UpdateMarcaRequest $request, Marca $marca): JsonResponse
    {
        $marca->update($request->validated());

        return $this->ok($marca->fresh(), 'Marca atualizada');
    }

    public function destroy(Marca $marca): JsonResponse
    {
        $marca->delete();

        return $this->ok(null, 'Marca excluída');
    }
}
