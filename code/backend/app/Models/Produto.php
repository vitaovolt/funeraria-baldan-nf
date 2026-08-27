<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    protected $fillable = [
        'marca_id',
        'categoria_id',
        'codigo_barras',
        'descricao',
        'referencia',
        'ncm',
        'custo',
        'preco_venda',
        'estoque_atual',
        'ativo',
    ];

    protected $attributes = [
        'ativo' => true,
        'custo' => 0,
        'preco_venda' => 0,
        'estoque_atual' => 0,
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'custo' => 'decimal:2',
            'preco_venda' => 'decimal:2',
            'estoque_atual' => 'decimal:3',
        ];
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoEstoque::class);
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
            $q->where('descricao', 'ilike', '%'.$termo.'%')
                ->orWhere('codigo_barras', 'ilike', '%'.$termo.'%')
                ->orWhere('referencia', 'ilike', '%'.$termo.'%');
        });
    }
}
