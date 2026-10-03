<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\DTOs\ProductDTO;
use App\Domain\Repositories\ProductRepositoryInterface;

final class ListProductsUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    /**
     * @return array{items: ProductDTO[], total: int, page: int, perPage: int, totalPages: int}
     */
    public function execute(?string $query, ?string $categoryId, int $page = 1, int $perPage = 20): array
    {
        // CA-01.5: Si perPage excede el máximo permitido (100), se ajusta a 100
        $safePerPage = min(max(1, $perPage), 100);
        $safePage = max(1, $page);

        $result = $this->productRepository->search($query, $categoryId, $safePage, $safePerPage);

        $dtos = array_map(function ($product) {
            return new ProductDTO(
                id: $product->getId(),
                name: $product->getName(),
                price: $product->getPrice()->toFloat(),
                stock: $product->getStock(),
                categoryId: $product->getCategoryId(),
                imageKey: $product->getImageKey()
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
