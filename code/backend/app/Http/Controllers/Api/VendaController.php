<?php

namespace App\Http\Controllers\Api;

use App\Actions\EmitirNfceSincrono;
use App\Actions\EmitirNfeSincrono;
use App\Actions\FinalizarVenda;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmitirNfceVendaRequest;
use App\Http\Requests\EmitirNfeVendaRequest;
use App\Http\Requests\FinalizarVendaRequest;
use App\Models\ConfiguracaoFiscal;
use App\Models\NotaNfce;
use App\Models\Venda;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class VendaController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $termo = $request->query('q');

        $vendas = Venda::query()
            ->with(['cliente', 'notaNfce', 'notaNfe'])
            ->when($termo, function ($q) use ($termo) {
                $q->where(function ($inner) use ($termo) {
                    if (ctype_digit((string) $termo)) {
                        $inner->where('id', (int) $termo);
                    }
                    $inner->orWhereHas('cliente', function ($cli) use ($termo) {
                        $cli->where('nome', 'ilike', '%'.$termo.'%')
                            ->orWhere('documento', 'ilike', '%'.$termo.'%');
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->okPage($vendas);
    }

    public function finalizar(FinalizarVendaRequest $request, FinalizarVenda $action): JsonResponse
    {
        $dados = $request->validated();
        $idemKey = $request->header('Idempotency-Key');
        if (is_string($idemKey) && trim($idemKey) !== '') {
            $dados['idempotency_key'] = trim($idemKey);
        }

        $jaExistia = ! empty($dados['idempotency_key'])
            && Venda::query()->where('idempotency_key', $dados['idempotency_key'])->exists();

        try {
            $venda = $action->handle($request->user(), $dados);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['venda' => [$e->getMessage()]], 422);
        }

        $venda->load(['itens.produto', 'cliente', 'notaNfce', 'notaNfe']);

        return $this->ok($venda, 'Venda finalizada', $jaExistia ? 200 : 201);
    }

    public function show(Venda $venda): JsonResponse
    {
        return $this->ok($venda->load(['itens.produto', 'cliente', 'notaNfce', 'notaNfe', 'usuario:id,name']));
    }

    public function emitirNfce(EmitirNfceVendaRequest $request, Venda $venda, EmitirNfceSincrono $emitir): JsonResponse
    {
        if (! ConfiguracaoFiscal::moduloAtivo()) {
            return $this->fail('Módulo fiscal desabilitado nas configurações.', [
                'modulo_fiscal' => ['Habilite o módulo fiscal para emitir NFC-e.'],
            ], 422);
        }

        $venda->load('notaNfce');
        if ($venda->notaNfce?->status === 'autorizada') {
            return $this->ok($venda->fresh(['itens.produto', 'cliente', 'notaNfce', 'notaNfe']), 'NFC-e já autorizada');
        }

        $doc = $request->validated('documento_nfce') ?? '';
        if ($doc !== '') {
            $venda->documento_destinatario_nfce = $doc;
            $venda->save();
        }

        $nota = $venda->notaNfce ?: NotaNfce::query()->create([
            'venda_id' => $venda->id,
            'tipo' => 'nfce',
            'status' => 'pendente',
        ]);

        $nota = $emitir->handle($nota);

        return $this->ok(
            $venda->fresh(['itens.produto', 'cliente', 'notaNfce', 'notaNfe']),
            $nota->status === 'autorizada' ? 'NFC-e autorizada' : 'NFC-e não autorizada'
        );
    }

    public function emitirNfe(EmitirNfeVendaRequest $request, Venda $venda, EmitirNfeSincrono $emitir): JsonResponse
    {
        if (! ConfiguracaoFiscal::moduloAtivo()) {
            return $this->fail('Módulo fiscal desabilitado nas configurações.', [
                'modulo_fiscal' => ['Habilite o módulo fiscal para emitir NF-e.'],
            ], 422);
        }

        $venda->load(['cliente', 'notaNfe', 'itens']);
        if ($venda->itens->isEmpty()) {
            return $this->fail('Venda sem itens.', ['venda' => ['Sem itens.']]);
        }
        if (! $venda->cliente_id) {
            return $this->fail('Vincule um cliente à venda antes de emitir a NF-e de saída.', [
                'cliente' => ['Obrigatório.'],
            ]);
        }

        if ($venda->notaNfe?->status === 'autorizada') {
            return $this->ok($venda->fresh(['itens.produto', 'cliente', 'notaNfce', 'notaNfe']), 'NF-e já autorizada');
        }

        $doc = $request->validated('documento_destinatario') ?? '';
        if ($doc !== '') {
            $venda->documento_destinatario_nfce = $doc;
            $venda->save();
        }

        $nota = $venda->notaNfe;
        if ($nota && in_array($nota->status, ['autorizada'], true)) {
            return $this->ok($venda->fresh(['itens.produto', 'cliente', 'notaNfce', 'notaNfe']), 'NF-e já autorizada');
        }

        if (! $nota) {
            $nota = NotaNfce::query()->create([
                'venda_id' => $venda->id,
                'tipo' => 'nfe',
                'status' => 'pendente',
            ]);
        }

        $nota = $emitir->handle($nota);

        return $this->ok(
            $venda->fresh(['itens.produto', 'cliente', 'notaNfce', 'notaNfe']),
            $nota->status === 'autorizada' ? 'NF-e autorizada' : 'NF-e não autorizada'
        );
    }
}
