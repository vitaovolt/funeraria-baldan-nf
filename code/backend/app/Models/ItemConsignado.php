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
            'quantidade' => 'integer',
            'quantidade_devolvida' => 'integer',
            'quantidade_vendida' => 'integer',
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

    public function quantidadePendente(): int
    {
        return (int) $this->quantidade
            - (int) $this->quantidade_devolvida
            - (int) $this->quantidade_vendida;
    }
}
