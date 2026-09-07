<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogAcao;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogAcaoController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LogAcao::class);

        $itens = LogAcao::query()
            ->busca($request->query('q'))
            ->when($request->filled('acao'), fn ($q) => $q->where('acao', $request->query('acao')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', (int) $request->query('user_id')))
            ->when($request->filled('de'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('de')))
            ->when($request->filled('ate'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('ate')))
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->okPage($itens);
    }
}
