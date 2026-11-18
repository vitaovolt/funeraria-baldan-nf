<?php

namespace App\Http\Controllers\Api;

use App\Actions\ConsultarCnpj;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Throwable;

class CnpjController extends Controller
{
    use ApiResponse;

    public function show(string $cnpj, ConsultarCnpj $action): JsonResponse
    {
        try {
            return $this->ok($action->handle($cnpj));
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), ['cnpj' => [$e->getMessage()]], 422);
        } catch (Throwable $e) {
            return $this->fail('Não foi possível consultar o CNPJ agora. Tente de novo.', [
                'cnpj' => ['Falha na consulta do CNPJ.'],
            ], 502);
        }
    }
}
