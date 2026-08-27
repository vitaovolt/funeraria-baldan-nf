<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDependenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'parentesco' => ['nullable', 'string', 'max:40'],
            'documento' => ['nullable', 'string', 'max:18'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
