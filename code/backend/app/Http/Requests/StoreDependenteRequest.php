<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDependenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'parentesco' => ['nullable', 'string', 'max:40'],
            'documento' => ['nullable', 'string', 'max:18'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
