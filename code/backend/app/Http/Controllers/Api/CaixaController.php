<?php

namespace App\Http\Controllers\Api;

use App\Actions\AbrirSessaoCaixa;
use App\Actions\FecharSessaoCaixa;
use App\Actions\RegistrarSangria;
use App\Actions\RegistrarSuprimento;
use App\Http\Controllers\Controller;
use App\Http\Requests\AbrirCaixaRequest;
use App\Http\Requests\RegistrarSangriaRequest;
use App\Models\SessaoCaixa;
use App\Support\ApiResponse;
use App\Support\ResumoCaixa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CaixaController extends Controller
{
    use ApiResponse;

    public function atual(): JsonResponse
    {
        $caixa = SessaoCaixa::query()
            ->abertas()
            ->with(['usuario:id,name,email', 'sangrias', 'suprimentos'])
            ->first();

        return $this->ok($caixa);
    }

    public function abrir(AbrirCaixaRequest $request, AbrirSessaoCaixa $action): JsonResponse
    {
        try {
            $caixa = $action->handle(
                $request->user(),
                (float) ($request->validated('valor_abertura') ?? 0)
            );
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['caixa' => [$e->getMessage()]], 422);
        }

        return $this->ok($caixa->load('usuario:id,name,email'), 'Caixa aberto', 201);
    }

    public function sangria(RegistrarSangriaRequest $request, RegistrarSangria $action): JsonResponse
    {
        try {
            $sangria = $action->handle(
                $request->user(),
                (float) $request->validated('valor'),
                $request->validated('motivo')
            );
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['sangria' => [$e->getMessage()]], 422);
        }

        return $this->ok($sangria->load('usuario:id,name,email'), 'Sangria registrada', 201);
    }

    public function suprimento(RegistrarSangriaRequest $request, RegistrarSuprimento $action): JsonResponse
    {
        try {
            $movimento = $action->handle(
                $request->user(),
                (float) $request->validated('valor'),
                $request->validated('motivo')
            );
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['suprimento' => [$e->getMessage()]], 422);
        }

        return $this->ok($movimento->load('usuario:id,name,email'), 'Suprimento registrado', 201);
    }

    public function fechar(FecharSessaoCaixa $action): JsonResponse
    {
        try {
            $caixa = $action->handle(request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['caixa' => [$e->getMessage()]], 422);
        }

        return $this->ok($caixa, 'Caixa fechado');
    }

    public function fechamento(): JsonResponse
    {
        $aberta = SessaoCaixa::query()->abertas()->first();
        if ($aberta) {
            return $this->ok(ResumoCaixa::montar($aberta, true));
        }

        $caixa = SessaoCaixa::query()
            ->where('status', 'fechada')
            ->orderByDesc('fechado_em')
            ->first();

        if (! $caixa) {
            return $this->ok(null);
        }

        return $this->ok(ResumoCaixa::montar($caixa, false));
    }

    public function vendasDoDia(Request $request): JsonResponse
    {
        $caixa = SessaoCaixa::query()->abertas()->first();
        if (! $caixa) {
            return $this->ok([], 'Operação realizada com sucesso', 200, [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $this->perPage($request),
                'total' => 0,
            ]);
        }

        $termo = $request->query('q');

        $vendas = $caixa->vendas()
            ->with(['cliente', 'itens.produto', 'notaNfce'])
            ->when($termo, function ($q) use ($termo) {
                $q->where(function ($inner) use ($termo) {
                    if (ctype_digit((string) $termo)) {
                        $inner->where('id', (int) $termo);
                    }
                    $inner->orWhereHas('cliente', function ($c) use ($termo) {
                        $c->where('nome', 'ilike', '%'.$termo.'%');
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->okPage($vendas);
    }
}
