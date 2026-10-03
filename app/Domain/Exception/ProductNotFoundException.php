<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ProductNotFoundException extends BusinessRuleViolation
{
    public function __construct(string $message = "El producto no fue encontrado o está inactivo")
    {
        parent::__construct($message);
    }
}
