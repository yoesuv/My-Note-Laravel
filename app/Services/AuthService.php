<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    private const API_TOKEN_NAME = 'api-token';

    public function __construct(
        private readonly UserRepository $users,
    ) {}

    /**
     * @param  array{full_name: string, email: string, password: string}  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        try {
            $user = $this->users->create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
        } catch (QueryException $exception) {
            $this->handleDuplicateEmailQueryException($exception);
        }

        return $this->authResponse($user);
    }

    /**
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $user = $this->users->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->tokens()->where('name', self::API_TOKEN_NAME)->delete();

        return $this->authResponse($user);
    }

    public function logout(User $user): void
    {
        $accessToken = $user->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }
    }

    /**
     * @return array{user: User, token: string}
     */
    private function authResponse(User $user): array
    {
        return [
            'user' => $user,
            'token' => $user->createToken(self::API_TOKEN_NAME)->plainTextToken,
        ];
    }

    /**
     * @throws QueryException
     * @throws ValidationException
     */
    private function handleDuplicateEmailQueryException(QueryException $exception): never
    {
        if ($this->isDuplicateEmailQueryException($exception)) {
            $this->throwEmailTakenValidationException();
        }

        throw $exception;
    }

    private function isDuplicateEmailQueryException(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = $exception->getMessage().' '.(string) ($exception->errorInfo[2] ?? '');

        if (! in_array($sqlState, ['23000', '23505'], true)) {
            return false;
        }

        if ($sqlState === '23000' && ! in_array($driverCode, [19, 1062, 2067], true)) {
            return false;
        }

        return str_contains($message, 'users.email')
            || str_contains($message, 'users_email_unique');
    }

    /**
     * @throws ValidationException
     */
    private function throwEmailTakenValidationException(): never
    {
        throw ValidationException::withMessages([
            'email' => ['The email has already been taken.'],
        ]);
    }
}
