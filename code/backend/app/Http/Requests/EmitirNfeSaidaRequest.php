<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmitirNfeSaidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('documento_destinatario')) {
            $this->merge([
                'documento_destinatario' => preg_replace('/\D/', '', (string) $this->input('documento_destinatario')) ?? '',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['required', 'integer', 'exists:produtos,id'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
            'forma_pagamento' => ['sometimes', 'in:dinheiro,pix,cartao,cartao_credito,cartao_debito'],
            'documento_destinatario' => ['nullable', 'string', 'regex:/^$|^\d{11}$|^\d{14}$/'],
        ];
    }
}
