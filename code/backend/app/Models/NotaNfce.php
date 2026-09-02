<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaNfce extends Model
{
    protected $table = 'notas_nfce';

    protected $fillable = [
        'venda_id',
        'tipo',
        'status',
        'chave',
        'numero',
        'serie',
        'protocolo',
        'xml_path',
        'danfe_path',
        'mensagem_erro',
        'enviada_em',
        'autorizada_em',
    ];

    protected $attributes = [
        'status' => 'pendente',
        'tipo' => 'nfce',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'serie' => 'integer',
            'enviada_em' => 'datetime',
            'autorizada_em' => 'datetime',
        ];
    }

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class);
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', 'pendente');
    }

    public function scopeNfce(Builder $query): Builder
    {
        return $query->where('tipo', 'nfce');
    }

    public function scopeNfe(Builder $query): Builder
    {
        return $query->where('tipo', 'nfe');
    }

    public function isNfe(): bool
    {
        return $this->tipo === 'nfe';
    }
}
