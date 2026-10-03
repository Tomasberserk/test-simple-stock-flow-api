<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\DTOs\ProductDTO;
use App\Application\DTOs\UpdateProductDTO;
use App\Domain\Exceptions\InvalidCategoryException;
use App\Domain\Exceptions\ProductNotFoundException;
use App\Domain\Repositories\CategoryRepositoryInterface;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\ValueObjects\Money;

final class UpdateProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(UpdateProductDTO $dto): ProductDTO
    {
        $product = $this->productRepository->findById($dto->id);
        if ($product === null) {
            throw new ProductNotFoundException("El producto solicitado no existe");
        }

        if ($dto->categoryId !== $product->getCategoryId()) {
            $category = $this->categoryRepository->findById($dto->categoryId);
            if ($category === null) {
                throw new InvalidCategoryException("La categoría especificada no existe");
            }
            $product->setCategory($dto->categoryId);
        }

        $product->rename($dto->name);
        $product->changePrice(new Money($dto->price));
        if ($dto->imageKey !== null) {
            $product->attachImage($dto->imageKey);
        }

        $this->productRepository->update($product);

        return new ProductDTO(
            id: $product->getId(),
            name: $product->getName(),
            price: $product->getPrice()->toFloat(),
            stock: $product->getStock(),
            categoryId: $product->getCategoryId(),
            imageKey: $product->getImageKey()
        );
    }
}
