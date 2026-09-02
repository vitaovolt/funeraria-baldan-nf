<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        if ($this->exists('password') && $this->input('password') === '') {
            $merge['password'] = null;
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $userId = $this->route('usuario')?->id ?? $this->route('usuario');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'login' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'login')->ignore($userId),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:6', 'max:120'],
            'role' => ['sometimes', 'required', Rule::in(['operador', 'admin'])],
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
