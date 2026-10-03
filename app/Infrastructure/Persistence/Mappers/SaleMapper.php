<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Model\Sale;
use App\Domain\Model\SaleItem;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductId;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\SaleId;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;
use App\Infrastructure\Persistence\Models\SaleItemModel;
use App\Infrastructure\Persistence\Models\SaleModel;
use DateTimeImmutable;

final class SaleMapper
{
    public static function toDomain(SaleModel $model): Sale
    {
        $items = [];
        if ($model->relationLoaded('items') || $model->items) {
            foreach ($model->items as $itemModel) {
                $items[] = new SaleItem(
                    id: (string) $itemModel->id,
                    saleId: SaleId::fromString((string) $itemModel->sale_id),
                    productId: ProductId::fromString((string) $itemModel->product_id),
                    productName: (string) $itemModel->product_name,
                    categoryName: (string) $itemModel->category_name,
                    quantity: Quantity::of((int) $itemModel->quantity),
                    unitPrice: Money::of((string) $itemModel->unit_price)
                );
            }
        }

        return new Sale(
            id: SaleId::fromString((string) $model->id),
            soldAt: new DateTimeImmutable((string) $model->sold_at),
            soldByUsername: Username::fromString((string) $model->sold_by_username),
            soldByUserId: UserId::fromString((string) $model->sold_by_user_id),
            items: $items
        );
    }
}
