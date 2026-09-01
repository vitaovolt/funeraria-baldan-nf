<?php

namespace App\Actions;

use App\Models\ConfiguracaoFiscal;
use App\Models\NotaNfce;
use App\Services\Integrations\ClienteFocusNfe;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class EmitirNfceFocus
{
    public function __construct(
        private ClienteFocusNfe $cliente,
        private MontarPayloadNfce $montarPayload,
    ) {}

    public function handle(NotaNfce $nota): NotaNfce
    {
        $nota->loadMissing('venda.itens.produto', 'venda.cliente');
        if (! $nota->venda) {
            throw new InvalidArgumentException('Venda da nota não encontrada.');
        }

        $config = ConfiguracaoFiscal::query()->first();
        if (! $config?->cnpj) {
            throw new InvalidArgumentException('Cadastre o CNPJ em Configuração fiscal.');
        }

        $ambiente = $config->ambiente_nfce === 'producao' ? 'producao' : 'homologacao';
        $serie = (int) ($config->serie_nfce ?: 1);

        return Cache::lock('nfce_numero_'.$serie, 30)->block(10, function () use ($nota, $config, $ambiente, $serie) {
            $config = ConfiguracaoFiscal::query()->lockForUpdate()->firstOrFail();
            $numero = (int) $config->proximo_numero_nfce;
            $config->proximo_numero_nfce = $numero + 1;
            $config->save();

            $payload = $this->montarPayload->handle($nota->venda, $config, $numero, $serie);
            $ref = 'venda_'.$nota->venda_id.'_nota_'.$nota->id;

            $nota->fill([
                'status' => 'enviada',
                'numero' => $numero,
                'serie' => $serie,
                'enviada_em' => now(),
            ])->save();

            $resposta = $this->cliente->enviarNfce($ref, $payload, $ambiente);
            $dados = $resposta->json() ?? [];

            if ($resposta->failed() && ! $this->processando($dados)) {
                $mensagem = $this->mensagemErro($dados, $resposta->body());
                $nota->update([
                    'status' => 'erro',
                    'mensagem_erro' => $mensagem,
                ]);

                return $nota->fresh();
            }

            if ($this->processando($dados)) {
                $consulta = $this->cliente->consultarNfce($ref, $ambiente);
                $dados = $consulta->json() ?? $dados;
            }

            return $this->persistirRetorno($nota, $dados, $ambiente);
        });
    }

    /** @param  array<string, mixed>  $dados */
    private function persistirRetorno(NotaNfce $nota, array $dados, string $ambiente): NotaNfce
    {
        $statusApi = (string) ($dados['status'] ?? '');
        $autorizada = in_array($statusApi, ['autorizado', 'autorizada'], true);

        $xmlPath = null;
        $danfePath = null;
        if ($autorizada) {
            $xml = $this->cliente->baixarArquivo((string) ($dados['caminho_xml_nota_fiscal'] ?? ''), $ambiente);
            $danfe = $this->cliente->baixarArquivo((string) ($dados['caminho_danfe'] ?? ''), $ambiente);
            if ($xml) {
                $xmlPath = "nfce/{$nota->id}.xml";
                Storage::disk('local')->put($xmlPath, $xml);
            }
            if ($danfe) {
                $danfePath = "nfce/{$nota->id}-danfe.pdf";
                Storage::disk('local')->put($danfePath, $danfe);
            }
        }

        $nota->fill([
            'status' => $autorizada ? 'autorizada' : ($this->processando($dados) ? 'pendente' : 'erro'),
            'chave' => $dados['chave_nfe'] ?? $nota->chave,
            'protocolo' => $dados['protocolo'] ?? $dados['numero_protocolo'] ?? $nota->protocolo,
            'xml_path' => $xmlPath ?? $nota->xml_path,
            'danfe_path' => $danfePath ?? $nota->danfe_path,
            'mensagem_erro' => $autorizada ? null : $this->mensagemErro($dados, null),
            'autorizada_em' => $autorizada ? now() : null,
        ])->save();

        return $nota->fresh();
    }

    /** @param  array<string, mixed>  $dados */
    private function processando(array $dados): bool
    {
        return ($dados['status'] ?? '') === 'processando_autorizacao';
    }

    /** @param  array<string, mixed>  $dados */
    private function mensagemErro(array $dados, ?string $corpo): string
    {
        $msg = $dados['mensagem_sefaz'] ?? $dados['mensagem'] ?? $dados['codigo'] ?? $corpo;

        return is_string($msg) && $msg !== '' ? $msg : 'Focus NFe recusou a NFC-e.';
    }
}
