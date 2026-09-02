<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ConfiguracaoFiscal;
use App\Models\NotaNfce;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NfeSaidaEPagamentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_venda_com_cartao_credito_e_debito(): void
    {
        Sanctum::actingAs(User::factory()->create());
        ConfiguracaoFiscal::query()->create([
            'razao_social' => 'Baldan',
            'cnpj' => '12345678000199',
            'modulo_fiscal_ativo' => false,
        ]);
        $produto = Produto::query()->create([
            'codigo_barras' => '7890000444000',
            'descricao' => 'Item cartao',
            'preco_venda' => 50,
            'estoque_atual' => 10,
        ]);
        $this->postJson('/api/v1/caixa/abrir')->assertCreated();

        $this->postJson('/api/v1/vendas/finalizar', [
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 1]],
            'forma_pagamento' => 'cartao_credito',
            'emitir_nfce' => false,
        ])->assertCreated()->assertJsonPath('data.forma_pagamento', 'cartao_credito');

        $this->postJson('/api/v1/vendas/finalizar', [
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 1]],
            'forma_pagamento' => 'cartao_debito',
            'emitir_nfce' => false,
        ])->assertCreated()->assertJsonPath('data.forma_pagamento', 'cartao_debito');

        $fechamento = $this->getJson('/api/v1/caixa/fechamento')->assertOk();
        $this->assertEquals(50.0, (float) $fechamento->json('data.totais_forma.cartao_credito'));
        $this->assertEquals(50.0, (float) $fechamento->json('data.totais_forma.cartao_debito'));
    }

    public function test_emite_nfe_de_saida_para_venda_com_cliente(): void
    {
        Sanctum::actingAs(User::factory()->create());
        ConfiguracaoFiscal::query()->create([
            'razao_social' => 'Baldan',
            'cnpj' => '12345678000199',
            'serie_nfe' => 1,
            'proximo_numero_nfe' => 5,
            'modulo_fiscal_ativo' => true,
            'ambiente_nfce' => 'homologacao',
        ]);
        $cliente = Cliente::query()->create([
            'tipo' => 'pj',
            'documento' => '11222333000181',
            'nome' => 'Hospital Teste',
            'logradouro' => 'Rua das Flores',
            'numero' => '100',
            'bairro' => 'Centro',
            'cidade' => 'Guariba',
            'uf' => 'SP',
            'cep' => '14840000',
            'ativo' => true,
        ]);
        $produto = Produto::query()->create([
            'codigo_barras' => '7890000555000',
            'descricao' => 'Urna',
            'ncm' => '44219000',
            'preco_venda' => 100,
            'estoque_atual' => 3,
        ]);
        $this->postJson('/api/v1/caixa/abrir')->assertCreated();
        $venda = $this->postJson('/api/v1/vendas/finalizar', [
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 1]],
            'cliente_id' => $cliente->id,
            'forma_pagamento' => 'pix',
            'emitir_nfce' => false,
        ])->assertCreated();

        $res = $this->postJson('/api/v1/vendas/'.$venda->json('data.id').'/emitir-nfe')
            ->assertOk()
            ->assertJsonPath('data.nota_nfe.status', 'autorizada')
            ->assertJsonPath('data.nota_nfe.tipo', 'nfe');

        $this->assertDatabaseHas('notas_nfce', [
            'venda_id' => $venda->json('data.id'),
            'tipo' => 'nfe',
            'status' => 'autorizada',
            'numero' => 5,
        ]);
        $this->assertSame(6, (int) ConfiguracaoFiscal::query()->first()->proximo_numero_nfe);
        $this->assertNotNull($res->json('data.nota_nfe.chave'));
    }
}
