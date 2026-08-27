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
}
