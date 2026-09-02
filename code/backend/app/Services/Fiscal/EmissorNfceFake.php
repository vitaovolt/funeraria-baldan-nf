<?php

namespace App\Services\Fiscal;

use App\Models\ConfiguracaoFiscal;
use App\Models\NotaNfce;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Emissor fake para testes locais (NFC-e e NF-e).
 */
class EmissorNfceFake
{
    public function emitir(NotaNfce $nota): NotaNfce
    {
        return DB::transaction(function () use ($nota) {
            $ehNfe = $nota->tipo === 'nfe';
            $config = ConfiguracaoFiscal::query()->lockForUpdate()->first();
            $serie = $ehNfe
                ? (int) ($config?->serie_nfe ?: 1)
                : (int) ($config?->serie_nfce ?: 1);
            $numero = (int) $nota->numero ?: 1;
            if ($config && ! $nota->numero) {
                if ($ehNfe) {
                    $numero = (int) $config->proximo_numero_nfe;
                    $config->proximo_numero_nfe = $numero + 1;
                } else {
                    $numero = (int) $config->proximo_numero_nfce;
                    $config->proximo_numero_nfce = $numero + 1;
                }
                $config->save();
            }

            $chave = str_pad((string) random_int(0, PHP_INT_MAX), 44, '0', STR_PAD_LEFT);
            $chave = substr($chave, 0, 44);
            $protocolo = 'FAKE-'.Str::upper(Str::random(10));
            $pasta = $ehNfe ? 'nfe' : 'nfce';
            $rotulo = $ehNfe ? 'NF-e' : 'NFC-e';

            $xml = '<?xml version="1.0"?><notaFake tipo="'.$rotulo.'" chave="'.$chave.'" numero="'.$numero.'" serie="'.$serie.'"/>';
            $xmlPath = "{$pasta}/{$nota->id}.xml";
            $danfePath = "{$pasta}/{$nota->id}-danfe.txt";
            Storage::disk('local')->put($xmlPath, $xml);
            Storage::disk('local')->put($danfePath, "DANFE {$rotulo} fake {$chave}\nTotal venda #{$nota->venda_id}");

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
