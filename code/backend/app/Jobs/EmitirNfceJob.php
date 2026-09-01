<?php

namespace App\Jobs;

use App\Actions\EmitirNfceSincrono;
use App\Models\NotaNfce;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class EmitirNfceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public int $notaNfceId)
    {
        $this->onQueue('fiscal');
    }

    public function handle(EmitirNfceSincrono $emitir): void
    {
        $nota = NotaNfce::query()->find($this->notaNfceId);
        if (! $nota || $nota->status === 'autorizada') {
            return;
        }

        $emitir->handle($nota);
    }

    public function failed(?Throwable $e): void
    {
        $nota = NotaNfce::query()->find($this->notaNfceId);
        if (! $nota) {
            return;
        }

        $nota->update([
            'status' => 'erro',
            'mensagem_erro' => $e?->getMessage() ?: 'Falha ao emitir NFC-e',
        ]);
    }
}
