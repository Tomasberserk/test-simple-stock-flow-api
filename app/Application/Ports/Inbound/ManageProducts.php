<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

interface ManageProducts
{
    public function listProducts(?string $query, ?string $categoryId, int $page = 1, int $perPage = 20): PagedResult;

    public function getProductById(string $id): ?ProductView;

    public function createProduct(string $name, string $price, int $stock, string $categoryId, ?string $imageKey = null): ProductView;

    public function updateProduct(string $id, string $name, string $price, int $stock, string $categoryId, ?string $imageKey = null): ProductView;

    public function deleteProduct(string $id): void;

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function listCategories(): array;

    public function uploadImage(string $productId, string $content, string $extension): ProductView;
}
