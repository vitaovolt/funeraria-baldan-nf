<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BaldanTokenCommand extends Command
{
    protected $signature = 'baldan:token {email=operador@baldan.local}';

    protected $description = 'Emite token Sanctum para smoke F1 (API)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("Usuário {$email} não encontrado. Rode: php artisan db:seed");

            return self::FAILURE;
        }

        $token = $user->createToken('smoke-f1')->plainTextToken;
        $this->line($token);

        return self::SUCCESS;
    }
}
