<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_fiscais', function (Blueprint $table) {
            $table->unsignedSmallInteger('serie_nfe')->default(1)->after('proximo_numero_nfce');
            $table->unsignedInteger('proximo_numero_nfe')->default(1)->after('serie_nfe');
        });

        Schema::table('notas_nfce', function (Blueprint $table) {
            $table->string('tipo', 10)->default('nfce')->after('venda_id'); // nfce|nfe
            $table->index(['tipo', 'status']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('inscricao_estadual', 30)->nullable()->after('documento');
            $table->string('logradouro')->nullable()->after('telefone');
            $table->string('numero', 20)->nullable()->after('logradouro');
            $table->string('complemento')->nullable()->after('numero');
            $table->string('bairro', 120)->nullable()->after('complemento');
            $table->string('cidade', 120)->nullable()->after('bairro');
            $table->string('uf', 2)->nullable()->after('cidade');
            $table->string('cep', 10)->nullable()->after('uf');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'inscricao_estadual',
                'logradouro',
                'numero',
                'complemento',
                'bairro',
                'cidade',
                'uf',
                'cep',
            ]);
        });

        Schema::table('notas_nfce', function (Blueprint $table) {
            $table->dropIndex(['tipo', 'status']);
            $table->dropColumn('tipo');
        });

        Schema::table('configuracoes_fiscais', function (Blueprint $table) {
            $table->dropColumn(['serie_nfe', 'proximo_numero_nfe']);
        });
    }
};
