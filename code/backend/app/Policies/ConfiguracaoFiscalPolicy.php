<?php

namespace App\Policies;

use App\Models\ConfiguracaoFiscal;
use App\Models\User;

class ConfiguracaoFiscalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo;
    }

    public function view(User $user, ConfiguracaoFiscal $config): bool
    {
        return $user->ativo;
    }

    public function update(User $user): bool
    {
        return $user->ativo && $user->isAdmin();
    }

    public function uploadCertificado(User $user): bool
    {
        return $user->ativo && $user->isAdmin();
    }
}
