<?php

namespace App\Console\Commands;

use App\Models\LogAcao;
use Illuminate\Console\Command;

class PruneLogsAcoes extends Command
{
    protected $signature = 'logs-acoes:prune {--meses=24 : Meses a conservar}';

    protected $description = 'Remove logs de ação mais antigos que o prazo de retenção';

    public function handle(): int
    {
        $meses = max(1, (int) $this->option('meses'));
        $limite = now()->subMonths($meses);

        $apagados = LogAcao::query()->where('created_at', '<', $limite)->delete();

        $this->info("Removidos {$apagados} logs anteriores a {$limite->toDateString()}.");

        return self::SUCCESS;
    }
}
