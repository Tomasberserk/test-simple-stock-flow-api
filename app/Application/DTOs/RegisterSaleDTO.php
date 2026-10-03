<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final class RegisterSaleDTO
{
    /**
     * @param RegisterSaleItemDTO[] $items
     */
    public function __construct(
        public readonly string $soldByUserId,
        public readonly string $soldByUsername,
        public readonly array $items
    ) {}
}
