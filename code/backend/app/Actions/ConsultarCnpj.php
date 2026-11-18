<?php

namespace App\Actions;

use App\Services\Integrations\ClienteBrasilApi;
use Illuminate\Http\Client\RequestException;
use InvalidArgumentException;
use Throwable;

class ConsultarCnpj
{
    public function __construct(private ClienteBrasilApi $cliente)
    {
    }

    /**
     * @return array{
     *   cnpj: string,
     *   nome: string,
     *   logradouro: string,
     *   numero: string,
     *   complemento: string,
     *   bairro: string,
     *   municipio: string,
     *   uf: string,
     *   cep: string
     * }
     */
    public function handle(string $cnpj): array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj) ?? '';
        if (strlen($cnpj) !== 14) {
            throw new InvalidArgumentException('CNPJ deve ter 14 dígitos. Dados inválidos para a NFC-e.');
        }

        try {
            $dados = $this->cliente->consultarCnpj($cnpj);
        } catch (RequestException $e) {
            if ($e->response?->status() === 404) {
                throw new InvalidArgumentException('CNPJ não encontrado. Dados inválidos para a NFC-e.');
            }

            throw new InvalidArgumentException('Não foi possível consultar o CNPJ do destinatário. Dados inválidos para a NFC-e.', 0, $e);
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new InvalidArgumentException('Não foi possível consultar o CNPJ do destinatário. Dados inválidos para a NFC-e.', 0, $e);
        }

        $uf = strtoupper(trim((string) ($dados['uf'] ?? '')));
        if ($uf === '') {
            throw new InvalidArgumentException('O cadastro deste CNPJ não trouxe a UF. Dados inválidos para a NFC-e.');
        }

        return [
            'cnpj' => $cnpj,
            'nome' => trim((string) ($dados['razao_social'] ?? '')),
            'logradouro' => trim((string) ($dados['logradouro'] ?? '')),
            'numero' => trim((string) ($dados['numero'] ?? '')),
            'complemento' => trim((string) ($dados['complemento'] ?? '')),
            'bairro' => trim((string) ($dados['bairro'] ?? '')),
            'municipio' => trim((string) ($dados['municipio'] ?? '')),
            'uf' => $uf,
            'cep' => preg_replace('/\D/', '', (string) ($dados['cep'] ?? '')) ?? '',
        ];
    }
}
