<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessaoCaixa extends Model
{
    protected $table = 'sessoes_caixa';

    protected $fillable = [
        'user_id',
        'aberto_em',
        'fechado_em',
        'valor_abertura',
        'total_vendas',
        'total_sangrias',
        'total_dinheiro_esperado',
        'status',
    ];

    protected $attributes = [
        'status' => 'aberta',
        'valor_abertura' => 0,
    ];

    protected function casts(): array
    {
        return [
            'aberto_em' => 'datetime',
            'fechado_em' => 'datetime',
            'valor_abertura' => 'decimal:2',
            'total_vendas' => 'decimal:2',
            'total_sangrias' => 'decimal:2',
            'total_dinheiro_esperado' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vendas(): HasMany
    {
        return $this->hasMany(Venda::class, 'sessao_caixa_id');
    }

    public function movimentosCaixa(): HasMany
    {
        return $this->hasMany(SangriaCaixa::class, 'sessao_caixa_id');
    }

    public function sangrias(): HasMany
    {
        return $this->hasMany(SangriaCaixa::class, 'sessao_caixa_id')
            ->where(function ($q) {
                $q->where('tipo', 'sangria')->orWhereNull('tipo');
            });
    }

    public function suprimentos(): HasMany
    {
        return $this->hasMany(SangriaCaixa::class, 'sessao_caixa_id')->where('tipo', 'suprimento');
    }

    public function scopeAbertas(Builder $query): Builder
    {
        return $query->where('status', 'aberta');
    }
}
