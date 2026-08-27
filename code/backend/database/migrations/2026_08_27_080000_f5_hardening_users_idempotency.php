<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('ativo')->default(true)->after('password');
            $table->string('role', 20)->default('operador')->after('ativo'); // operador|admin
            $table->index(['ativo', 'role']);
        });

        Schema::table('vendas', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->after('forma_pagamento');
            $table->unique('idempotency_key');
        });

        // Uma única sessão de caixa aberta (Postgres).
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE UNIQUE INDEX sessoes_caixa_uma_aberta ON sessoes_caixa ((1)) WHERE status = 'aberta'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS sessoes_caixa_uma_aberta');
        }

        Schema::table('vendas', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['ativo', 'role']);
            $table->dropColumn(['ativo', 'role']);
        });
    }
};
