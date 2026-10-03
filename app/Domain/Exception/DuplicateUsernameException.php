<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class DuplicateUsernameException extends BusinessRuleViolation
{
    public function __construct(string $message = "El nombre de usuario ya se encuentra registrado")
    {
        parent::__construct($message);
    }
}
