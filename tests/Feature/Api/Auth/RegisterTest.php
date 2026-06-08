<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDOException;
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

    public function test_register_stores_trimmed_lowercase_email(): void
    {
        $response = $this->postJson('/api/register', [
            'full_name' => 'Case User',
            'email' => '  CASE@example.com  ',
            'password' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'case@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'case@example.com',
        ]);
    }

    public function test_register_email_unique_validation_uses_normalized_email(): void
    {
        $this->postJson('/api/register', [
            'full_name' => 'First User',
            'email' => 'same@example.com',
            'password' => 'secret123',
        ])->assertCreated();

        $response = $this->postJson('/api/register', [
            'full_name' => 'Second User',
            'email' => '  SAME@example.com  ',
            'password' => 'secret123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_duplicate_email_insert_collision_returns_validation_error_for_sqlite(): void
    {
        $this->bindUserRepositoryThatThrows($this->queryException(
            sqlState: '23000',
            driverCode: 19,
            message: 'UNIQUE constraint failed: users.email',
        ));

        $response = $this->postJson('/api/register', [
            'full_name' => 'Race User',
            'email' => 'race@example.com',
            'password' => 'secret123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    public function test_register_duplicate_email_insert_collision_returns_validation_error_for_mysql(): void
    {
        $this->bindUserRepositoryThatThrows($this->queryException(
            sqlState: '23000',
            driverCode: 1062,
            message: "Duplicate entry 'race@example.com' for key 'users_email_unique'",
        ));

        $response = $this->postJson('/api/register', [
            'full_name' => 'Race User',
            'email' => 'race@example.com',
            'password' => 'secret123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    public function test_register_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
                ->postJson('/api/register', [
                    'full_name' => 'Rate Limited User',
                    'email' => "rate{$attempt}@example.com",
                    'password' => 'secret123',
                ])
                ->assertCreated();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
            ->postJson('/api/register', [
                'full_name' => 'Rate Limited User',
                'email' => 'rate6@example.com',
                'password' => 'secret123',
            ])
            ->assertTooManyRequests();
    }

    private function bindUserRepositoryThatThrows(QueryException $exception): void
    {
        $this->app->bind(UserRepository::class, fn () => new class($exception) extends UserRepository
        {
            public function __construct(private readonly QueryException $exception) {}

            /**
             * @param  array{name: string, email: string, password: string}  $data
             */
            public function create(array $data): User
            {
                throw $this->exception;
            }
        });
    }

    private function queryException(string $sqlState, int $driverCode, string $message): QueryException
    {
        $previous = new PDOException($message);
        $previous->errorInfo = [$sqlState, $driverCode, $message];

        return new QueryException('testing', 'insert into users', [], $previous);
    }
}
