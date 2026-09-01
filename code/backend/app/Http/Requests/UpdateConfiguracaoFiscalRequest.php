<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracaoFiscalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('cnpj')) {
            $merge['cnpj'] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input('cnpj')) ?? '');
        }
        if ($this->has('codigo_ibge')) {
            $merge['codigo_ibge'] = preg_replace('/\D/', '', (string) $this->input('codigo_ibge')) ?: null;
        }
        if ($this->has('inscricao_estadual')) {
            $merge['inscricao_estadual'] = preg_replace('/\D/', '', (string) $this->input('inscricao_estadual')) ?: null;
        }
        if ($this->exists('regime_tributario') && ! $this->input('regime_tributario')) {
            $merge['regime_tributario'] = 'simples';
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'razao_social' => ['sometimes', 'nullable', 'string', 'max:255'],
            'nome_fantasia' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cnpj' => ['sometimes', 'required', 'string', 'max:18'],
            'inscricao_estadual' => ['sometimes', 'nullable', 'string', 'max:30'],
            'regime_tributario' => ['sometimes', 'nullable', 'in:simples,lucro_presumido,lucro_real'],
            'ambiente_nfce' => ['sometimes', 'in:homologacao,producao'],
            'serie_nfce' => ['sometimes', 'integer', 'min:1'],
            'proximo_numero_nfce' => ['sometimes', 'integer', 'min:1'],
            'certificado_nome' => ['nullable', 'string', 'max:255'],
            'certificado_validade' => ['nullable', 'date'],
            'uf' => ['sometimes', 'nullable', 'string', 'size:2'],
            'municipio' => ['sometimes', 'nullable', 'string', 'max:120'],
            'codigo_ibge' => ['sometimes', 'nullable', 'string', 'max:7'],
        ];
    }

    public function messages(): array
    {
        return [
            'cnpj.required' => 'Informe o CNPJ da empresa (usado na NFC-e).',
            'regime_tributario.in' => 'Escolha o regime: Simples, Lucro presumido ou Lucro real.',
            'uf.size' => 'UF deve ter 2 letras.',
        ];
    }
}
