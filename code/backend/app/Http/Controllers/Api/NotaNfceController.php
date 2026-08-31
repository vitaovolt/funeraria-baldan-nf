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
        $termo = $request->query('q');

        $notas = NotaNfce::query()
            ->with(['venda.cliente', 'venda.itens.produto'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($termo, function ($q) use ($termo) {
                $q->where(function ($inner) use ($termo) {
                    if (ctype_digit((string) $termo)) {
                        $inner->where('id', (int) $termo)
                            ->orWhere('venda_id', (int) $termo);
                    }
                    $inner->orWhere('status', 'ilike', '%'.$termo.'%')
                        ->orWhere('chave', 'ilike', '%'.$termo.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->okPage($notas);
    }

    public function show(NotaNfce $nota): JsonResponse
    {
        return $this->ok($nota->load(['venda.cliente', 'venda.itens.produto']));
    }
}
