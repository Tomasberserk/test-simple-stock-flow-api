<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\DTOs\RegisterSaleDTO;
use App\Application\DTOs\SaleItemResponseDTO;
use App\Application\DTOs\SaleResponseDTO;
use App\Application\Ports\TransactionManagerInterface;
use App\Domain\Entities\Sale;
use App\Domain\Entities\SaleItem;
use App\Domain\Exceptions\DuplicateProductInSaleException;
use App\Domain\Exceptions\EmptySaleException;
use App\Domain\Exceptions\ProductNotFoundException;
use App\Domain\Repositories\CategoryRepositoryInterface;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\Repositories\SaleRepositoryInterface;
use App\Domain\ValueObjects\Quantity;
use DateTimeImmutable;
use DateTimeZone;
use Ramsey\Uuid\Uuid;

final class RegisterSaleUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly TransactionManagerInterface $transactionManager
    ) {}

    public function execute(RegisterSaleDTO $dto): SaleResponseDTO
    {
        if (empty($dto->items)) {
            throw new EmptySaleException("Una venta debe contener al menos un producto");
        }

        // CA-04.4: Dado el mismo producto repetido en dos líneas, se rechaza
        $productIds = [];
        foreach ($dto->items as $item) {
            if (in_array($item->productId, $productIds, true)) {
                throw new DuplicateProductInSaleException("Un producto no puede repetirse en varias líneas de la misma venta");
            }
            $productIds[] = $item->productId;
        }

        // Ejecutar atómicamente a través del puerto de infraestructura (TransactionManagerInterface)
        /** @var Sale $sale */
        $sale = $this->transactionManager->execute(function () use ($dto, $productIds): Sale {
            $products = $this->productRepository->findByIds($productIds);
            $productMap = [];
            foreach ($products as $p) {
                $productMap[$p->getId()] = $p;
            }

            // Verificar que todos los productos existan y estén activos
            foreach ($dto->items as $itemDto) {
                if (!isset($productMap[$itemDto->productId])) {
                    throw new ProductNotFoundException("El producto con ID '{$itemDto->productId}' no existe o fue dado de baja");
                }
            }

            // Pre-cargar categorías para los nombres congelados
            $categories = $this->categoryRepository->findAll();
            $categoryMap = [];
            foreach ($categories as $cat) {
                $categoryMap[$cat->getId()] = $cat->getName();
            }

            $saleId = Uuid::uuid4()->toString();
            $soldAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $saleItems = [];

            foreach ($dto->items as $itemDto) {
                $product = $productMap[$itemDto->productId];
                $quantity = new Quantity($itemDto->quantity);

                // Descuenta stock; si es insuficiente, lanza InsufficientStockException y se revierte la transacción
                $product->withdrawStock($quantity);
                $this->productRepository->update($product);

                $categoryName = $categoryMap[$product->getCategoryId()] ?? 'General';

                $saleItemId = Uuid::uuid4()->toString();
                $saleItem = new SaleItem(
                    id: $saleItemId,
                    saleId: $saleId,
                    productId: $product->getId(),
                    productName: $product->getName(),
                    categoryName: $categoryName,
                    quantity: $quantity,
                    unitPrice: $product->getPrice()
                );

                $saleItems[] = $saleItem;
            }

            $saleEntity = new Sale(
                id: $saleId,
                soldAt: $soldAt,
                soldByUsername: $dto->soldByUsername,
                soldByUserId: $dto->soldByUserId,
                items: $saleItems
            );

            $saleEntity->ensureConfirmable();
            $this->saleRepository->save($saleEntity);

            return $saleEntity;
        });

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
