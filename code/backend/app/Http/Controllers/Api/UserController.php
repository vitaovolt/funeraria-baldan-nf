<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $itens = User::query()
            ->busca($request->query('q'))
            ->when($request->has('ativo'), fn ($q) => $q->where('ativo', filter_var($request->query('ativo'), FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return $this->okPage($itens);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $dados = $request->validated();
        if (empty($dados['email'])) {
            $dados['email'] = $dados['login'].'@baldan.local';
        }
        $dados['ativo'] = array_key_exists('ativo', $dados)
            ? filter_var($dados['ativo'], FILTER_VALIDATE_BOOLEAN)
            : true;

        $user = User::query()->create($dados);

        return $this->ok(new UserResource($user), 'Usuário criado', 201);
    }

    public function show(User $usuario): JsonResponse
    {
        $this->authorize('view', $usuario);

        return $this->ok(new UserResource($usuario));
    }

    public function update(UpdateUserRequest $request, User $usuario): JsonResponse
    {
        $this->authorize('update', $usuario);

        $dados = $request->validated();
        if (array_key_exists('password', $dados) && ($dados['password'] === null || $dados['password'] === '')) {
            unset($dados['password']);
        }
        if (array_key_exists('email', $dados) && $dados['email'] === null) {
            $login = $dados['login'] ?? $usuario->login;
            $dados['email'] = $login.'@baldan.local';
        }
        if (array_key_exists('ativo', $dados)) {
            $dados['ativo'] = filter_var($dados['ativo'], FILTER_VALIDATE_BOOLEAN);
            if (! $dados['ativo'] && $request->user()?->id === $usuario->id) {
                return $this->fail('Você não pode desativar a própria conta.', [
                    'ativo' => ['Operação não permitida.'],
                ]);
            }
        }

        $usuario->update($dados);

        return $this->ok(new UserResource($usuario->fresh()), 'Usuário atualizado');
    }

    public function destroy(Request $request, User $usuario): JsonResponse
    {
        $this->authorize('delete', $usuario);

        if ($request->user()?->id === $usuario->id) {
            return $this->fail('Você não pode excluir a própria conta.', [
                'usuario' => ['Operação não permitida.'],
            ]);
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return $this->ok(null, 'Usuário excluído');
    }
}
