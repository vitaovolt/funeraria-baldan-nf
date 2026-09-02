<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
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
        $clienteId = $this->route('cliente')?->id ?? $this->route('cliente');

        return [
            'tipo' => ['sometimes', 'required', 'in:pf,pj'],
            'documento' => [
                'sometimes',
                'required',
                'string',
                'max:18',
                Rule::unique('clientes', 'documento')->ignore($clienteId),
            ],
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
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
