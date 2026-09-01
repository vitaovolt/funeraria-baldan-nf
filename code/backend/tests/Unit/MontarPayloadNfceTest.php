<?php

namespace Tests\Unit;

use App\Actions\MontarPayloadNfce;
use App\Models\Cliente;
use App\Models\ConfiguracaoFiscal;
use App\Models\ItemVenda;
use App\Models\Produto;
use App\Models\SessaoCaixa;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MontarPayloadNfceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payload_manda_so_o_que_a_focus_usa_na_nfce(): void
    {
        $user = User::factory()->create();
        $produto = Produto::query()->create([
            'codigo_barras' => '7891000100059',
            'descricao' => 'Vela 7 Dias',
            'ncm' => '34060000',
            'preco_venda' => 12,
        ]);
        $cliente = Cliente::query()->create([
            'tipo' => 'pf',
            'documento' => '12345678901',
            'nome' => 'Maria',
        ]);
        $caixa = SessaoCaixa::query()->create([
            'user_id' => $user->id,
            'aberto_em' => now(),
            'status' => 'aberta',
        ]);
        $venda = Venda::query()->create([
            'sessao_caixa_id' => $caixa->id,
            'user_id' => $user->id,
            'cliente_id' => $cliente->id,
            'status' => 'finalizada',
            'subtotal' => 24,
            'desconto_tipo' => 'valor',
            'desconto_valor' => 4,
            'total' => 20,
            'forma_pagamento' => 'pix',
        ]);
        ItemVenda::query()->create([
            'venda_id' => $venda->id,
            'produto_id' => $produto->id,
            'quantidade' => 2,
            'preco_unitario' => 12,
            'custo_unitario' => 4,
            'total_linha' => 24,
        ]);

        $config = ConfiguracaoFiscal::query()->create([
            'razao_social' => 'FUNERARIA BALDAN LTDA',
            'cnpj' => '68480268000101',
            'ambiente_nfce' => 'homologacao',
        ]);

        $payload = app(MontarPayloadNfce::class)->handle($venda->fresh(['itens.produto', 'cliente']), $config, 7, 1);

        $this->assertSame('68480268000101', $payload['cnpj_emitente']);
        $this->assertSame(1, $payload['serie']);
        $this->assertSame(7, $payload['numero']);
        $this->assertArrayNotHasKey('csc', $payload);
        $this->assertArrayNotHasKey('id_token', $payload);
        $this->assertArrayNotHasKey('inscricao_estadual', $payload);
        $this->assertSame('NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL', $payload['nome_destinatario']);
        $this->assertSame('12345678901', $payload['cpf_destinatario']);
        $this->assertSame('17', $payload['formas_pagamento'][0]['forma_pagamento']);
        $this->assertSame('20.00', $payload['formas_pagamento'][0]['valor_pagamento']);
        $this->assertSame('2', $payload['itens'][0]['quantidade_comercial']);
        $this->assertSame('34060000', $payload['itens'][0]['codigo_ncm']);
        $this->assertSame('4.00', $payload['itens'][0]['valor_desconto']);
        $this->assertSame('9', $payload['indicador_inscricao_estadual_destinatario']);
        $this->assertSame('NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL', $payload['itens'][0]['descricao']);
    }

    public function test_sem_cliente_nao_inventa_cpf_nem_nome(): void
    {
        $user = User::factory()->create();
        $produto = Produto::query()->create([
            'codigo_barras' => '7891000100059',
            'descricao' => 'Vela 7 Dias',
            'ncm' => '34060000',
            'preco_venda' => 12,
        ]);
        $caixa = SessaoCaixa::query()->create([
            'user_id' => $user->id,
            'aberto_em' => now(),
            'status' => 'aberta',
        ]);
        $venda = Venda::query()->create([
            'sessao_caixa_id' => $caixa->id,
            'user_id' => $user->id,
            'status' => 'finalizada',
            'subtotal' => 12,
            'desconto_tipo' => 'nenhum',
            'desconto_valor' => 0,
            'total' => 12,
            'forma_pagamento' => 'dinheiro',
        ]);
        ItemVenda::query()->create([
            'venda_id' => $venda->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco_unitario' => 12,
            'custo_unitario' => 4,
            'total_linha' => 12,
        ]);

        $config = ConfiguracaoFiscal::query()->create([
            'razao_social' => 'FUNERARIA BALDAN LTDA',
            'cnpj' => '68480268000101',
            'ambiente_nfce' => 'homologacao',
        ]);

        $payload = app(MontarPayloadNfce::class)->handle($venda->fresh(['itens.produto', 'cliente']), $config, 1, 1);

        $this->assertArrayNotHasKey('nome_destinatario', $payload);
        $this->assertArrayNotHasKey('cpf_destinatario', $payload);
        $this->assertArrayNotHasKey('cnpj_destinatario', $payload);
        $this->assertSame('9', $payload['indicador_inscricao_estadual_destinatario']);
        $this->assertSame('NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL', $payload['itens'][0]['descricao']);

        $config->ambiente_nfce = 'producao';
        $config->save();
        $prod = app(MontarPayloadNfce::class)->handle($venda->fresh(['itens.produto', 'cliente']), $config->fresh(), 1, 1);
        $this->assertArrayNotHasKey('nome_destinatario', $prod);
        $this->assertArrayNotHasKey('cpf_destinatario', $prod);
        $this->assertSame('Vela 7 Dias', $prod['itens'][0]['descricao']);
    }
}
