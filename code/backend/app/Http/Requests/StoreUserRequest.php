<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('login')) {
            $merge['login'] = strtolower(trim((string) $this->input('login')));
        }
        if ($this->exists('email')) {
            $email = trim((string) $this->input('email'));
            $merge['email'] = $email === '' ? null : strtolower($email);
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'login' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9._-]+$/', 'unique:users,login'],
            'email' => ['nullable', 'string', 'max:255', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:120'],
            'role' => ['required', Rule::in(['operador', 'admin'])],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.regex' => 'Use apenas letras minúsculas, números, ponto, hífen ou underscore.',
            'login.unique' => 'Este usuário de acesso já existe.',
        ];
    }
}
