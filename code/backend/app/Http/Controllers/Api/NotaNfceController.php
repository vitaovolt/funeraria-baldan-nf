<?php

namespace App\Http\Controllers\Api;

use App\Actions\EmitirNfceSincrono;
use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoFiscal;
use App\Models\NotaNfce;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class NotaNfceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $termo = $request->query('q');

        $notas = NotaNfce::query()
            ->with(['venda.cliente', 'venda.itens.produto'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($termo, function ($q) use ($termo) {
                $q->where(function ($inner) use ($termo) {
                    if (ctype_digit((string) $termo)) {
                        $inner->where('id', (int) $termo)
                            ->orWhere('venda_id', (int) $termo);
                    }
                    $inner->orWhere('status', 'ilike', '%'.$termo.'%')
                        ->orWhere('chave', 'ilike', '%'.$termo.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->okPage($notas);
    }

    public function show(NotaNfce $nota): JsonResponse
    {
        return $this->ok($nota->load(['venda.cliente', 'venda.itens.produto']));
    }

    public function reemitir(NotaNfce $nota, EmitirNfceSincrono $emitir): JsonResponse
    {
        if (! ConfiguracaoFiscal::moduloAtivo()) {
            return $this->fail('Módulo fiscal desabilitado nas configurações.', [
                'modulo_fiscal' => ['Habilite o módulo fiscal para emitir NFC-e.'],
            ], 422);
        }

        if ($nota->status === 'autorizada') {
            return $this->ok($nota->fresh(['venda.cliente']), 'NFC-e já autorizada');
        }

        $atualizada = $emitir->handle($nota);

        return $this->ok(
            $atualizada->load(['venda.cliente', 'venda.itens.produto']),
            $atualizada->status === 'autorizada' ? 'NFC-e autorizada' : 'NFC-e não autorizada'
        );
    }

    public function danfe(NotaNfce $nota): Response
    {
        return $this->arquivo($nota->danfe_path, 'danfe-'.$nota->id);
    }

    public function xml(NotaNfce $nota): Response
    {
        return $this->arquivo($nota->xml_path, 'nfce-'.$nota->id, 'application/xml');
    }

    private function arquivo(?string $path, string $nome, ?string $mime = null): Response
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Arquivo ainda não disponível.',
                'errors' => [],
            ], 404);
        }

        $conteudo = Storage::disk('local')->get($path);
        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'bin';
        $tipo = $mime ?? match ($ext) {
            'pdf' => 'application/pdf',
            'xml' => 'application/xml',
            default => 'text/plain',
        };

        return response($conteudo, 200, [
            'Content-Type' => $tipo,
            'Content-Disposition' => 'inline; filename="'.$nome.'.'.$ext.'"',
        ]);
    }
}
