<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ConfiguracaoFiscal;
use App\Models\NotaNfce;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PdvSliceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fluxo_caixa_venda_baixa_estoque_e_autoriza_nfce(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        ConfiguracaoFiscal::query()->create([
            'razao_social' => 'Baldan Teste',
            'cnpj' => '12345678000199',
            'serie_nfce' => 1,
            'proximo_numero_nfce' => 10,
            'ambiente_nfce' => 'homologacao',
        ]);

        $produto = Produto::query()->create([
            'codigo_barras' => '7890000999888',
            'descricao' => 'Muleta E2E',
            'preco_venda' => 100,
            'custo' => 40,
            'estoque_atual' => 5,
        ]);

        $cliente = Cliente::query()->create([
            'tipo' => 'pf',
            'documento' => '11122233344',
            'nome' => 'Cliente PDV',
            'tem_plano' => false,
        ]);

        $this->getJson('/api/v1/caixa/atual')->assertOk()->assertJsonPath('data', null);

        $this->postJson('/api/v1/caixa/abrir', ['valor_abertura' => 50])
            ->assertCreated()
            ->assertJsonPath('data.status', 'aberta');

        $this->postJson('/api/v1/caixa/abrir')->assertStatus(422);

        $venda = $this->postJson('/api/v1/vendas/finalizar', [
            'itens' => [
                ['produto_id' => $produto->id, 'quantidade' => 2],
            ],
            'cliente_id' => $cliente->id,
            'desconto_tipo' => 'valor',
            'desconto_valor' => 10,
            'forma_pagamento' => 'dinheiro',
        ]);

        $venda->assertCreated()
            ->assertJsonPath('data.total', '190.00')
            ->assertJsonPath('data.nota_nfce.status', 'autorizada');

        $vendaId = $venda->json('data.id');
        $this->assertDatabaseHas('vendas', [
            'id' => $vendaId,
            'total' => 190.00,
            'cliente_id' => $cliente->id,
        ]);
        $this->assertEquals(3.0, (float) $produto->fresh()->estoque_atual);

        $nota = NotaNfce::query()->where('venda_id', $vendaId)->first();
        $this->assertNotNull($nota);
        $this->assertSame('autorizada', $nota->status);
        $this->assertSame(10, $nota->numero);
        Storage::disk('local')->assertExists($nota->xml_path);
        Storage::disk('local')->assertExists($nota->danfe_path);

        $this->getJson('/api/v1/caixa/vendas-do-dia')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/notas-nfce?status=autorizada')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_finalizar_sem_caixa_e_estoque_insuficiente(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $produto = Produto::query()->create([
            'codigo_barras' => '7890000111000',
            'descricao' => 'Item',
            'preco_venda' => 10,
            'estoque_atual' => 1,
        ]);

        $this->postJson('/api/v1/vendas/finalizar', [
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 1]],
        ])->assertStatus(422);

        $this->postJson('/api/v1/caixa/abrir')->assertCreated();

        $this->postJson('/api/v1/vendas/finalizar', [
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 5]],
        ])->assertStatus(422);

        $this->assertEquals(1.0, (float) $produto->fresh()->estoque_atual);
    }

    public function test_venda_sem_nfce_gera_so_comprovante(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $produto = Produto::query()->create([
            'codigo_barras' => '7890000222000',
            'descricao' => 'Item comprovante',
            'preco_venda' => 20,
            'estoque_atual' => 3,
        ]);

        $this->postJson('/api/v1/caixa/abrir')->assertCreated();

        $venda = $this->postJson('/api/v1/vendas/finalizar', [
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 1]],
            'emitir_nfce' => false,
            'documento_nfce' => '390.533.447-05',
            'valor_recebido' => 50,
        ])->assertCreated();

        $this->assertNull($venda->json('data.nota_nfce'));
        $this->assertDatabaseMissing('notas_nfce', ['venda_id' => $venda->json('data.id')]);
        $this->assertDatabaseHas('vendas', [
            'id' => $venda->json('data.id'),
            'documento_destinatario_nfce' => '39053344705',
        ]);

        $this->get("/api/v1/vendas/{$venda->json('data.id')}/comprovante")
            ->assertOk()
            ->assertSee('COMPROVANTE DA VENDA', false)
            ->assertSee('Sem valor fiscal', false);
    }

    public function test_emite_nfce_depois_do_comprovante(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        ConfiguracaoFiscal::query()->create([
            'razao_social' => 'Baldan Teste',
            'cnpj' => '12345678000199',
            'serie_nfce' => 1,
            'proximo_numero_nfce' => 1,
            'ambiente_nfce' => 'homologacao',
        ]);
        $produto = Produto::query()->create([
            'codigo_barras' => '7890000333000',
            'descricao' => 'Item tarde',
            'preco_venda' => 15,
            'estoque_atual' => 2,
        ]);
        $this->postJson('/api/v1/caixa/abrir')->assertCreated();
        $venda = $this->postJson('/api/v1/vendas/finalizar', [
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 1]],
            'emitir_nfce' => false,
        ])->assertCreated();

        $this->postJson('/api/v1/vendas/'.$venda->json('data.id').'/emitir-nfce', [
            'documento_nfce' => '39053344705',
        ])->assertOk()->assertJsonPath('data.nota_nfce.status', 'autorizada');
    }

    public function test_pdv_exige_auth(): void
    {
        $this->postJson('/api/v1/caixa/abrir')->assertUnauthorized();
        $this->postJson('/api/v1/vendas/finalizar', ['itens' => []])->assertUnauthorized();
        $this->getJson('/api/v1/notas-nfce')->assertUnauthorized();
    }
}
