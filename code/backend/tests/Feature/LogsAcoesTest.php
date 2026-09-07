<?php

namespace Tests\Feature;

use App\Models\LogAcao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogsAcoesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_grava_log_de_acao(): void
    {
        $user = User::factory()->create([
            'login' => 'marcia',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'marcia',
            'password' => 'password',
        ])->assertOk();

        $this->assertDatabaseHas('logs_acoes', [
            'acao' => 'auth.login',
            'user_id' => $user->id,
            'user_login' => 'marcia',
        ]);
    }

    public function test_login_invalido_grava_falha_sem_senha(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'login' => 'ninguem',
            'password' => 'errada',
        ])->assertUnprocessable();

        $log = LogAcao::query()->where('acao', 'auth.login_falhou')->first();
        $this->assertNotNull($log);
        $this->assertSame('ninguem', $log->payload['login'] ?? null);
        $this->assertArrayNotHasKey('password', $log->payload ?? []);
    }

    public function test_admin_lista_logs_paginado(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/auth/logout')->assertOk();

        Sanctum::actingAs($admin);
        $res = $this->getJson('/api/v1/logs-acoes?per_page=15')->assertOk();

        $this->assertNotEmpty($res->json('data'));
        $this->assertArrayHasKey('current_page', $res->json('meta'));
        $this->assertSame('auth.logout', collect($res->json('data'))->firstWhere('acao', 'auth.logout')['acao'] ?? null);
    }

    public function test_operador_nao_lista_logs(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'operador']));

        $this->getJson('/api/v1/logs-acoes')->assertForbidden();
    }

    public function test_anonimo_nao_lista_logs(): void
    {
        $this->getJson('/api/v1/logs-acoes')->assertUnauthorized();
    }

    public function test_criar_usuario_grava_log_sem_senha(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/usuarios', [
            'name' => 'Equipe',
            'login' => 'equipe',
            'password' => 'senha123',
            'role' => 'operador',
        ])->assertCreated();

        $log = LogAcao::query()->where('acao', 'usuario.criar')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('equipe', (string) $log->descricao);
        $this->assertArrayNotHasKey('password', $log->payload ?? []);
    }

    public function test_prune_remove_apenas_logs_antigos(): void
    {
        LogAcao::query()->create([
            'acao' => 'auth.login',
            'descricao' => 'antigo',
            'created_at' => now()->subMonths(25),
        ]);
        LogAcao::query()->create([
            'acao' => 'auth.login',
            'descricao' => 'recente',
            'created_at' => now()->subDay(),
        ]);

        $this->artisan('logs-acoes:prune', ['--meses' => 24])->assertSuccessful();

        $this->assertDatabaseMissing('logs_acoes', ['descricao' => 'antigo']);
        $this->assertDatabaseHas('logs_acoes', ['descricao' => 'recente']);
    }
}
