<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dependente extends Model
{
    protected $fillable = [
        'cliente_id',
        'nome',
        'parentesco',
        'documento',
        'ativo',
    ];

    protected $attributes = [
        'ativo' => true,
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
