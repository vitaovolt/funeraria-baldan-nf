<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmitirNfceVendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('documento_nfce')) {
            $this->merge([
                'documento_nfce' => preg_replace('/\D/', '', (string) $this->input('documento_nfce')) ?? '',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'documento_nfce' => ['nullable', 'string', 'regex:/^$|^\d{11}$|^\d{14}$/'],
        ];
    }
}
