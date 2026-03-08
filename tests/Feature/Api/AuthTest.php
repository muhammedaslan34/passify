<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_token_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'secret123',
            'device_name' => 'chrome-extension',
        ]);

        $response->assertOk()->assertJsonStructure(['token']);
    }

    public function test_invalid_credentials_return_422(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->postJson('/api/auth/token', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'device_name' => 'test',
        ])->assertUnprocessable();
    }

    public function test_user_can_revoke_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/auth/token')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/organizations')
            ->assertUnauthorized();
    }
}
