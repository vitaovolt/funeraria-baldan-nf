<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizarVendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
        ];
    }
}
