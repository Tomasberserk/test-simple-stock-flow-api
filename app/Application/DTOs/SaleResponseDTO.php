<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final class SaleResponseDTO
{
    /**
     * @param SaleItemResponseDTO[] $items
     */
    public function __construct(
        public readonly string $id,
        public readonly string $soldAt,
        public readonly string $soldByUserId,
        public readonly string $soldByUsername,
        public readonly array $items,
        public readonly float $total
    ) {}
}
