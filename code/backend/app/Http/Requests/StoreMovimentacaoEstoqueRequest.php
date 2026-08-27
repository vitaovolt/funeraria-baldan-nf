<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovimentacaoEstoqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:entrada,saida,ajuste'],
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'observacao' => ['nullable', 'string', 'max:255'],
        ];
    }
}
