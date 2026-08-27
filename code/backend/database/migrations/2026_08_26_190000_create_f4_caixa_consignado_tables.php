<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessoes_caixa', function (Blueprint $table) {
            $table->decimal('total_vendas', 12, 2)->nullable()->after('valor_abertura');
            $table->decimal('total_sangrias', 12, 2)->nullable()->after('total_vendas');
            $table->decimal('total_dinheiro_esperado', 12, 2)->nullable()->after('total_sangrias');
        });

        Schema::create('sangrias_caixa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sessao_caixa_id')->constrained('sessoes_caixa')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('valor', 12, 2);
            $table->string('motivo', 255)->nullable();
            $table->timestamps();

            $table->index('sessao_caixa_id');
        });

        Schema::create('consignados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('aberto'); // aberto|parcial|devolvido|vendido
            $table->string('observacao', 255)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('cliente_id');
        });

        Schema::create('itens_consignado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignado_id')->constrained('consignados')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->decimal('quantidade', 12, 3);
            $table->decimal('quantidade_devolvida', 12, 3)->default(0);
            $table->decimal('quantidade_vendida', 12, 3)->default(0);
            $table->decimal('preco_unitario', 12, 2);
            $table->timestamps();

            $table->index('consignado_id');
            $table->index('produto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_consignado');
        Schema::dropIfExists('consignados');
        Schema::dropIfExists('sangrias_caixa');

        Schema::table('sessoes_caixa', function (Blueprint $table) {
            $table->dropColumn(['total_vendas', 'total_sangrias', 'total_dinheiro_esperado']);
        });
    }
};
