<?php

namespace Tests\Feature;

use App\Jobs\EmitirNfceJob;
use App\Models\Cliente;
use App\Models\ConfiguracaoFiscal;
use App\Models\Consignado;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModulosF4Test extends TestCase
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
        ]);
    }

    public function test_sangria_fechar_caixa_e_fechamento(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->seedFiscal();

        $this->postJson('/api/v1/caixa/abrir', ['valor_abertura' => 100])->assertCreated();
        $this->postJson('/api/v1/caixa/sangria', ['valor' => 30, 'motivo' => 'Troco banco'])
            ->assertCreated()
            ->assertJsonPath('data.valor', '30.00');

        $this->assertDatabaseHas('sangrias_caixa', [
            'valor' => 30.00,
            'motivo' => 'Troco banco',
        ]);

        $this->postJson('/api/v1/caixa/fechar')
            ->assertOk()
            ->assertJsonPath('data.status', 'fechada')
            ->assertJsonPath('data.total_sangrias', '30.00')
            ->assertJsonPath('data.total_dinheiro_esperado', '70.00');

        $this->getJson('/api/v1/caixa/atual')->assertOk()->assertJsonPath('data', null);

        $this->getJson('/api/v1/caixa/fechamento')
            ->assertOk()
            ->assertJsonPath('data.preview', false)
            ->assertJsonPath('data.total_sangrias', 30);
    }

    public function test_consignado_criar_devolver_e_converter(): void
    {
        Storage::fake('local');
        Queue::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->seedFiscal();

        $produto = Produto::query()->create([
            'codigo_barras' => '7890000555666',
            'descricao' => 'Item consignado',
            'preco_venda' => 50,
            'custo' => 20,
            'estoque_atual' => 10,
        ]);
        $cliente = Cliente::query()->create([
            'tipo' => 'pf',
            'documento' => '99988877766',
            'nome' => 'Titular Consignado',
            'tem_plano' => false,
        ]);

        $criar = $this->postJson('/api/v1/consignados', [
            'cliente_id' => $cliente->id,
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 2]],
        ])->assertCreated();

        $consignadoId = $criar->json('data.id');
        $itemId = $criar->json('data.itens.0.id');
        $this->assertEquals(8.0, (float) $produto->fresh()->estoque_atual);
        $this->assertDatabaseHas('consignados', ['id' => $consignadoId, 'status' => 'aberto']);

        $this->postJson("/api/v1/consignados/{$consignadoId}/devolver", [
            'itens' => [['item_id' => $itemId, 'quantidade' => 1]],
        ])->assertOk()->assertJsonPath('data.status', 'parcial');

        $this->assertEquals(9.0, (float) $produto->fresh()->estoque_atual);

        $this->postJson('/api/v1/caixa/abrir')->assertCreated();

        $this->postJson("/api/v1/consignados/{$consignadoId}/converter")
            ->assertCreated()
            ->assertJsonPath('data.total', '50.00');

        $this->assertEquals(9.0, (float) $produto->fresh()->estoque_atual);
        $this->assertDatabaseHas('consignados', ['id' => $consignadoId, 'status' => 'vendido']);
        $this->assertDatabaseHas('vendas', ['cliente_id' => $cliente->id, 'total' => 50.00]);
        Queue::assertPushedOn('fiscal', EmitirNfceJob::class);
    }

    public function test_upload_certificado_a1(): void
    {
        Storage::fake('local');
        $user = User::factory()->admin()->create();
        Sanctum::actingAs($user);
        $this->seedFiscal();

        $file = UploadedFile::fake()->create('empresa.pfx', 20, 'application/x-pkcs12');

        $this->post('/api/v1/configuracao-fiscal/certificado', [
            'certificado' => $file,
            'certificado_nome' => 'Baldan A1',
            'certificado_validade' => '2027-12-31',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.tem_certificado', true)
            ->assertJsonPath('data.certificado_nome', 'Baldan A1');

        $config = ConfiguracaoFiscal::query()->first();
        $this->assertNotNull($config->certificado_path);
        Storage::disk('local')->assertExists($config->certificado_path);
    }

    public function test_modulos_exigem_auth(): void
    {
        $this->postJson('/api/v1/caixa/sangria', ['valor' => 1])->assertUnauthorized();
        $this->postJson('/api/v1/consignados', [])->assertUnauthorized();
        $this->postJson('/api/v1/configuracao-fiscal/certificado')->assertUnauthorized();
    }
}
