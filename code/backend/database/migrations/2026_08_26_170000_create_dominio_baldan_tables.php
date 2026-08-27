<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes_fiscais', function (Blueprint $table) {
            $table->id();
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('cnpj', 18)->unique();
            $table->string('inscricao_estadual', 30)->nullable();
            $table->string('regime_tributario', 40)->default('simples');
            $table->string('ambiente_nfce', 20)->default('homologacao'); // homologacao|producao
            $table->unsignedSmallInteger('serie_nfce')->default(1);
            $table->unsignedInteger('proximo_numero_nfce')->default(1);
            $table->string('certificado_path')->nullable();
            $table->string('certificado_nome')->nullable();
            $table->timestamp('certificado_validade')->nullable();
            $table->string('uf', 2)->default('SP');
            $table->string('municipio')->nullable();
            $table->string('codigo_ibge', 7)->nullable();
            $table->timestamps();
        });

        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['ativo', 'nome']);
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['ativo', 'nome']);
        });

        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marca_id')->nullable()->constrained('marcas')->nullOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->string('codigo_barras', 64)->unique();
            $table->string('descricao');
            $table->string('referencia', 64)->nullable();
            $table->string('ncm', 10)->nullable();
            $table->decimal('custo', 12, 2)->default(0);
            $table->decimal('preco_venda', 12, 2)->default(0);
            $table->decimal('estoque_atual', 12, 3)->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['ativo', 'descricao']);
            $table->index(['categoria_id', 'ativo']);
            $table->index('marca_id');
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 2)->default('pf'); // pf|pj
            $table->string('documento', 18)->unique();
            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->boolean('tem_plano')->default(false);
            $table->string('plano_nome')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['ativo', 'nome']);
            $table->index('tem_plano');
        });

        Schema::create('dependentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('nome');
            $table->string('parentesco', 40)->nullable();
            $table->string('documento', 18)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['cliente_id', 'ativo']);
        });

        Schema::create('movimentacoes_estoque', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 20); // entrada|saida|ajuste
            $table->decimal('quantidade', 12, 3);
            $table->decimal('saldo_anterior', 12, 3);
            $table->decimal('saldo_posterior', 12, 3);
            $table->string('observacao')->nullable();
            $table->timestamps();

            $table->index(['produto_id', 'created_at']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_estoque');
        Schema::dropIfExists('dependentes');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('marcas');
        Schema::dropIfExists('configuracoes_fiscais');
    }
};
