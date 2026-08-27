<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbrirCaixaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valor_abertura' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
