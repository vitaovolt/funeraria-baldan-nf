<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsignadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'observacao' => ['nullable', 'string', 'max:255'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['required', 'integer', 'exists:produtos,id'],
            'itens.*.quantidade' => ['required', 'numeric', 'min:0.001'],
        ];
    }
}
