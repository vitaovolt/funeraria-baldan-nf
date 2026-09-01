<?php

namespace App\Services\Fiscal;

use App\Models\ConfiguracaoFiscal;
use App\Models\NotaNfce;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Emissor fake para F3 (homologação local). Produção real entra na F4/F6.
 */
class EmissorNfceFake
{
    public function emitir(NotaNfce $nota): NotaNfce
    {
        return DB::transaction(function () use ($nota) {
            $config = ConfiguracaoFiscal::query()->lockForUpdate()->first();
            $serie = (int) ($config?->serie_nfce ?: 1);
            $numero = (int) $nota->numero ?: 1;
            if ($config && ! $nota->numero) {
                $numero = (int) $config->proximo_numero_nfce;
                $config->proximo_numero_nfce = $numero + 1;
                $config->save();
            }

            $chave = str_pad((string) random_int(0, PHP_INT_MAX), 44, '0', STR_PAD_LEFT);
            $chave = substr($chave, 0, 44);
            $protocolo = 'FAKE-'.Str::upper(Str::random(10));

            $xml = '<?xml version="1.0"?><nfceFake chave="'.$chave.'" numero="'.$numero.'" serie="'.$serie.'"/>';
            $xmlPath = "nfce/{$nota->id}.xml";
            $danfePath = "nfce/{$nota->id}-danfe.txt";
            Storage::disk('local')->put($xmlPath, $xml);
            Storage::disk('local')->put($danfePath, "DANFE NFC-e fake {$chave}\nTotal venda #{$nota->venda_id}");

            $nota->fill([
                'status' => 'autorizada',
                'chave' => $chave,
                'numero' => $numero,
                'serie' => $serie,
                'protocolo' => $protocolo,
                'xml_path' => $xmlPath,
                'danfe_path' => $danfePath,
                'mensagem_erro' => null,
                'enviada_em' => now(),
                'autorizada_em' => now(),
            ]);
            $nota->save();

            return $nota->fresh();
        });
    }
}
