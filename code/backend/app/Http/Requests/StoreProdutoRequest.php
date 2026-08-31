<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'marca_id' => ['nullable', 'integer', 'exists:marcas,id'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'codigo_barras' => ['required', 'string', 'max:64', 'unique:produtos,codigo_barras'],
            'descricao' => ['required', 'string', 'max:255'],
            'referencia' => ['nullable', 'string', 'max:64'],
            'ncm' => ['nullable', 'string', 'max:10'],
            'custo' => ['sometimes', 'numeric', 'min:0'],
            'preco_venda' => ['sometimes', 'numeric', 'min:0'],
            'estoque_atual' => ['sometimes', 'integer', 'min:0'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
