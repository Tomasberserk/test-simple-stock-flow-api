<?php

declare(strict_types=1);

namespace App\Application\Exception;

use RuntimeException;

final class ConcurrencyConflict extends RuntimeException
{
    public function __construct(string $message = "El recurso fue modificado concurrentemente por otra operación")
    {
        parent::__construct($message);
    }
}
