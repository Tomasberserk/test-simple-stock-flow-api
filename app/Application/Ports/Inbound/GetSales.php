<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

interface GetSales
{
    public function getSaleById(string $id): ?SaleView;

    public function listSalesByRange(string $startDate, string $endDate, int $page = 1, int $perPage = 20): PagedResult;
}
