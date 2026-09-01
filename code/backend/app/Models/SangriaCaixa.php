<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SangriaCaixa extends Model
{
    protected $table = 'sangrias_caixa';

    protected $fillable = [
        'sessao_caixa_id',
        'user_id',
        'valor',
        'tipo',
        'motivo',
    ];

    protected $attributes = [
        'tipo' => 'sangria',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
        ];
    }

    public function ehSuprimento(): bool
    {
        return $this->tipo === 'suprimento';
    }

    public function sessaoCaixa(): BelongsTo
    {
        return $this->belongsTo(SessaoCaixa::class, 'sessao_caixa_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
