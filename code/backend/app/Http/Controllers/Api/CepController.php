<?php

namespace App\Http\Controllers\Api;

use App\Actions\ConsultarCep;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Throwable;

class CepController extends Controller
{
    use ApiResponse;

    public function show(string $cep, ConsultarCep $action): JsonResponse
    {
        try {
            return $this->ok($action->handle($cep));
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['cep' => [$e->getMessage()]], 422);
        } catch (Throwable $e) {
            return $this->fail('Não foi possível consultar o CEP agora. Tente de novo.', [
                'cep' => ['Falha na consulta ViaCEP.'],
            ], 502);
        }
    }
}
