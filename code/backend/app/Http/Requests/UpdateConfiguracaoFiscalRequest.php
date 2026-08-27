<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracaoFiscalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('cnpj')) {
            $this->merge([
                'cnpj' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input('cnpj')) ?? ''),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'razao_social' => ['sometimes', 'required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['sometimes', 'required', 'string', 'max:18'],
            'inscricao_estadual' => ['nullable', 'string', 'max:30'],
            'regime_tributario' => ['sometimes', 'string', 'max:40'],
            'ambiente_nfce' => ['sometimes', 'in:homologacao,producao'],
            'serie_nfce' => ['sometimes', 'integer', 'min:1'],
            'proximo_numero_nfce' => ['sometimes', 'integer', 'min:1'],
            'certificado_nome' => ['nullable', 'string', 'max:255'],
            'certificado_validade' => ['nullable', 'date'],
            'uf' => ['sometimes', 'string', 'size:2'],
            'municipio' => ['nullable', 'string', 'max:120'],
            'codigo_ibge' => ['nullable', 'string', 'max:7'],
        ];
    }
}
