<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consignado extends Model
{
    protected $table = 'consignados';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'status',
        'observacao',
    ];

    protected $attributes = [
        'status' => 'aberto',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemConsignado::class, 'consignado_id');
    }

    public function scopeAbertos(Builder $query): Builder
    {
        return $query->whereIn('status', ['aberto', 'parcial']);
    }
}
