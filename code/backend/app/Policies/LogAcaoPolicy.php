<?php

namespace App\Policies;

use App\Models\LogAcao;
use App\Models\User;

class LogAcaoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo && $user->isAdmin();
    }

    public function view(User $user, LogAcao $logAcao): bool
    {
        return $user->ativo && $user->isAdmin();
    }
}
