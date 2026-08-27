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
            'tem_plano' => ['sometimes', 'boolean'],
            'plano_nome' => ['nullable', 'string', 'max:120'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
