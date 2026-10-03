<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final class AuthTokenDTO
{
    public function __construct(
        public readonly string $token,
        public readonly string $expiresAt,
        public readonly string $userId,
        public readonly string $username,
        public readonly string $role
    ) {}
}
