<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidRoleException extends BusinessRuleViolation
{
    public function __construct(string $message = "El rol especificado no es válido")
    {
        parent::__construct($message);
    }
}
