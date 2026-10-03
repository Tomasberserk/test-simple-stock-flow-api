<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InsufficientStockException extends BusinessRuleViolation
{
    public function __construct(string $message = "Stock insuficiente para completar la venta")
    {
        parent::__construct($message);
    }
}
