<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('documento')) {
            $this->merge([
                'documento' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input('documento')) ?? ''),
            ]);
        }
        if ($this->has('cep')) {
            $this->merge([
                'cep' => preg_replace('/\D/', '', (string) $this->input('cep')) ?: null,
            ]);
        }
        if ($this->has('uf')) {
            $uf = strtoupper(trim((string) $this->input('uf')));
            $this->merge(['uf' => $uf !== '' ? $uf : null]);
        }
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:pf,pj'],
            'documento' => ['required', 'string', 'max:18', 'unique:clientes,documento'],
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'inscricao_estadual' => ['nullable', 'string', 'max:30'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:120'],
            'bairro' => ['nullable', 'string', 'max:120'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'uf' => ['nullable', 'string', 'size:2'],
            'cep' => ['nullable', 'string', 'max:10'],
            'tem_plano' => ['sometimes', 'boolean'],
            'plano_nome' => ['nullable', 'string', 'max:120'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
