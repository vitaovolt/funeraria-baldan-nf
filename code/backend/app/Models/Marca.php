<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marca extends Model
{
    protected $fillable = ['nome', 'ativo'];

    protected $attributes = [
        'ativo' => true,
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
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

        return $query->where('nome', 'ilike', '%'.$termo.'%');
    }
}
