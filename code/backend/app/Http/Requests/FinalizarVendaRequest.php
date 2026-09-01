<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizarVendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('documento_nfce')) {
            $this->merge([
                'documento_nfce' => preg_replace('/\D/', '', (string) $this->input('documento_nfce')) ?? '',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['required', 'integer', 'exists:produtos,id'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'desconto_tipo' => ['sometimes', 'in:nenhum,percentual,valor'],
            'desconto_valor' => ['sometimes', 'numeric', 'min:0'],
            'forma_pagamento' => ['sometimes', 'string', 'max:40'],
            'emitir_nfce' => ['sometimes', 'boolean'],
            'documento_nfce' => ['nullable', 'string', 'regex:/^$|^\d{11}$|^\d{14}$/'],
            'valor_recebido' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
