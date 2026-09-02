<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class ConsultarCep
{
    /**
     * @return array{
     *   cep: string,
     *   logradouro: string|null,
     *   complemento: string|null,
     *   bairro: string|null,
     *   cidade: string|null,
     *   uf: string|null,
     *   codigo_ibge: string|null
     * }
     */
    public function handle(string $cep): array
    {
        $cep = preg_replace('/\D/', '', $cep) ?? '';
        if (strlen($cep) !== 8) {
            throw new InvalidArgumentException('CEP deve ter 8 dígitos.');
        }

        $resposta = Http::timeout(5)
            ->retry(2, 200)
            ->acceptJson()
            ->get("https://viacep.com.br/ws/{$cep}/json/");

        $resposta->throw();
        $dados = $resposta->json();

        if (! is_array($dados) || ! empty($dados['erro'])) {
            throw new InvalidArgumentException('CEP não encontrado.');
        }

        return [
            'cep' => $cep,
            'logradouro' => $dados['logradouro'] ?? null,
            'complemento' => $dados['complemento'] ?? null,
            'bairro' => $dados['bairro'] ?? null,
            'cidade' => $dados['localidade'] ?? null,
            'uf' => isset($dados['uf']) ? strtoupper((string) $dados['uf']) : null,
            'codigo_ibge' => isset($dados['ibge']) ? preg_replace('/\D/', '', (string) $dados['ibge']) : null,
        ];
    }
}
