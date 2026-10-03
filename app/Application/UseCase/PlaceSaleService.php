<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Ports\Inbound\PlaceSale;
use App\Application\Ports\Inbound\PlaceSaleCommand;
use App\Application\Ports\Inbound\SaleItemView;
use App\Application\Ports\Inbound\SaleView;
use App\Application\Ports\Outbound\CategoryRepository;
use App\Application\Ports\Outbound\Clock;
use App\Application\Ports\Outbound\ProductRepository;
use App\Application\Ports\Outbound\SaleRepository;
use App\Application\Ports\Outbound\UnitOfWork;
use App\Domain\Exception\EmptySaleException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Exception\RepeatedProductException;
use App\Domain\Model\Sale;
use App\Domain\Model\SaleItem;
use App\Domain\ValueObject\ProductId;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\SaleId;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;
use Ramsey\Uuid\Uuid;

final class PlaceSaleService implements PlaceSale
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly SaleRepository $saleRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly UnitOfWork $unitOfWork,
        private readonly Clock $clock
    ) {}

    public function execute(PlaceSaleCommand $command): SaleView
    {
        if (empty($command->items)) {
            throw new EmptySaleException("Una venta debe contener al menos una línea");
        }

        // CA-04.4: Prohíbe productos duplicados en la misma venta
        $seenProductIds = [];
        foreach ($command->items as $item) {
            if (in_array($item->productId, $seenProductIds, true)) {
                throw new RepeatedProductException("Un producto no puede repetirse en varias líneas de la misma venta");
            }
            $seenProductIds[] = $item->productId;
        }

        /** @var Sale $sale */
        $sale = $this->unitOfWork->execute(function () use ($command, $seenProductIds): Sale {
            $productIds = array_map(fn($id) => ProductId::fromString($id), $seenProductIds);
            $products = $this->productRepository->findByIds($productIds);

            $productMap = [];
            foreach ($products as $p) {
                $productMap[$p->getId()->getValue()] = $p;
            }

            foreach ($command->items as $itemCmd) {
                if (!isset($productMap[$itemCmd->productId])) {
                    throw new ProductNotFoundException("El producto con ID '{$itemCmd->productId}' no existe o está inactivo");
                }
            }

            $categories = $this->categoryRepository->findAll();
            $categoryMap = [];
            foreach ($categories as $cat) {
                $categoryMap[$cat->getId()->getValue()] = $cat->getName();
            }

            $saleId = SaleId::generate();
            $soldAt = $this->clock->now();
            $saleItems = [];

            foreach ($command->items as $itemCmd) {
                $product = $productMap[$itemCmd->productId];
                $quantity = Quantity::of($itemCmd->quantity);

                // Descuenta stock según invariantes (lanza InsufficientStockException si no alcanza)
                $product->withdrawStock($quantity);
                $this->productRepository->update($product);

                $catName = $categoryMap[$product->getCategoryId()->getValue()] ?? 'General';

                $saleItem = new SaleItem(
                    id: Uuid::uuid4()->toString(),
                    saleId: $saleId,
                    productId: $product->getId(),
                    productName: $product->getName(),
                    categoryName: $catName,
                    quantity: $quantity,
                    unitPrice: $product->getPrice()
                );

                $saleItems[] = $saleItem;
            }

            $saleEntity = new Sale(
                id: $saleId,
                soldAt: $soldAt,
                soldByUsername: Username::fromString($command->soldByUsername),
                soldByUserId: UserId::fromString($command->soldByUserId),
                items: $saleItems
            );

            $saleEntity->ensureConfirmable();
            $this->saleRepository->save($saleEntity);

            return $saleEntity;
        });

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
