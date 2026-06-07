<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $auth = $this->auth->register($request->validated());

        return response()->json([
            'message' => 'User registered successfully.',
            'data' => [
                'user' => new UserResource($auth['user']),
                'token' => $auth['token'],
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $auth = $this->auth->login($request->validated());

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($auth['user']),
                'token' => $auth['token'],
            ],
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }
}
