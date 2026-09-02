<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaixaController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\CepController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ConfiguracaoFiscalController;
use App\Http\Controllers\Api\ConsignadoController;
use App\Http\Controllers\Api\DependenteController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ImpressaoController;
use App\Http\Controllers\Api\MarcaController;
use App\Http\Controllers\Api\MovimentacaoEstoqueController;
use App\Http\Controllers\Api\NotaNfceController;
use App\Http\Controllers\Api\ProdutoController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VendaController;
use App\Http\Middleware\EnsureUserAtivo;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('/health', HealthController::class);
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', EnsureUserAtivo::class])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);

        Route::get('/cep/{cep}', [CepController::class, 'show'])->where('cep', '[0-9.\-]{1,10}');

        Route::get('/configuracao-fiscal', [ConfiguracaoFiscalController::class, 'show']);
        Route::put('/configuracao-fiscal', [ConfiguracaoFiscalController::class, 'update']);
        Route::post('/configuracao-fiscal/certificado', [ConfiguracaoFiscalController::class, 'uploadCertificado']);

        Route::apiResource('usuarios', UserController::class);

        Route::apiResource('marcas', MarcaController::class);
        Route::apiResource('categorias', CategoriaController::class);
        Route::apiResource('produtos', ProdutoController::class);
        Route::apiResource('clientes', ClienteController::class);

        Route::get('/clientes/{cliente}/dependentes', [DependenteController::class, 'index']);
        Route::post('/clientes/{cliente}/dependentes', [DependenteController::class, 'store']);
        Route::get('/dependentes/{dependente}', [DependenteController::class, 'show']);
        Route::put('/dependentes/{dependente}', [DependenteController::class, 'update']);
        Route::delete('/dependentes/{dependente}', [DependenteController::class, 'destroy']);

        Route::get('/produtos/{produto}/movimentacoes', [MovimentacaoEstoqueController::class, 'index']);
        Route::post('/produtos/{produto}/movimentacoes', [MovimentacaoEstoqueController::class, 'store']);

        Route::middleware('throttle:mutations')->group(function () {
            Route::post('/caixa/abrir', [CaixaController::class, 'abrir']);
            Route::post('/caixa/sangria', [CaixaController::class, 'sangria']);
            Route::post('/caixa/suprimento', [CaixaController::class, 'suprimento']);
            Route::post('/caixa/fechar', [CaixaController::class, 'fechar']);
            Route::post('/vendas/finalizar', [VendaController::class, 'finalizar']);
            Route::post('/vendas/{venda}/emitir-nfce', [VendaController::class, 'emitirNfce']);
            Route::post('/vendas/{venda}/emitir-nfe', [VendaController::class, 'emitirNfe']);
            Route::post('/notas-nfce/{nota}/reemitir', [NotaNfceController::class, 'reemitir']);
            Route::post('/consignados', [ConsignadoController::class, 'store']);
            Route::post('/consignados/{consignado}/devolver', [ConsignadoController::class, 'devolver']);
            Route::post('/consignados/{consignado}/converter', [ConsignadoController::class, 'converter']);
        });

        Route::get('/caixa/atual', [CaixaController::class, 'atual']);
        Route::get('/caixa/fechamento', [CaixaController::class, 'fechamento']);
        Route::get('/caixa/vendas-do-dia', [CaixaController::class, 'vendasDoDia']);

        Route::get('/vendas', [VendaController::class, 'index']);
        Route::get('/vendas/{venda}', [VendaController::class, 'show']);
        Route::get('/vendas/{venda}/comprovante', [ImpressaoController::class, 'comprovanteVenda']);

        Route::get('/notas-nfce', [NotaNfceController::class, 'index']);
        Route::get('/notas-nfce/{nota}/danfe', [NotaNfceController::class, 'danfe']);
        Route::get('/notas-nfce/{nota}/xml', [NotaNfceController::class, 'xml']);
        Route::get('/notas-nfce/{nota}', [NotaNfceController::class, 'show']);

        Route::get('/consignados', [ConsignadoController::class, 'index']);
        Route::get('/consignados/{consignado}/notinha', [ImpressaoController::class, 'notinhaConsignado']);
        Route::get('/consignados/{consignado}', [ConsignadoController::class, 'show']);
        Route::get('/caixa/fechamento/impressao', [ImpressaoController::class, 'fechamentoCaixa']);
    });
});
