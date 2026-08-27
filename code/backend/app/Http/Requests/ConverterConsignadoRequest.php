<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConverterConsignadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'itens' => ['nullable', 'array'],
            'itens.*.item_id' => ['required_with:itens', 'integer', 'exists:itens_consignado,id'],
            'itens.*.quantidade' => ['nullable', 'numeric', 'min:0.001'],
            'forma_pagamento' => ['sometimes', 'string', 'max:40'],
        ];
    }
}
