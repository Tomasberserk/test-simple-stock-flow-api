<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

interface GetSalesReport
{
    public function execute(string $startDate, string $endDate): SalesReport;
}
