<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmitirNfeVendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('documento_destinatario')) {
            $this->merge([
                'documento_destinatario' => preg_replace('/\D/', '', (string) $this->input('documento_destinatario')) ?? '',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'documento_destinatario' => ['nullable', 'string', 'regex:/^$|^\d{11}$|^\d{14}$/'],
        ];
    }
}
