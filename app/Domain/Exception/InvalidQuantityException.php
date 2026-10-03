<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidQuantityException extends BusinessRuleViolation
{
    public function __construct(string $message = "La cantidad debe ser estrictamente mayor que cero")
    {
        parent::__construct($message);
    }
}
