<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsultarCnpjTest extends TestCase
{
    use RefreshDatabase;

    public function test_consulta_cnpj_devolve_uf(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Http::fake([
            'brasilapi.com.br/*' => Http::response([
                'razao_social' => 'MUNICIPIO DE GUATAPARA',
                'uf' => 'SP',
                'cep' => '14115000',
                'logradouro' => 'RUA DOS JASMINS',
                'numero' => '296',
                'bairro' => 'CENTRO',
                'municipio' => 'GUATAPARA',
            ], 200),
        ]);

        $this->getJson('/api/v1/cnpj/68319748000195')
            ->assertOk()
            ->assertJsonPath('data.uf', 'SP')
            ->assertJsonPath('data.nome', 'MUNICIPIO DE GUATAPARA')
            ->assertJsonPath('data.municipio', 'GUATAPARA');
    }

    public function test_cnpj_incompleto_retorna_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/cnpj/123')
            ->assertStatus(422);
    }
}
