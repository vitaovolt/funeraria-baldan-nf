<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemConsignado extends Model
{
    protected $table = 'itens_consignado';

    protected $fillable = [
        'consignado_id',
        'produto_id',
        'quantidade',
        'quantidade_devolvida',
        'quantidade_vendida',
        'preco_unitario',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:3',
            'quantidade_devolvida' => 'decimal:3',
            'quantidade_vendida' => 'decimal:3',
            'preco_unitario' => 'decimal:2',
        ];
    }

    public function consignado(): BelongsTo
    {
        return $this->belongsTo(Consignado::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function quantidadePendente(): float
    {
        return (float) $this->quantidade
            - (float) $this->quantidade_devolvida
            - (float) $this->quantidade_vendida;
    }
}
