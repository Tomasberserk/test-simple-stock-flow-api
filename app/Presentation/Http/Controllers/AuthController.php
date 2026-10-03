<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\Ports\Inbound\Authenticate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthController
{
    public function __construct(
        private readonly Authenticate $authenticate
    ) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'El nombre de usuario es requerido',
            'password.required' => 'La contraseña es requerida',
        ]);

        $result = $this->authenticate->login($data['username'], $data['password']);

        return response()->json([
            'token' => $result->token,
            'expiresAt' => $result->expiresAt,
            'userId' => $result->userId,
            'username' => $result->username,
            'role' => $result->role,
        ], Response::HTTP_OK);
    }

    public function registerSeller(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string|min:3|max:50',
            'password' => 'required|string|min:6',
        ], [
            'username.required' => 'El nombre de usuario es requerido',
            'password.required' => 'La contraseña es requerida',
        ]);

        $result = $this->authenticate->registerSeller($data['username'], $data['password']);

        return response()->json([
            'token' => $result->token,
            'expiresAt' => $result->expiresAt,
            'userId' => $result->userId,
            'username' => $result->username,
            'role' => $result->role,
        ], Response::HTTP_CREATED);
    }
}
