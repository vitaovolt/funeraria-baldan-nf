<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_fiscais', function (Blueprint $table) {
            $table->boolean('modulo_fiscal_ativo')->default(false)->after('codigo_ibge');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('login', 80)->nullable()->after('name');
        });

        $users = DB::table('users')->orderBy('id')->get(['id', 'email']);
        $usados = [];
        foreach ($users as $user) {
            $base = strtolower((string) strstr((string) $user->email, '@', true));
            if ($base === '' || $base === false) {
                $base = 'user'.$user->id;
            }
            $login = preg_replace('/[^a-z0-9._-]/', '', $base) ?: 'user'.$user->id;
            $candidato = $login;
            $n = 1;
            while (isset($usados[$candidato])) {
                $candidato = $login.$n;
                $n++;
            }
            $usados[$candidato] = true;
            DB::table('users')->where('id', $user->id)->update(['login' => $candidato]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('login');
        });

        DB::statement('ALTER TABLE users ALTER COLUMN login SET NOT NULL');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login']);
            $table->dropColumn('login');
        });

        Schema::table('configuracoes_fiscais', function (Blueprint $table) {
            $table->dropColumn('modulo_fiscal_ativo');
        });
    }
};
