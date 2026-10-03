<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Model\Product;
use App\Domain\ValueObject\CategoryId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductId;
use App\Infrastructure\Persistence\Models\ProductModel;

final class ProductMapper
{
    public static function toDomain(ProductModel $model): Product
    {
        return new Product(
            id: ProductId::fromString((string) $model->id),
            name: (string) $model->name,
            price: Money::of((string) $model->price),
            stock: (int) $model->stock,
            categoryId: CategoryId::fromString((string) $model->category_id),
            imageKey: $model->image_key !== null ? (string) $model->image_key : null
        );
    }

    public static function toPersistenceArray(Product $domain, int $version = 1): array
    {
        return [
            'id' => $domain->getId()->getValue(),
            'name' => $domain->getName(),
            'price' => (float) $domain->getPrice()->getAmountString(),
            'stock' => $domain->getStock(),
            'category_id' => $domain->getCategoryId()->getValue(),
            'image_key' => $domain->getImageKey(),
            'version' => $version,
        ];
    }
}
