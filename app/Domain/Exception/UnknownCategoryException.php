<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class UnknownCategoryException extends BusinessRuleViolation
{
    public function __construct(string $message = "La categoría especificada no existe")
    {
        parent::__construct($message);
    }
}
