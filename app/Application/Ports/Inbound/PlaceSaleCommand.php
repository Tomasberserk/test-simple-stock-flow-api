<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

final class PlaceSaleCommand
{
    /**
     * @param PlaceSaleItemCommand[] $items
     */
    public function __construct(
        public readonly string $soldByUserId,
        public readonly string $soldByUsername,
        public readonly array $items
    ) {}
}
