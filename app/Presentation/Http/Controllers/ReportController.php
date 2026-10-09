<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\Ports\Inbound\GetSalesReport;
use App\Application\Ports\Inbound\SalesReport;
use App\Application\Ports\Inbound\SalesReportRow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ReportController
{
    public function __construct(
        private readonly GetSalesReport $getSalesReport
    ) {}

    public function sales(Request $request): JsonResponse
    {
        $startDate = $request->query('from') ?? $request->query('startDate');
        $endDate = $request->query('to') ?? $request->query('endDate');

        if (!is_string($startDate) || !is_string($endDate)) {
            return response()->json([
                'type' => 'about:blank',
                'title' => 'Parámetros inválidos',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'detail' => 'Los parámetros de rango de fecha (from/to) son requeridos en formato YYYY-MM-DD'
            ], Response::HTTP_UNPROCESSABLE_ENTITY, [
                'Content-Type' => 'application/problem+json',
            ]);
        }

        $report = $this->getSalesReport->execute($startDate, $endDate);

        return response()->json([
            'startDate' => $report->startDate,
            'endDate' => $report->endDate,
            'totalSalesCount' => $report->totalSalesCount,
            'grandTotal' => (float) $report->grandTotal,
            'items' => array_map(function (SalesReportRow $row) {
                return [
                    'productId' => $row->productId,
                    'productName' => $row->productName,
                    'categoryName' => $row->categoryName,
                    'unitsSold' => $row->unitsSold,
                    'revenue' => (float) $row->revenue,
                ];
            }, $report->items),
        ], Response::HTTP_OK);
    }
}
