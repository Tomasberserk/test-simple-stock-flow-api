<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Ports\Inbound\AuthResult;
use App\Application\Ports\Outbound\TokenGenerator;
use App\Domain\ValueObject\Role;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;

final class JwtTokenGenerator implements TokenGenerator
{
    private readonly string $secret;
    private readonly int $ttlSeconds;

    public function __construct(?string $secret = null, int $ttlHours = 8)
    {
        $this->secret = $secret ?? env('JWT_SECRET', 'supersecretjwtkeyforstockflowtest2026');
        $this->ttlSeconds = $ttlHours * 3600;
    }

    public function generate(UserId $id, Username $username, Role $role): AuthResult
    {
        $now = time();
        $expiresAt = $now + $this->ttlSeconds;

        $payload = [
            'iss' => 'stockflow-api',
            'sub' => $id->getValue(),
            'username' => $username->getValue(),
            'role' => $role->getValue(),
            'iat' => $now,
            'exp' => $expiresAt,
        ];

        $token = self::encodeJwt($payload, $this->secret);

        return new AuthResult(
            token: $token,
            expiresAt: date('c', $expiresAt),
            userId: $id->getValue(),
            username: $username->getValue(),
            role: $role->getValue()
        );
    }

    public static function decodeJwt(string $token, ?string $secret = null): ?array
    {
        $key = $secret ?? env('JWT_SECRET', 'supersecretjwtkeyforstockflowtest2026');
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;
        $expectedSig = self::base64UrlEncode(hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $key, true));

        if (!hash_equals($expectedSig, $sigB64)) {
            return null;
        }

        $payloadJson = self::base64UrlDecode($payloadB64);
        $payload = json_decode($payloadJson, true);
        if (!is_array($payload) || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private static function encodeJwt(array $payload, string $secret): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $headerB64 = self::base64UrlEncode((string) json_encode($header));
        $payloadB64 = self::base64UrlEncode((string) json_encode($payload));
        $signature = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $secret, true);
        $sigB64 = self::base64UrlEncode($signature);

        return "{$headerB64}.{$payloadB64}.{$sigB64}";
    }

    private static function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
