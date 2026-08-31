<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $itens = Cliente::query()
            ->with('dependentes')
            ->busca($request->query('q'))
            ->when($request->has('tem_plano'), fn ($q) => $q->where('tem_plano', filter_var($request->query('tem_plano'), FILTER_VALIDATE_BOOLEAN)))
            ->when($request->has('ativo'), fn ($q) => $q->where('ativo', filter_var($request->query('ativo'), FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('nome')
            ->paginate($this->perPage($request));

        return $this->okPage($itens);
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $cliente = Cliente::query()->create($request->validated());

        return $this->ok($cliente->load('dependentes'), 'Cliente criado', 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        return $this->ok($cliente->load('dependentes'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $cliente->update($request->validated());

        return $this->ok($cliente->fresh()->load('dependentes'), 'Cliente atualizado');
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $cliente->delete();

        return $this->ok(null, 'Cliente excluído');
    }
}
