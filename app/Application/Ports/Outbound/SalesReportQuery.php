<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Application\Model\DateRange;
use App\Application\Ports\Inbound\SalesReport;

interface SalesReportQuery
{
    /**
     * Resuelve la agregación directamente en el motor de base de datos (CA-06.5).
     * Devuelve una fila por producto y etiqueta congelada de categoría.
     */
    public function execute(DateRange $range): SalesReport;
}
