<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final class SaleItemResponseDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $productId,
        public readonly string $productName,
        public readonly string $categoryName,
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly float $subtotal
    ) {}
}
