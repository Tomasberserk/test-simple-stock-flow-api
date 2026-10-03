<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Sale;
use App\Domain\ValueObjects\DateRange;

interface SaleRepositoryInterface
{
    public function save(Sale $sale): void;

    public function findById(string $id): ?Sale;

    /**
     * @return array{items: Sale[], total: int, page: int, perPage: int, totalPages: int}
     */
    public function findByDateRange(DateRange $range, int $page = 1, int $perPage = 20): array;
}
