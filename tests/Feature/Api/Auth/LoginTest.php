<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_email_and_password(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('data.user.full_name', 'Test User')
            ->assertJsonStructure([
                'data' => ['user' => ['id', 'full_name', 'email'], 'token'],
            ]);
    }

    public function test_login_revokes_only_existing_api_tokens_with_same_name(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $user->createToken('api-token');
        $user->createToken('mobile-token');

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'api-token',
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'mobile-token',
        ]);
    }

    public function test_user_can_login_with_trimmed_lowercase_email(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => '  TEST@example.com  ',
            'password' => 'password',
        ]);

        $response->assertOk();
    }

    public function test_invalid_credentials_fail(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_unregistered_email_returns_same_error_as_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $wrongPasswordResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $unregisteredEmailResponse = $this->postJson('/api/login', [
            'email' => 'unknown@example.com',
            'password' => 'password',
        ]);

        $wrongPasswordResponse
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The provided credentials are incorrect.')
            ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');

        $unregisteredEmailResponse
            ->assertUnprocessable()
            ->assertJsonPath('message', $wrongPasswordResponse->json('message'))
            ->assertJsonPath('errors.email.0', $wrongPasswordResponse->json('errors.email.0'));
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_is_rate_limited_by_email_and_ip(): void
    {
        User::factory()->create([
            'email' => 'limited@example.com',
            'password' => Hash::make('password'),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.20'])
                ->postJson('/api/login', [
                    'email' => 'limited@example.com',
                    'password' => 'wrong-password',
                ])
                ->assertUnprocessable();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.20'])
            ->postJson('/api/login', [
                'email' => 'limited@example.com',
                'password' => 'wrong-password',
            ])
            ->assertTooManyRequests();
    }
}
