<?php

namespace Tests\Feature\Api\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'full_name' => 'Yoesuv Developer',
            'email' => 'yoesuv@example.com',
            'password' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'User registered successfully.')
            ->assertJsonPath('data.user.full_name', 'Yoesuv Developer')
            ->assertJsonPath('data.user.email', 'yoesuv@example.com')
            ->assertJsonStructure([
                'data' => ['user' => ['id', 'full_name', 'email'], 'token'],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Yoesuv Developer',
            'email' => 'yoesuv@example.com',
        ]);
    }

    public function test_register_requires_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'full_name' => 'A',
            'email' => 'not-an-email',
            'password' => '12345',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['full_name', 'email', 'password']);
    }

    public function test_email_must_be_unique(): void
    {
        $this->postJson('/api/register', [
            'full_name' => 'First User',
            'email' => 'same@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/register', [
            'full_name' => 'Second User',
            'email' => 'same@example.com',
            'password' => 'secret123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
