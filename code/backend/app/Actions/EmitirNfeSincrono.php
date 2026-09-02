<?php

namespace App\Actions;

use App\Models\NotaNfce;
use App\Services\Fiscal\EmissorNfceFake;
use Throwable;

class EmitirNfeSincrono
{
    public function __construct(
        private EmissorNfceFake $fake,
        private EmitirNfeFocus $focus,
    ) {}

    public function handle(NotaNfce $nota): NotaNfce
    {
        set_time_limit(120);
        $nota->refresh();
        if ($nota->status === 'autorizada') {
            return $nota;
        }

        try {
            if (config('focusnfe.driver') === 'fake') {
                return $this->fake->emitir($nota);
            }

            return $this->focus->handle($nota);
        } catch (Throwable $e) {
            $nota->update([
                'status' => 'erro',
                'mensagem_erro' => $e->getMessage() ?: 'Falha ao emitir NF-e',
            ]);

            return $nota->fresh();
        }
    }
}
