<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessoes_caixa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('aberto_em');
            $table->timestamp('fechado_em')->nullable();
            $table->decimal('valor_abertura', 12, 2)->default(0);
            $table->string('status', 20)->default('aberta'); // aberta|fechada
            $table->timestamps();

            $table->index(['status', 'aberto_em']);
            $table->index('user_id');
        });

        Schema::create('vendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sessao_caixa_id')->constrained('sessoes_caixa')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('status', 20)->default('finalizada');
            $table->decimal('subtotal', 12, 2);
            $table->string('desconto_tipo', 20)->default('nenhum'); // nenhum|percentual|valor
            $table->decimal('desconto_valor', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('forma_pagamento', 40)->default('dinheiro');
            $table->timestamp('finalizada_em')->nullable();
            $table->timestamps();

            $table->index(['sessao_caixa_id', 'finalizada_em']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('itens_venda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venda_id')->constrained('vendas')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->decimal('quantidade', 12, 3);
            $table->decimal('preco_unitario', 12, 2);
            $table->decimal('custo_unitario', 12, 2)->default(0);
            $table->decimal('total_linha', 12, 2);
            $table->timestamps();

            $table->index('venda_id');
            $table->index('produto_id');
        });

        Schema::create('notas_nfce', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venda_id')->unique()->constrained('vendas')->cascadeOnDelete();
            $table->string('status', 20)->default('pendente'); // pendente|autorizada|rejeitada|erro
            $table->string('chave', 44)->nullable();
            $table->unsignedInteger('numero')->nullable();
            $table->unsignedSmallInteger('serie')->nullable();
            $table->string('protocolo')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('danfe_path')->nullable();
            $table->text('mensagem_erro')->nullable();
            $table->timestamp('enviada_em')->nullable();
            $table->timestamp('autorizada_em')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_nfce');
        Schema::dropIfExists('itens_venda');
        Schema::dropIfExists('vendas');
        Schema::dropIfExists('sessoes_caixa');
    }
};
