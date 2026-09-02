<?php

namespace App\Services\Integrations;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class ClienteFocusNfe
{
    public function enviarNfce(string $ref, array $payload, string $ambiente): Response
    {
        $request = $this->requisicao($ambiente);

        return $request->retry((int) config('focusnfe.retry', 3), 1000)
            ->post('/v2/nfce?ref='.rawurlencode($ref), $payload);
    }

    public function consultarNfce(string $ref, string $ambiente): Response
    {
        return $this->requisicao($ambiente)
            ->retry((int) config('focusnfe.retry', 3), 1000)
            ->get('/v2/nfce/'.rawurlencode($ref));
    }

    public function enviarNfe(string $ref, array $payload, string $ambiente): Response
    {
        $request = $this->requisicao($ambiente);

        return $request->retry((int) config('focusnfe.retry', 3), 1000)
            ->post('/v2/nfe?ref='.rawurlencode($ref), $payload);
    }

    public function consultarNfe(string $ref, string $ambiente): Response
    {
        return $this->requisicao($ambiente)
            ->retry((int) config('focusnfe.retry', 3), 1000)
            ->get('/v2/nfe/'.rawurlencode($ref));
    }

    public function baixarArquivo(string $caminho, string $ambiente): ?string
    {
        if ($caminho === '') {
            return null;
        }

        $url = str_starts_with($caminho, 'http')
            ? $caminho
            : rtrim($this->baseUrl($ambiente), '/').'/'.ltrim($caminho, '/');

        $resposta = $this->requisicao($ambiente)->get($url);
        if ($resposta->failed()) {
            return null;
        }

        return $resposta->body();
    }

    private function requisicao(string $ambiente)
    {
        $token = $this->token($ambiente);
        if ($token === '') {
            $var = $ambiente === 'producao' ? 'FOCUSNFE_TOKEN_PRODUCAO' : 'FOCUSNFE_TOKEN_HOMOLOGACAO';
            throw new InvalidArgumentException("Token Focus NFe não configurado ({$var}).");
        }

        return Http::timeout((int) config('focusnfe.timeout', 30))
            ->baseUrl($this->baseUrl($ambiente))
            ->withBasicAuth($token, '')
            ->acceptJson()
            ->asJson();
    }

    private function token(string $ambiente): string
    {
        return $ambiente === 'producao'
            ? (string) config('focusnfe.token_producao')
            : (string) config('focusnfe.token_homologacao');
    }

    private function baseUrl(string $ambiente): string
    {
        return $ambiente === 'producao'
            ? 'https://api.focusnfe.com.br'
            : 'https://homologacao.focusnfe.com.br';
    }
}
