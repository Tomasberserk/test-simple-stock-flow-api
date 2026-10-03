<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class EmptySaleException extends BusinessRuleViolation
{
    public function __construct(string $message = "Una venta debe contener al menos una línea")
    {
        parent::__construct($message);
    }
}
