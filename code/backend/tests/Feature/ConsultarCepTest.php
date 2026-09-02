<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsultarCepTest extends TestCase
{
    use RefreshDatabase;

    public function test_consulta_cep_preenche_endereco(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'cep' => '14840-000',
                'logradouro' => 'Rua Teste',
                'complemento' => '',
                'bairro' => 'Centro',
                'localidade' => 'Guariba',
                'uf' => 'SP',
                'ibge' => '3517306',
            ], 200),
        ]);

        $this->getJson('/api/v1/cep/14840000')
            ->assertOk()
            ->assertJsonPath('data.cidade', 'Guariba')
            ->assertJsonPath('data.uf', 'SP')
            ->assertJsonPath('data.codigo_ibge', '3517306')
            ->assertJsonPath('data.logradouro', 'Rua Teste');
    }

    public function test_cep_invalido_retorna_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/cep/123')
            ->assertStatus(422);
    }
}
