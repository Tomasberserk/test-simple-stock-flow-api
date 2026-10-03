<?php

declare(strict_types=1);

namespace App\Application\UseCases\Auth;

use App\Application\DTOs\AuthTokenDTO;
use App\Application\DTOs\LoginDTO;
use App\Domain\Entities\User;
use App\Domain\Exceptions\InvalidCredentialsException;
use App\Domain\Repositories\UserRepositoryInterface;
use Firebase\JWT\JWT;

final class LoginUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(LoginDTO $dto): AuthTokenDTO
    {
        $normalizedUsername = User::normalizeUsername($dto->username);
        $user = $this->userRepository->findByUsername($normalizedUsername);

        // CA-07.2: Dadas credenciales inválidas, rechazo que NO revela si falló el usuario o la contraseña
        if ($user === null || !password_verify($dto->password, $user->getPasswordHash())) {
            throw new InvalidCredentialsException("Credenciales inválidas");
        }

        $now = time();
        $expiresAtTimestamp = $now + (8 * 3600); // 8 horas de sesión
        $secretKey = env('JWT_SECRET', 'supersecretjwtkeyforstockflowtest2026');

        $payload = [
            'iss' => 'stockflow-api',
            'sub' => $user->getId(),
            'username' => $user->getUsername(),
            'role' => $user->getRole(),
            'iat' => $now,
            'exp' => $expiresAtTimestamp,
        ];

        // Codificación JWT estándar
        $token = self::encodeJwt($payload, $secretKey);

        return new AuthTokenDTO(
            token: $token,
            expiresAt: date('c', $expiresAtTimestamp),
            userId: $user->getId(),
            username: $user->getUsername(),
            role: $user->getRole()
        );
    }

    /**
     * Codificador JWT puro y autocontenido en PHP para evitar dependencias obligatorias
     */
    private static function encodeJwt(array $payload, string $secret): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $base64UrlHeader = self::base64UrlEncode((string) json_encode($header));
        $base64UrlPayload = self::base64UrlEncode((string) json_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    private static function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
