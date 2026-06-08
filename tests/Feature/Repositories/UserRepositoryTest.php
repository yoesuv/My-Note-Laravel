<?php

namespace Tests\Feature\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_by_email_normalizes_email_before_lookup(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $foundUser = app(UserRepository::class)->findByEmail('  TEST@example.com  ');

        $this->assertTrue($user->is($foundUser));
    }
}
