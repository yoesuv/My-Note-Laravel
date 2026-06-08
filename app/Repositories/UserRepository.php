<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Str;

class UserRepository
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', Str::lower(trim($email)))->first();
    }
}
