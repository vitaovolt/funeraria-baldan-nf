<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->string('documento_destinatario_nfce', 14)->nullable()->after('forma_pagamento');
            $table->decimal('valor_recebido', 12, 2)->nullable()->after('documento_destinatario_nfce');
        });

        Schema::table('sangrias_caixa', function (Blueprint $table) {
            $table->string('tipo', 20)->default('sangria')->after('valor');
        });
    }

    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropColumn(['documento_destinatario_nfce', 'valor_recebido']);
        });

        Schema::table('sangrias_caixa', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
