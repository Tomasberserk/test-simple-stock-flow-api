<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final class LoginDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $password
    ) {}
}
