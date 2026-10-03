<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Domain\Exceptions\ProductNotFoundException;
use App\Domain\Repositories\ProductRepositoryInterface;

final class SoftDeleteProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function execute(string $id): void
    {
        $product = $this->productRepository->findById($id);
        if ($product === null) {
            throw new ProductNotFoundException("El producto solicitado no existe");
        }

        $this->productRepository->softDelete($id);
    }
}
