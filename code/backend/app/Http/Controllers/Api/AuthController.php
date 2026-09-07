<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\Auditoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request): JsonResponse
    {
        $ident = $request->validated('login');

        $user = User::query()
            ->where(function ($q) use ($ident) {
                $q->where('login', $ident)->orWhere('email', $ident);
            })
            ->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            Auditoria::registrar(
                'auth.login_falhou',
                'Tentativa de login falhou.',
                payload: ['login' => $ident, 'motivo' => 'credenciais'],
            );
            throw ValidationException::withMessages([
                'login' => ['Credenciais inválidas.'],
            ]);
        }

        if (! $user->ativo) {
            Auditoria::registrar(
                'auth.login_falhou',
                'Tentativa de login em conta inativa.',
                payload: ['login' => $ident, 'motivo' => 'inativa'],
                usuario: $user,
            );
            throw ValidationException::withMessages([
                'login' => ['Conta inativa.'],
            ]);
        }

        $device = $request->validated('device_name') ?: 'desktop';
        $token = $user->createToken($device)->plainTextToken;

        Auditoria::registrar('auth.login', 'Entrou na plataforma.', usuario: $user);

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

        Auditoria::registrar('auth.logout', 'Saiu da plataforma.');

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
