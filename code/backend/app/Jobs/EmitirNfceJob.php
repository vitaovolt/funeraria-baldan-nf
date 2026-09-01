<?php

namespace App\Jobs;

use App\Actions\EmitirNfceFocus;
use App\Models\NotaNfce;
use App\Services\Fiscal\EmissorNfceFake;
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

    public function handle(EmissorNfceFake $fake, EmitirNfceFocus $focus): void
    {
        $nota = NotaNfce::query()->find($this->notaNfceId);
        if (! $nota || $nota->status === 'autorizada') {
            return;
        }

        if (config('focusnfe.driver') === 'fake') {
            $fake->emitir($nota);

            return;
        }

        $focus->handle($nota);
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
