<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Model\DateRange;
use App\Application\Ports\Inbound\GetSalesReport;
use App\Application\Ports\Inbound\SalesReport;
use App\Application\Ports\Outbound\SalesReportQuery;

final class SalesReportService implements GetSalesReport
{
    public function __construct(
        private readonly SalesReportQuery $salesReportQuery
    ) {}

    public function execute(string $startDate, string $endDate): SalesReport
    {
        $range = new DateRange($startDate, $endDate);
        // Resuelve la agregación directamente en la base de datos (CA-06.5)
        return $this->salesReportQuery->execute($range);
    }
}
