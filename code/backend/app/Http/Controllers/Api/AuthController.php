<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->validated('email'))
            ->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        if (! $user->ativo) {
            throw ValidationException::withMessages([
                'email' => ['Conta inativa.'],
            ]);
        }

        $device = $request->validated('device_name') ?: 'desktop';
        $token = $user->createToken($device)->plainTextToken;

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Login realizado');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok(new UserResource($request->user()), 'Usuário autenticado');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return $this->ok(null, 'Logout realizado');
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()?->delete();
        $token = $user->createToken('refresh')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Token renovado');
    }
}
