<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracaoFiscal extends Model
{
    protected $table = 'configuracoes_fiscais';

    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'inscricao_estadual',
        'regime_tributario',
        'ambiente_nfce',
        'serie_nfce',
        'proximo_numero_nfce',
        'certificado_path',
        'certificado_nome',
        'certificado_validade',
        'uf',
        'municipio',
        'codigo_ibge',
        'modulo_fiscal_ativo',
    ];

    protected $attributes = [
        'regime_tributario' => 'simples',
        'ambiente_nfce' => 'homologacao',
        'serie_nfce' => 1,
        'proximo_numero_nfce' => 1,
        'uf' => 'SP',
        'modulo_fiscal_ativo' => false,
    ];

    protected $hidden = [
        'certificado_path',
    ];

    protected $appends = [
        'tem_certificado',
    ];

    protected function casts(): array
    {
        return [
            'serie_nfce' => 'integer',
            'proximo_numero_nfce' => 'integer',
            'certificado_validade' => 'datetime',
            'modulo_fiscal_ativo' => 'boolean',
        ];
    }

    public function getTemCertificadoAttribute(): bool
    {
        return filled($this->attributes['certificado_path'] ?? null);
    }

    public static function moduloAtivo(): bool
    {
        $config = static::query()->first();

        return (bool) ($config?->modulo_fiscal_ativo);
    }
}
