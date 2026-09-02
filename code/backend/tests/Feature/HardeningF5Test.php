<?php

namespace Tests\Feature;

use App\Models\ConfiguracaoFiscal;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HardeningF5Test extends TestCase
{
    use RefreshDatabase;

    private function seedFiscal(): void
    {
        ConfiguracaoFiscal::query()->create([
            'razao_social' => 'Baldan Teste',
            'cnpj' => '12345678000199',
            'serie_nfce' => 1,
            'proximo_numero_nfce' => 1,
            'ambiente_nfce' => 'homologacao',
            'modulo_fiscal_ativo' => true,
        ]);
    }

    public function test_login_conta_inativa_retorna_422(): void
    {
        $user = User::factory()->inativo()->create([
            'email' => 'inativo@baldan.local',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['login']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_usuario_inativo_com_token_recebe_403(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        Sanctum::actingAs($user);
        $user->update(['ativo' => false]);

        $this->getJson('/api/v1/produtos')
            ->assertForbidden()
            ->assertJsonPath('message', 'Conta inativa.');
    }

    public function test_operador_nao_altera_config_fiscal_nem_certificado(): void
    {
        Storage::fake('local');
        $this->seedFiscal();
        Sanctum::actingAs(User::factory()->create(['role' => 'operador']));

        $this->getJson('/api/v1/configuracao-fiscal')->assertOk();

        $this->putJson('/api/v1/configuracao-fiscal', [
            'razao_social' => 'Hack',
        ])->assertForbidden();

        $this->post('/api/v1/configuracao-fiscal/certificado', [
            'certificado' => UploadedFile::fake()->create('x.pfx', 10),
        ], ['Accept' => 'application/json'])->assertForbidden();

        $this->assertDatabaseMissing('configuracoes_fiscais', ['razao_social' => 'Hack']);
    }

    public function test_admin_altera_config_fiscal(): void
    {
        $this->seedFiscal();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->putJson('/api/v1/configuracao-fiscal', [
            'municipio' => 'Campinas',
        ])->assertOk()
            ->assertJsonPath('data.municipio', 'Campinas');

        $this->assertDatabaseHas('configuracoes_fiscais', ['municipio' => 'Campinas']);
    }

    public function test_venda_com_idempotency_key_nao_duplica(): void
    {
        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());
        $this->seedFiscal();

        $produto = Produto::query()->create([
            'codigo_barras' => '7890000111222',
            'descricao' => 'Item idem',
            'preco_venda' => 50,
            'estoque_atual' => 10,
        ]);

        $this->postJson('/api/v1/caixa/abrir')->assertCreated();

        $payload = [
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 1]],
            'forma_pagamento' => 'dinheiro',
        ];
        $headers = ['Idempotency-Key' => 'venda-e2e-key-001'];

        $first = $this->postJson('/api/v1/vendas/finalizar', $payload, $headers)
            ->assertCreated();
        $vendaId = $first->json('data.id');

        $second = $this->postJson('/api/v1/vendas/finalizar', $payload, $headers)
            ->assertOk()
            ->assertJsonPath('data.id', $vendaId);

        $this->assertEquals(1, Venda::query()->count());
        $this->assertEquals(9.0, (float) $produto->fresh()->estoque_atual);
        $this->assertDatabaseHas('vendas', [
            'id' => $vendaId,
            'idempotency_key' => 'venda-e2e-key-001',
        ]);
        $this->assertSame($vendaId, $second->json('data.id'));
    }

    public function test_mutacoes_criticas_exigem_auth(): void
    {
        $this->postJson('/api/v1/vendas/finalizar', ['itens' => []])->assertUnauthorized();
        $this->postJson('/api/v1/caixa/abrir')->assertUnauthorized();
        $this->putJson('/api/v1/configuracao-fiscal', [])->assertUnauthorized();
    }

    public function test_health_ainda_tem_security_headers(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    }
}
