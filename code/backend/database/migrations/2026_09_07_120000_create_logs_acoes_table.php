<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_acoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_nome')->nullable();
            $table->string('user_login', 80)->nullable();
            $table->string('acao', 80);
            $table->string('recurso_tipo', 80)->nullable();
            $table->unsignedBigInteger('recurso_id')->nullable();
            $table->string('descricao', 500);
            $table->jsonb('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('acao');
            $table->index('user_id');
            $table->index(['recurso_tipo', 'recurso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_acoes');
    }
};
