<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

abstract class BusinessRuleViolation extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
