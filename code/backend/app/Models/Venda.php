<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venda extends Model
{
    protected $fillable = [
        'sessao_caixa_id',
        'user_id',
        'cliente_id',
        'status',
        'subtotal',
        'desconto_tipo',
        'desconto_valor',
        'total',
        'forma_pagamento',
        'documento_destinatario_nfce',
        'valor_recebido',
        'idempotency_key',
        'finalizada_em',
    ];

    protected $attributes = [
        'status' => 'finalizada',
        'desconto_tipo' => 'nenhum',
        'desconto_valor' => 0,
        'forma_pagamento' => 'dinheiro',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'desconto_valor' => 'decimal:2',
            'total' => 'decimal:2',
            'valor_recebido' => 'decimal:2',
            'finalizada_em' => 'datetime',
        ];
    }

    public function sessaoCaixa(): BelongsTo
    {
        return $this->belongsTo(SessaoCaixa::class, 'sessao_caixa_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemVenda::class);
    }

    public function notaNfce(): HasOne
    {
        return $this->hasOne(NotaNfce::class)->where('tipo', 'nfce');
    }

    public function notaNfe(): HasOne
    {
        return $this->hasOne(NotaNfce::class)->where('tipo', 'nfe');
    }

    public function notasFiscais(): HasMany
    {
        return $this->hasMany(NotaNfce::class);
    }
}
