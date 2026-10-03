<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\DTOs\SaleItemResponseDTO;
use App\Application\DTOs\SaleResponseDTO;
use App\Domain\Entities\Sale;
use App\Domain\Entities\SaleItem;
use App\Domain\Repositories\SaleRepositoryInterface;
use App\Domain\ValueObjects\DateRange;

final class ListSalesUseCase
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository
    ) {}

    /**
     * @return array{items: SaleResponseDTO[], total: int, page: int, perPage: int, totalPages: int}
     */
    public function execute(string $startDate, string $endDate, int $page = 1, int $perPage = 20): array
    {
        $range = new DateRange($startDate, $endDate);
        $safePage = max(1, $page);
        $safePerPage = min(max(1, $perPage), 100);

        $result = $this->saleRepository->findByDateRange($range, $safePage, $safePerPage);

        $dtos = array_map(function (Sale $sale) {
            $itemDTOs = array_map(function (SaleItem $item) {
                return new SaleItemResponseDTO(
                    id: $item->getId(),
                    productId: $item->getProductId(),
                    productName: $item->getProductName(),
                    categoryName: $item->getCategoryName(),
                    quantity: $item->getQuantity()->getValue(),
                    unitPrice: $item->getUnitPrice()->toFloat(),
                    subtotal: $item->getSubtotal()->toFloat()
                );
            }, $sale->getItems());

            return new SaleResponseDTO(
                id: $sale->getId(),
                soldAt: $sale->getSoldAt()->format('Y-m-d\TH:i:s.u\Z'),
                soldByUserId: $sale->getSoldByUserId(),
                soldByUsername: $sale->getSoldByUsername(),
                items: $itemDTOs,
                total: $sale->getTotal()->toFloat()
            );
        }, $result['items']);

        return [
            'items' => $dtos,
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
