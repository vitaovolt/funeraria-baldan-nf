<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsBootstrapTest extends TestCase
{
    public function test_preflight_options_retorna_204_com_origem_local(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/v1/health');

        $response->assertNoContent();
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }

    public function test_cors_production_sem_frontend_url_nao_libera_origem(): void
    {
        $prevEnv = getenv('APP_ENV');
        $prevFront = getenv('FRONTEND_URL');

        putenv('APP_ENV=production');
        putenv('FRONTEND_URL=');
        $_ENV['APP_ENV'] = 'production';
        $_ENV['FRONTEND_URL'] = '';
        $_SERVER['APP_ENV'] = 'production';
        $_SERVER['FRONTEND_URL'] = '';

        try {
            $config = require base_path('config/cors.php');
            $this->assertSame([], $config['allowed_origins']);
        } finally {
            if ($prevEnv === false) {
                putenv('APP_ENV');
                unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
            } else {
                putenv('APP_ENV='.$prevEnv);
                $_ENV['APP_ENV'] = $prevEnv;
                $_SERVER['APP_ENV'] = $prevEnv;
            }
            if ($prevFront === false) {
                putenv('FRONTEND_URL');
                unset($_ENV['FRONTEND_URL'], $_SERVER['FRONTEND_URL']);
            } else {
                putenv('FRONTEND_URL='.$prevFront);
                $_ENV['FRONTEND_URL'] = $prevFront;
                $_SERVER['FRONTEND_URL'] = $prevFront;
            }
        }
    }
}
