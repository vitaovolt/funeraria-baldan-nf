<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ApiResponse
{
    protected function ok(mixed $data = null, string $message = 'Operação realizada com sucesso', int $status = 200, ?array $meta = null): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => [],
        ];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function okPage(LengthAwarePaginator $paginator, string $message = 'Operação realizada com sucesso'): JsonResponse
    {
        return $this->ok(
            $paginator->items(),
            $message,
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    protected function perPage(Request $request, int $default = 15): int
    {
        return min(100, max(1, (int) $request->query('per_page', $default)));
    }

    protected function fail(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
