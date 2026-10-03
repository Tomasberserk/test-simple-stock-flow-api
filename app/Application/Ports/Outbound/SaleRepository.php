<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Application\Model\DateRange;
use App\Domain\Model\Sale;
use App\Domain\ValueObject\SaleId;

interface SaleRepository
{
    public function save(Sale $sale): void;

    public function findById(SaleId $id): ?Sale;

    /**
     * @return array{items: Sale[], total: int, page: int, perPage: int, totalPages: int}
     */
    public function findByDateRange(DateRange $range, int $page, int $perPage): array;
}
