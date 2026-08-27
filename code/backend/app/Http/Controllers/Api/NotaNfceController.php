<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotaNfce;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotaNfceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $notas = NotaNfce::query()
            ->with(['venda.cliente', 'venda.itens.produto'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return $this->ok($notas);
    }

    public function show(NotaNfce $nota): JsonResponse
    {
        return $this->ok($nota->load(['venda.cliente', 'venda.itens.produto']));
    }
}
