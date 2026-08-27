<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_retorna_token_e_usuario(): void
    {
        $user = User::factory()->create([
            'email' => 'caixa@baldan.local',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'caixa@baldan.local',
            'password' => 'password',
            'device_name' => 'test',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'caixa@baldan.local')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user' => ['id', 'name', 'email']]]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test',
        ]);
    }

    public function test_login_invalido_retorna_422_sem_criar_token(): void
    {
        User::factory()->create([
            'email' => 'caixa@baldan.local',
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'caixa@baldan.local',
            'password' => 'errada',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_logout_e_refresh(): void
    {
        User::factory()->create([
            'email' => 'caixa@baldan.local',
            'password' => Hash::make('password'),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'caixa@baldan.local',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'caixa@baldan.local');

        $refresh = $this->withToken($token)
            ->postJson('/api/v1/auth/refresh')
            ->assertOk();

        $newToken = $refresh->json('data.token');
        $this->assertNotSame($token, $newToken);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        $this->withToken($newToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        $this->withToken($newToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withToken($newToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_rotas_protegidas_sem_token_retornam_401(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
        $this->postJson('/api/v1/auth/refresh')->assertUnauthorized();
        $this->getJson('/api/v1/produtos')->assertUnauthorized();

        // Smoke PowerShell sem Accept JSON — não pode virar 500
        $this->call('GET', '/api/v1/produtos', [], [], [], [
            'HTTP_ACCEPT' => '*/*',
        ])->assertUnauthorized();
    }

    public function test_seed_operador_consegue_logar(): void
    {
        $this->seed();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'operador@baldan.local',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.user.email', 'operador@baldan.local')
            ->assertJsonPath('success', true);
    }
}
