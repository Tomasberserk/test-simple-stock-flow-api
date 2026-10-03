<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\DTOs\SaleItemResponseDTO;
use App\Application\DTOs\SaleResponseDTO;
use App\Domain\Entities\SaleItem;
use App\Domain\Repositories\SaleRepositoryInterface;

final class GetSaleByIdUseCase
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository
    ) {}

    public function execute(string $id): ?SaleResponseDTO
    {
        $sale = $this->saleRepository->findById($id);
        if ($sale === null) {
            return null;
        }

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
    }
}
