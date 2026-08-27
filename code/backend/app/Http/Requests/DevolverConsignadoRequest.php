<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DevolverConsignadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.item_id' => ['required', 'integer', 'exists:itens_consignado,id'],
            'itens.*.quantidade' => ['required', 'numeric', 'min:0.001'],
        ];
    }
}
