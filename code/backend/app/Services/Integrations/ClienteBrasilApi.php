<?php

namespace App\Services\Integrations;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ClienteBrasilApi
{
    /**
     * @return array<string, mixed>
     */
    public function consultarCnpj(string $cnpj): array
    {
        $resposta = Http::timeout(5)
            ->retry(2, 200, function ($exception) {
                if ($exception instanceof ConnectionException) {
                    return true;
                }

                return $exception instanceof RequestException && $exception->response?->serverError();
            })
            ->acceptJson()
            ->get("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");

        $resposta->throw();

        $dados = $resposta->json();

        return is_array($dados) ? $dados : [];
    }
}
