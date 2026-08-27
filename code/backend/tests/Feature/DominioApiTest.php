<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Marca;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DominioApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dominio_exige_auth(): void
    {
        $this->getJson('/api/v1/marcas')->assertUnauthorized();
        $this->getJson('/api/v1/categorias')->assertUnauthorized();
        $this->getJson('/api/v1/produtos')->assertUnauthorized();
        $this->getJson('/api/v1/clientes')->assertUnauthorized();
        $this->getJson('/api/v1/configuracao-fiscal')->assertUnauthorized();
    }

    public function test_crud_marca_categoria_produto_persiste(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $marca = $this->postJson('/api/v1/marcas', ['nome' => 'OrtoTest']);
        $marca->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nome', 'OrtoTest');
        $marcaId = $marca->json('data.id');
        $this->assertDatabaseHas('marcas', ['id' => $marcaId, 'nome' => 'OrtoTest']);

        $cat = $this->postJson('/api/v1/categorias', ['nome' => 'Ortopedia']);
        $cat->assertCreated();
        $catId = $cat->json('data.id');

        $produto = $this->postJson('/api/v1/produtos', [
            'marca_id' => $marcaId,
            'categoria_id' => $catId,
            'codigo_barras' => '7899999000011',
            'descricao' => 'Muleta Teste',
            'ncm' => '90211010',
            'custo' => 10.5,
            'preco_venda' => 25.9,
            'estoque_atual' => 4,
        ]);

        $produto->assertCreated()
            ->assertJsonPath('data.codigo_barras', '7899999000011')
            ->assertJsonPath('data.descricao', 'Muleta Teste');

        $produtoId = $produto->json('data.id');
        $this->assertDatabaseHas('produtos', [
            'id' => $produtoId,
            'codigo_barras' => '7899999000011',
            'custo' => 10.50,
            'preco_venda' => 25.90,
        ]);

        $this->putJson("/api/v1/produtos/{$produtoId}", [
            'preco_venda' => 29.9,
        ])->assertOk()
            ->assertJsonPath('data.preco_venda', '29.90');

        $this->assertDatabaseHas('produtos', ['id' => $produtoId, 'preco_venda' => 29.90]);

        $this->getJson('/api/v1/produtos?q=Muleta&categoria_id='.$catId)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_barcode_e_documento_duplicados_retornam_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/produtos', [
            'codigo_barras' => '7891111222233',
            'descricao' => 'A',
        ])->assertCreated();

        $this->postJson('/api/v1/produtos', [
            'codigo_barras' => '7891111222233',
            'descricao' => 'B',
        ])->assertStatus(422);

        $this->postJson('/api/v1/clientes', [
            'tipo' => 'pf',
            'documento' => '123.456.789-01',
            'nome' => 'Maria',
        ])->assertCreated()
            ->assertJsonPath('data.documento', '12345678901');

        $this->postJson('/api/v1/clientes', [
            'tipo' => 'pf',
            'documento' => '12345678901',
            'nome' => 'Outra',
        ])->assertStatus(422);
    }

    public function test_cliente_com_dependentes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $cliente = $this->postJson('/api/v1/clientes', [
            'tipo' => 'pf',
            'documento' => '11122233344',
            'nome' => 'Titular',
            'tem_plano' => true,
            'plano_nome' => 'Familiar',
        ])->assertCreated();

        $clienteId = $cliente->json('data.id');

        $dep = $this->postJson("/api/v1/clientes/{$clienteId}/dependentes", [
            'nome' => 'Filho',
            'parentesco' => 'filho',
        ])->assertCreated()
            ->assertJsonPath('data.nome', 'Filho');

        $this->assertDatabaseHas('dependentes', [
            'cliente_id' => $clienteId,
            'nome' => 'Filho',
        ]);

        $this->getJson("/api/v1/clientes/{$clienteId}")
            ->assertOk()
            ->assertJsonCount(1, 'data.dependentes');

        $depId = $dep->json('data.id');
        $this->deleteJson("/api/v1/dependentes/{$depId}")->assertOk();
        $this->assertDatabaseMissing('dependentes', ['id' => $depId]);
    }

    public function test_movimentacao_estoque_altera_saldo(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $produto = Produto::query()->create([
            'codigo_barras' => '7890000111122',
            'descricao' => 'Item Estoque',
            'estoque_atual' => 10,
        ]);

        $entrada = $this->postJson("/api/v1/produtos/{$produto->id}/movimentacoes", [
            'tipo' => 'entrada',
            'quantidade' => 5,
            'observacao' => 'compra',
        ]);

        $entrada->assertCreated()
            ->assertJsonPath('data.produto.estoque_atual', '15.000');

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'estoque_atual' => 15]);
        $this->assertDatabaseHas('movimentacoes_estoque', [
            'produto_id' => $produto->id,
            'tipo' => 'entrada',
            'user_id' => $user->id,
        ]);

        $this->postJson("/api/v1/produtos/{$produto->id}/movimentacoes", [
            'tipo' => 'saida',
            'quantidade' => 3,
        ])->assertCreated()
            ->assertJsonPath('data.produto.estoque_atual', '12.000');

        $this->postJson("/api/v1/produtos/{$produto->id}/movimentacoes", [
            'tipo' => 'saida',
            'quantidade' => 999,
        ])->assertStatus(422);

        $this->assertEquals(12.0, (float) $produto->fresh()->estoque_atual);
    }

    public function test_configuracao_fiscal_get_put(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/configuracao-fiscal')
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->putJson('/api/v1/configuracao-fiscal', [
            'razao_social' => 'Baldan Teste',
            'cnpj' => '12.345.678/0001-99',
            'ambiente_nfce' => 'homologacao',
            'serie_nfce' => 1,
        ])->assertOk()
            ->assertJsonPath('data.cnpj', '12345678000199')
            ->assertJsonPath('data.razao_social', 'Baldan Teste');

        $this->assertDatabaseHas('configuracoes_fiscais', [
            'cnpj' => '12345678000199',
            'razao_social' => 'Baldan Teste',
        ]);

        $this->putJson('/api/v1/configuracao-fiscal', [
            'municipio' => 'Campinas',
        ])->assertOk()
            ->assertJsonPath('data.municipio', 'Campinas');
    }

    public function test_seed_baldan_carrega_cadastros(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', ['email' => 'operador@baldan.local']);
        $this->assertDatabaseHas('configuracoes_fiscais', ['nome_fantasia' => 'Baldan']);
        $this->assertGreaterThanOrEqual(3, Marca::query()->count());
        $this->assertGreaterThanOrEqual(8, Produto::query()->count());
        $this->assertDatabaseHas('clientes', ['documento' => '12345678901', 'tem_plano' => true]);
        $this->assertEquals(2, Cliente::query()->where('documento', '12345678901')->first()->dependentes()->count());
    }
}
