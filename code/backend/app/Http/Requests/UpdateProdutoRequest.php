<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $produtoId = $this->route('produto')?->id ?? $this->route('produto');

        return [
            'marca_id' => ['nullable', 'integer', 'exists:marcas,id'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'codigo_barras' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                Rule::unique('produtos', 'codigo_barras')->ignore($produtoId),
            ],
            'descricao' => ['sometimes', 'required', 'string', 'max:255'],
            'referencia' => ['nullable', 'string', 'max:64'],
            'ncm' => ['nullable', 'string', 'max:10'],
            'custo' => ['sometimes', 'numeric', 'min:0'],
            'preco_venda' => ['sometimes', 'numeric', 'min:0'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
