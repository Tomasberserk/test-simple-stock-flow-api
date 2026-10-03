<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Model\DateRange;
use App\Application\Model\PageRequest;
use App\Application\Ports\Inbound\GetSales;
use App\Application\Ports\Inbound\PagedResult;
use App\Application\Ports\Inbound\SaleItemView;
use App\Application\Ports\Inbound\SaleView;
use App\Application\Ports\Outbound\SaleRepository;
use App\Domain\Model\Sale;
use App\Domain\Model\SaleItem;
use App\Domain\ValueObject\SaleId;

final class GetSalesService implements GetSales
{
    public function __construct(
        private readonly SaleRepository $saleRepository
    ) {}

    public function getSaleById(string $id): ?SaleView
    {
        $sale = $this->saleRepository->findById(SaleId::fromString($id));
        if ($sale === null) {
            return null;
        }

        return self::toView($sale);
    }

    public function listSalesByRange(string $startDate, string $endDate, int $page = 1, int $perPage = 20): PagedResult
    {
        $range = new DateRange($startDate, $endDate);
        $pageReq = new PageRequest($page, $perPage);

        $result = $this->saleRepository->findByDateRange($range, $pageReq->page, $pageReq->perPage);

        $views = array_map(fn(Sale $sale) => self::toView($sale), $result['items']);

        return new PagedResult(
            items: $views,
            total: $result['total'],
            page: $result['page'],
            perPage: $result['perPage'],
            totalPages: $result['totalPages']
        );
    }

    private static function toView(Sale $sale): SaleView
    {
        $itemViews = array_map(function (SaleItem $item) {
            return new SaleItemView(
                id: $item->getId(),
                productId: $item->getProductId()->getValue(),
                productName: $item->getProductName(),
                categoryName: $item->getCategoryName(),
                quantity: $item->getQuantity()->getValue(),
                unitPrice: $item->getUnitPrice()->getAmountString(),
                subtotal: $item->getSubtotal()->getAmountString()
            );
        }, $sale->getItems());

        return new SaleView(
            id: $sale->getId()->getValue(),
            soldAt: $sale->getSoldAt()->format('Y-m-d\TH:i:s.u\Z'),
            soldByUserId: $sale->getSoldByUserId()->getValue(),
            soldBy: $sale->getSoldByUsername()->getValue(),
            items: $itemViews,
            total: $sale->getTotal()->getAmountString()
        );
    }
}
