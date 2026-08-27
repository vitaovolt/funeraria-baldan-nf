<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateConfiguracaoFiscalRequest;
use App\Http\Requests\UploadCertificadoRequest;
use App\Models\ConfiguracaoFiscal;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ConfiguracaoFiscalController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        $this->authorize('viewAny', ConfiguracaoFiscal::class);

        $config = ConfiguracaoFiscal::query()->first();

        return $this->ok($config);
    }

    public function update(UpdateConfiguracaoFiscalRequest $request): JsonResponse
    {
        $this->authorize('update', ConfiguracaoFiscal::class);

        $config = ConfiguracaoFiscal::query()->first();

        if (! $config) {
            $dados = $request->validated();
            if (! isset($dados['razao_social'], $dados['cnpj'])) {
                return $this->fail('Configuração fiscal ainda não existe. Informe razao_social e cnpj.', [
                    'razao_social' => ['Obrigatório na primeira gravação.'],
                    'cnpj' => ['Obrigatório na primeira gravação.'],
                ]);
            }
            $config = ConfiguracaoFiscal::query()->create($dados);
        } else {
            $config->update($request->validated());
            $config = $config->fresh();
        }

        return $this->ok($config, 'Configuração fiscal atualizada');
    }

    public function uploadCertificado(UploadCertificadoRequest $request): JsonResponse
    {
        $this->authorize('uploadCertificado', ConfiguracaoFiscal::class);

        $config = ConfiguracaoFiscal::query()->first();
        if (! $config) {
            return $this->fail('Crie a configuração fiscal antes de enviar o certificado A1.', [
                'configuracao' => ['Obrigatória.'],
            ], 422);
        }

        $arquivo = $request->file('certificado');
        $path = $arquivo->storeAs(
            'certificados',
            'a1-'.now()->format('YmdHis').'.'.$arquivo->getClientOriginalExtension(),
            'local'
        );

        if ($config->certificado_path) {
            Storage::disk('local')->delete($config->certificado_path);
        }

        $config->update([
            'certificado_path' => $path,
            'certificado_nome' => $request->validated('certificado_nome')
                ?? $arquivo->getClientOriginalName(),
            'certificado_validade' => $request->validated('certificado_validade'),
        ]);

        return $this->ok($config->fresh(), 'Certificado A1 armazenado');
    }
}
