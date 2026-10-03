<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Product;

interface ProductRepositoryInterface
{
    public function findById(string $id): ?Product;

    /**
     * @param string[] $ids
     * @return Product[]
     */
    public function findByIds(array $ids): array;

    /**
     * @return array{items: Product[], total: int, page: int, perPage: int, totalPages: int}
     */
    public function search(?string $query, ?string $categoryId, int $page = 1, int $perPage = 20): array;

    public function save(Product $product): void;

    public function update(Product $product): void;

    public function softDelete(string $id): void;
}
