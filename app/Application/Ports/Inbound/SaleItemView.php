<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

final class SaleItemView
{
    public function __construct(
        public readonly string $id,
        public readonly string $productId,
        public readonly string $productName,
        public readonly string $categoryName,
        public readonly int $quantity,
        public readonly string $unitPrice,
        public readonly string $subtotal
    ) {}
}
