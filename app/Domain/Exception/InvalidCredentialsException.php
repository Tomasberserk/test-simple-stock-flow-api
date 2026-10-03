<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidCredentialsException extends BusinessRuleViolation
{
    public function __construct(string $message = "Credenciales inválidas")
    {
        parent::__construct($message);
    }
}
