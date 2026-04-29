<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_token_and_user(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Rina Naufal',
            'email' => 'rina@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'rina@example.com',
        ]);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
            ]);
    }

    public function test_profile_requires_auth(): void
    {
        $response = $this->getJson('/api/auth/profile');

        $response->assertUnauthorized();
    }

    public function test_profile_returns_user_for_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'profile@example.com',
        ]);

        $plainToken = Str::random(60);
        $user->forceFill([
            'api_token' => hash('sha256', $plainToken),
        ])->save();

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$plainToken)
            ->getJson('/api/auth/profile');

        $response
            ->assertOk()
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }
}
