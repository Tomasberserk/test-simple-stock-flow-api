<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\ValueObjects\DateRange;

interface SalesReportQueryInterface
{
    /**
     * Resuelve la agregación directamente en el motor de base de datos (CA-06.5).
     * Devuelve una fila por producto y etiqueta de categoría congelada.
     *
     * @return array{
     *     startDate: string,
     *     endDate: string,
     *     totalSalesCount: int,
     *     grandTotal: float,
     *     items: array<int, array{
     *         productId: string,
     *         productName: string,
     *         categoryName: string,
     *         unitsSold: int,
     *         revenue: float
     *     }>
     * }
     */
    public function generateReport(DateRange $range): array;
}
