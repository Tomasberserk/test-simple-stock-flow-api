<?php

declare(strict_types=1);

namespace App\Application\UseCases\Reports;

use App\Domain\Repositories\SalesReportQueryInterface;
use App\Domain\ValueObjects\DateRange;

final class GenerateSalesReportUseCase
{
    public function __construct(
        private readonly SalesReportQueryInterface $salesReportQuery
    ) {}

    public function execute(string $startDate, string $endDate): array
    {
        $range = new DateRange($startDate, $endDate);
        return $this->salesReportQuery->generateReport($range);
    }
}
