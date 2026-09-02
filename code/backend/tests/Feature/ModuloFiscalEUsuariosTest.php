<?php

namespace Tests\Feature;

use App\Models\ConfiguracaoFiscal;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModuloFiscalEUsuariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_venda_com_modulo_fiscal_desligado_nao_emite_mesmo_com_flag(): void
    {
        Sanctum::actingAs(User::factory()->create());
        ConfiguracaoFiscal::query()->create([
            'razao_social' => 'Baldan Teste',
            'cnpj' => '12345678000199',
            'serie_nfce' => 1,
            'proximo_numero_nfce' => 1,
            'ambiente_nfce' => 'homologacao',
            'modulo_fiscal_ativo' => false,
        ]);
        $produto = Produto::query()->create([
            'codigo_barras' => '7890000999000',
            'descricao' => 'Item sem NF',
            'preco_venda' => 10,
            'estoque_atual' => 5,
        ]);

        $this->postJson('/api/v1/caixa/abrir')->assertCreated();

        $venda = $this->postJson('/api/v1/vendas/finalizar', [
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 1]],
            'emitir_nfce' => true,
        ])->assertCreated();

        $this->assertNull($venda->json('data.nota_nfce'));
        $this->assertDatabaseMissing('notas_nfce', ['venda_id' => $venda->json('data.id')]);
    }

    public function test_admin_cria_usuario_por_login_sem_email(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/usuarios', [
            'name' => 'Marcia',
            'login' => 'marcia',
            'password' => 'senha123',
            'role' => 'operador',
        ])->assertCreated()
            ->assertJsonPath('data.login', 'marcia')
            ->assertJsonPath('data.email', 'marcia@baldan.local');

        $this->postJson('/api/v1/auth/login', [
            'login' => 'marcia',
            'password' => 'senha123',
        ])->assertOk()
            ->assertJsonPath('data.user.login', 'marcia');
    }

    public function test_operador_nao_gerencia_usuarios(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'operador']));

        $this->getJson('/api/v1/usuarios')->assertForbidden();
        $this->postJson('/api/v1/usuarios', [
            'name' => 'X',
            'login' => 'x',
            'password' => 'senha123',
            'role' => 'operador',
        ])->assertForbidden();
    }
}
