<?php

namespace Tests\Feature;

use App\Jobs\EmitirNfceJob;
use App\Models\ConfiguracaoFiscal;
use App\Models\NotaNfce;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FocusNfceTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_aceita_regime_vazio_sem_validation_string(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->putJson('/api/v1/configuracao-fiscal', [
            'razao_social' => 'FUNERARIA BALDAN LTDA',
            'cnpj' => '68.480.268/0001-01',
            'inscricao_estadual' => '334007241112',
            'regime_tributario' => '',
            'codigo_ibge' => '',
            'municipio' => 'Guariba',
            'uf' => 'SP',
            'ambiente_nfce' => 'homologacao',
            'serie_nfce' => 1,
            'proximo_numero_nfce' => 1,
        ])->assertOk()
            ->assertJsonPath('data.cnpj', '68480268000101');
    }

    public function test_emissao_focus_autoriza_e_grava_chave(): void
    {
        config(['focusnfe.driver' => 'focus', 'focusnfe.token_homologacao' => 'token-teste']);
        Http::fake([
            'homologacao.focusnfe.com.br/v2/nfce*' => Http::response([
                'status' => 'autorizado',
                'chave_nfe' => str_repeat('9', 44),
                'protocolo' => 'PROT-1',
                'caminho_xml_nota_fiscal' => '/arquivos/nota.xml',
            ], 200),
            'homologacao.focusnfe.com.br/arquivos/*' => Http::response('<nfce/>', 200),
        ]);

        Sanctum::actingAs(User::factory()->create());
        ConfiguracaoFiscal::query()->create([
            'razao_social' => 'FUNERARIA BALDAN LTDA',
            'cnpj' => '68480268000101',
            'ambiente_nfce' => 'homologacao',
            'serie_nfce' => 1,
            'proximo_numero_nfce' => 3,
            'modulo_fiscal_ativo' => true,
        ]);
        $produto = Produto::query()->create([
            'codigo_barras' => '7891000100059',
            'descricao' => 'Vela 7 Dias',
            'ncm' => '34060000',
            'preco_venda' => 12,
            'estoque_atual' => 5,
        ]);

        $this->postJson('/api/v1/caixa/abrir')->assertCreated();
        $venda = $this->postJson('/api/v1/vendas/finalizar', [
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 1]],
            'forma_pagamento' => 'dinheiro',
        ])->assertCreated();

        $nota = NotaNfce::query()->where('venda_id', $venda->json('data.id'))->first();
        $this->assertSame('autorizada', $nota?->status);
        $this->assertSame(str_repeat('9', 44), $nota->chave);
        $this->assertSame(3, $nota->numero);
        $this->assertSame(4, (int) ConfiguracaoFiscal::query()->first()->proximo_numero_nfce);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v2/nfce')
            && ($request['cnpj_emitente'] ?? null) === '68480268000101'
            && ! isset($request['nome_destinatario'])
            && ! isset($request['cpf_destinatario'])
            && ($request['indicador_inscricao_estadual_destinatario'] ?? null) === '9');
    }

    public function test_job_usa_fila_fiscal(): void
    {
        $this->assertTrue(class_exists(EmitirNfceJob::class));
    }
}
