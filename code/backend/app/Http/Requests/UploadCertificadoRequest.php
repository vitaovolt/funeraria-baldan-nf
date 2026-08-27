<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCertificadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'certificado' => ['required', 'file', 'max:5120'],
            'certificado_nome' => ['nullable', 'string', 'max:255'],
            'certificado_validade' => ['nullable', 'date'],
        ];
    }
}
