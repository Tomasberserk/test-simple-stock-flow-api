<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

final class SaleView
{
    /**
     * @param SaleItemView[] $items
     */
    public function __construct(
        public readonly string $id,
        public readonly string $soldAt,
        public readonly string $soldByUserId,
        public readonly string $soldBy,
        public readonly array $items,
        public readonly string $total
    ) {}
}
