<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'login',
        'email',
        'password',
        'ativo',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'ativo' => true,
        'role' => 'operador',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOperador(): bool
    {
        return $this->role === 'operador';
    }

    public function scopeBusca($query, ?string $termo)
    {
        if ($termo === null || trim($termo) === '') {
            return $query;
        }

        $termo = trim($termo);

        return $query->where(function ($q) use ($termo) {
            $q->where('name', 'ilike', '%'.$termo.'%')
                ->orWhere('login', 'ilike', '%'.$termo.'%')
                ->orWhere('email', 'ilike', '%'.$termo.'%');
        });
    }
}
