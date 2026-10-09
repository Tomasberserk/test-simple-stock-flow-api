<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\Ports\Inbound\GetSales;
use App\Application\Ports\Inbound\PlaceSale;
use App\Application\Ports\Inbound\PlaceSaleCommand;
use App\Application\Ports\Inbound\PlaceSaleItemCommand;
use App\Application\Ports\Inbound\SaleItemView;
use App\Application\Ports\Inbound\SaleView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SaleController
{
    public function __construct(
        private readonly PlaceSale $placeSale,
        private readonly GetSales $getSales
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|string|uuid',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'items.required' => 'La venta debe contener al menos un producto',
            'items.min' => 'La venta debe contener al menos un producto',
            'items.*.productId.required' => 'El identificador del producto es requerido',
            'items.*.productId.uuid' => 'El identificador del producto no es un UUID válido',
            'items.*.quantity.required' => 'La cantidad es requerida',
            'items.*.quantity.min' => 'La cantidad vendida debe ser de al menos 1 unidad',
        ]);

        $userId = (string) $request->attributes->get('auth_user_id');
        $username = (string) $request->attributes->get('auth_username');

        $items = array_map(function (array $rawItem) {
            return new PlaceSaleItemCommand(
                productId: $rawItem['productId'],
                quantity: (int) $rawItem['quantity']
            );
        }, $data['items']);

        $command = new PlaceSaleCommand(
            soldByUserId: $userId,
            soldByUsername: $username,
            items: $items
        );

        $saleView = $this->placeSale->execute($command);

        return response()->json(self::formatSale($saleView), Response::HTTP_CREATED);
    }

    public function index(Request $request): JsonResponse
    {
        $startDate = $request->query('startDate', '2000-01-01');
        $endDate = $request->query('endDate', '2099-12-31');
        $page = (int) $request->query('page', '1');
        $perPage = (int) $request->query('perPage', '20');

        $result = $this->getSales->listSalesByRange(
            startDate: (string) $startDate,
            endDate: (string) $endDate,
            page: max(1, $page),
            perPage: min(100, max(1, $perPage))
        );

        return response()->json([
            'items' => array_map(fn(SaleView $s) => self::formatSale($s), $result->items),
            'total' => $result->total,
            'page' => $result->page,
            'perPage' => $result->perPage,
            'totalPages' => $result->totalPages,
        ], Response::HTTP_OK);
    }

    public function show(string $id): JsonResponse|\Illuminate\Http\Response
    {
        $sale = $this->getSales->getSaleById($id);

        if ($sale === null) {
            return response('', Response::HTTP_NOT_FOUND, ['Content-Length' => '0']);
        }

        return response()->json(self::formatSale($sale), Response::HTTP_OK);
    }

    private static function formatSale(SaleView $sale): array
    {
        return [
            'id' => $sale->id,
            'soldAt' => $sale->soldAt,
            'soldByUserId' => $sale->soldByUserId,
            'soldBy' => $sale->soldBy,
            'items' => array_map(function (SaleItemView $item) {
                return [
                    'id' => $item->id,
                    'productId' => $item->productId,
                    'productName' => $item->productName,
                    'categoryName' => $item->categoryName,
                    'quantity' => $item->quantity,
                    'unitPrice' => (float) $item->unitPrice,
                    'subtotal' => (float) $item->subtotal,
                ];
            }, $sale->items),
            'total' => (float) $sale->total,
        ];
    }
}
