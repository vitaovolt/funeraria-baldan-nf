<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $fillable = [
        'tipo',
        'documento',
        'inscricao_estadual',
        'nome',
        'email',
        'telefone',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'cep',
        'tem_plano',
        'plano_nome',
        'ativo',
    ];

    protected $attributes = [
        'tipo' => 'pf',
        'tem_plano' => false,
        'ativo' => true,
    ];

    protected function casts(): array
    {
        return [
            'tem_plano' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    public function dependentes(): HasMany
    {
        return $this->hasMany(Dependente::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeBusca(Builder $query, ?string $termo): Builder
    {
        if ($termo === null || $termo === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($termo) {
            $q->where('nome', 'ilike', '%'.$termo.'%')
                ->orWhere('documento', 'ilike', '%'.$termo.'%');
        });
    }
}
