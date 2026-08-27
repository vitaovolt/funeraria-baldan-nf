<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoEstoque extends Model
{
    protected $table = 'movimentacoes_estoque';

    protected $fillable = [
        'produto_id',
        'user_id',
        'tipo',
        'quantidade',
        'saldo_anterior',
        'saldo_posterior',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:3',
            'saldo_anterior' => 'decimal:3',
            'saldo_posterior' => 'decimal:3',
        ];
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
