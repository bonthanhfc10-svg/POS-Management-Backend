<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);

        return $user;
    }

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'cashier@pos.test',
            'password' => bcrypt('password'),
            'role' => 'cashier',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'cashier@pos.test',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token']]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'cashier@pos.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'cashier@pos.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = $this->actingAsRole('cashier');

        $this->actingAs($user)
            ->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonMissingPath('data.password');
    }

    public function test_logout_revokes_token(): void
    {
        $user = $this->actingAsRole('cashier');

        $this->actingAs($user)
            ->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(0, $user->tokens()->count());
    }
}
