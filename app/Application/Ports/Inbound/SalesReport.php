<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

final class SalesReport
{
    /**
     * @param SalesReportRow[] $items
     */
    public function __construct(
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly int $totalSalesCount,
        public readonly string $grandTotal,
        public readonly array $items
    ) {}
}
