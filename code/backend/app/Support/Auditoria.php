<?php

namespace App\Support;

use App\Models\LogAcao;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class Auditoria
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function registrar(
        string $acao,
        ?string $descricao = null,
        ?Model $recurso = null,
        array $payload = [],
        ?User $usuario = null,
    ): void {
        try {
            $usuario ??= auth()->user();
            $request = request();

            LogAcao::query()->create([
                'user_id' => $usuario?->id,
                'user_nome' => $usuario?->name,
                'user_login' => $usuario?->login,
                'acao' => $acao,
                'recurso_tipo' => $recurso ? class_basename($recurso) : null,
                'recurso_id' => $recurso?->getKey(),
                'descricao' => $descricao ?? (LogAcao::ACOES[$acao] ?? $acao),
                'payload' => self::sanitizar($payload),
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function sanitizar(array $payload): array
    {
        $bloqueados = [
            'password', 'password_confirmation', 'senha', 'token', 'plainTextToken',
            'certificado', 'certificate', 'secret', 'api_key', 'authorization',
        ];

        $limpo = [];
        foreach ($payload as $chave => $valor) {
            if (in_array(strtolower((string) $chave), $bloqueados, true)) {
                continue;
            }
            if (is_array($valor)) {
                $limpo[$chave] = self::sanitizar($valor);

                continue;
            }
            $limpo[$chave] = $valor;
        }

        return $limpo;
    }
}
