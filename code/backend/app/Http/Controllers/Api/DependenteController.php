<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDependenteRequest;
use App\Http\Requests\UpdateDependenteRequest;
use App\Models\Cliente;
use App\Models\Dependente;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DependenteController extends Controller
{
    use ApiResponse;

    public function index(Cliente $cliente): JsonResponse
    {
        return $this->ok($cliente->dependentes()->orderBy('nome')->get());
    }

    public function store(StoreDependenteRequest $request, Cliente $cliente): JsonResponse
    {
        $dependente = $cliente->dependentes()->create($request->validated());

        return $this->ok($dependente, 'Dependente criado', 201);
    }

    public function show(Dependente $dependente): JsonResponse
    {
        return $this->ok($dependente->load('cliente'));
    }

    public function update(UpdateDependenteRequest $request, Dependente $dependente): JsonResponse
    {
        $dependente->update($request->validated());

        return $this->ok($dependente->fresh(), 'Dependente atualizado');
    }

    public function destroy(Dependente $dependente): JsonResponse
    {
        $dependente->delete();

        return $this->ok(null, 'Dependente excluído');
    }
}
