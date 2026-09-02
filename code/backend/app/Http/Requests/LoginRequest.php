<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $login = $this->input('login') ?? $this->input('email');
        if ($login !== null) {
            $this->merge([
                'login' => strtolower(trim((string) $login)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ];
    }
}
