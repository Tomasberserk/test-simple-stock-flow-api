<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

final class PagedResult
{
    /**
     * @param mixed[] $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $totalPages
    ) {}
}
