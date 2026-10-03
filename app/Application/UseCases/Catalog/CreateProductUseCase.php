<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\DTOs\CreateProductDTO;
use App\Application\DTOs\ProductDTO;
use App\Domain\Entities\Product;
use App\Domain\Exceptions\InvalidCategoryException;
use App\Domain\Repositories\CategoryRepositoryInterface;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\ValueObjects\Money;
use Ramsey\Uuid\Uuid;

final class CreateProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(CreateProductDTO $dto): ProductDTO
    {
        // CA-02.4: Dada una categoría inexistente, la creación se rechaza
        $category = $this->categoryRepository->findById($dto->categoryId);
        if ($category === null) {
            throw new InvalidCategoryException("La categoría especificada no existe");
        }

        $id = Uuid::uuid4()->toString();
        $product = new Product(
            id: $id,
            name: $dto->name,
            price: new Money($dto->price),
            stock: $dto->stock,
            categoryId: $dto->categoryId,
            imageKey: $dto->imageKey
        );

        $this->productRepository->save($product);

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
