<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Domain\Model\Product;
use App\Domain\ValueObject\CategoryId;
use App\Domain\ValueObject\ProductId;

interface ProductRepository
{
    public function findById(ProductId $id): ?Product;

    /**
     * @param ProductId[] $ids
     * @return Product[]
     */
    public function findByIds(array $ids): array;

    /**
     * @return array{items: Product[], total: int, page: int, perPage: int, totalPages: int}
     */
    public function search(?string $query, ?CategoryId $categoryId, int $page, int $perPage): array;

    public function save(Product $product): void;

    public function update(Product $product): void;

    public function softDelete(ProductId $id): void;
}
