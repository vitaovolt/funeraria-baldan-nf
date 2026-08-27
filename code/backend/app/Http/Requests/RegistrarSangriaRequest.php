<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarSangriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valor' => ['required', 'numeric', 'min:0.01'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}
