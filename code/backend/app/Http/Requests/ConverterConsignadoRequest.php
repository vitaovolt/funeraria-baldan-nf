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
            'itens.*.quantidade' => ['nullable', 'integer', 'min:1'],
            'forma_pagamento' => ['sometimes', 'string', 'max:40'],
            'emitir_nfce' => ['sometimes', 'boolean'],
            'documento_nfce' => ['nullable', 'string', 'max:18'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('documento_nfce')) {
            $this->merge([
                'documento_nfce' => preg_replace('/\D/', '', (string) $this->input('documento_nfce')) ?? '',
            ]);
        }
    }
}
