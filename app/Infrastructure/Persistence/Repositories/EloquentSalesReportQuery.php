<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Model\DateRange;
use App\Application\Ports\Inbound\SalesReport;
use App\Application\Ports\Inbound\SalesReportRow;
use App\Application\Ports\Outbound\SalesReportQuery;
use Illuminate\Support\Facades\DB;

final class EloquentSalesReportQuery implements SalesReportQuery
{
    public function execute(DateRange $range): SalesReport
    {
        $startStr = $range->getFrom()->format('Y-m-d 00:00:00.000000');
        $endStr = $range->getTo()->format('Y-m-d 23:59:59.999999');

        // CA-06.5: La agregación se resuelve en la base de datos (GROUP BY en SQL)
        $rows = DB::table('sale_item as si')
            ->join('sale as s', 's.id', '=', 'si.sale_id')
            ->whereBetween('s.sold_at', [$startStr, $endStr])
            ->select([
                'si.product_id',
                'si.product_name',
                'si.category_name',
                DB::raw('SUM(si.quantity) as units_sold'),
                DB::raw('SUM(si.quantity * si.unit_price) as revenue'),
            ])
            ->groupBy('si.product_id', 'si.product_name', 'si.category_name')
            ->orderByDesc('revenue')
            ->get();

        $totalSalesCount = DB::table('sale')
            ->whereBetween('sold_at', [$startStr, $endStr])
            ->count();

        $grandTotalDecimal = 0.0;
        $items = [];

        foreach ($rows as $row) {
            $revenueFloat = (float) $row->revenue;
            $grandTotalDecimal += $revenueFloat;

            $items[] = new SalesReportRow(
                productId: (string) $row->product_id,
                productName: (string) $row->product_name,
                categoryName: (string) $row->category_name,
                unitsSold: (int) $row->units_sold,
                revenue: number_format($revenueFloat, 2, '.', '')
            );
        }

        return new SalesReport(
            startDate: $range->getFrom()->format('Y-m-d'),
            endDate: $range->getTo()->format('Y-m-d'),
            totalSalesCount: $totalSalesCount,
            grandTotal: number_format($grandTotalDecimal, 2, '.', ''),
            items: $items
        );
    }
}
