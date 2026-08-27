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
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:pf,pj'],
            'documento' => ['required', 'string', 'max:18', 'unique:clientes,documento'],
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'tem_plano' => ['sometimes', 'boolean'],
            'plano_nome' => ['nullable', 'string', 'max:120'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
