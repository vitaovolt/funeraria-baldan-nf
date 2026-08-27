<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAtivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->ativo) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Conta inativa.',
                'errors' => ['auth' => ['Conta inativa.']],
            ], 403);
        }

        return $next($request);
    }
}
