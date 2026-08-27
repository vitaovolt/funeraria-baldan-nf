<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BootstrapSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabelas_base_existem(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertTrue(Schema::hasTable('job_batches'));
        $this->assertTrue(Schema::hasTable('failed_jobs'));
        $this->assertTrue(Schema::hasTable('personal_access_tokens'));
        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasTable('configuracoes_fiscais'));
        $this->assertTrue(Schema::hasTable('marcas'));
        $this->assertTrue(Schema::hasTable('categorias'));
        $this->assertTrue(Schema::hasTable('produtos'));
        $this->assertTrue(Schema::hasTable('clientes'));
        $this->assertTrue(Schema::hasTable('dependentes'));
        $this->assertTrue(Schema::hasTable('movimentacoes_estoque'));
        $this->assertTrue(Schema::hasTable('sessoes_caixa'));
        $this->assertTrue(Schema::hasTable('vendas'));
        $this->assertTrue(Schema::hasTable('itens_venda'));
        $this->assertTrue(Schema::hasTable('notas_nfce'));
    }
}
