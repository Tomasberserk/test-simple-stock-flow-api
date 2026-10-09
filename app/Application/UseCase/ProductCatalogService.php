<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Model\PageRequest;
use App\Application\Ports\Inbound\ManageProducts;
use App\Application\Ports\Inbound\PagedResult;
use App\Application\Ports\Inbound\ProductView;
use App\Application\Ports\Outbound\CategoryRepository;
use App\Application\Ports\Outbound\FileStorage;
use App\Application\Ports\Outbound\ProductRepository;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Exception\UnknownCategoryException;
use App\Domain\Model\Product;
use App\Domain\ValueObject\CategoryId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductId;

final class ProductCatalogService implements ManageProducts
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly FileStorage $fileStorage
    ) {}

    public function listProducts(?string $query, ?string $categoryId, int $page = 1, int $perPage = 20): PagedResult
    {
        $pageReq = new PageRequest($page, $perPage);
        $catId = ($categoryId !== null && trim($categoryId) !== '') ? CategoryId::fromString($categoryId) : null;

        $result = $this->productRepository->search($query, $catId, $pageReq->page, $pageReq->perPage);

        $views = array_map(function (Product $p) {
            return new ProductView(
                id: $p->getId()->getValue(),
                name: $p->getName(),
                price: $p->getPrice()->getAmountString(),
                stock: $p->getStock(),
                categoryId: $p->getCategoryId()->getValue(),
                imageKey: $p->getImageKey()
            );
        }, $result['items']);

        return new PagedResult(
            items: $views,
            total: $result['total'],
            page: $result['page'],
            perPage: $result['perPage'],
            totalPages: $result['totalPages']
        );
    }

    public function getProductById(string $id): ?ProductView
    {
        $product = $this->productRepository->findById(ProductId::fromString($id));
        if ($product === null) {
            return null;
        }

        return new ProductView(
            id: $product->getId()->getValue(),
            name: $product->getName(),
            price: $product->getPrice()->getAmountString(),
            stock: $product->getStock(),
            categoryId: $product->getCategoryId()->getValue(),
            imageKey: $product->getImageKey()
        );
    }

    public function createProduct(string $name, string $price, int $stock, string $categoryId, ?string $imageKey = null): ProductView
    {
        $catId = CategoryId::fromString($categoryId);
        $category = $this->categoryRepository->findById($catId);
        if ($category === null) {
            throw new UnknownCategoryException("La categoría especificada no existe");
        }

        $product = new Product(
            id: ProductId::generate(),
            name: $name,
            price: new Money($price),
            stock: $stock,
            categoryId: $catId,
            imageKey: $imageKey
        );

        $this->productRepository->save($product);

        return new ProductView(
            id: $product->getId()->getValue(),
            name: $product->getName(),
            price: $product->getPrice()->getAmountString(),
            stock: $product->getStock(),
            categoryId: $product->getCategoryId()->getValue(),
            imageKey: $product->getImageKey()
        );
    }

    public function updateProduct(string $id, string $name, string $price, int $stock, string $categoryId, ?string $imageKey = null): ProductView
    {
        $product = $this->productRepository->findById(ProductId::fromString($id));
        if ($product === null) {
            throw new ProductNotFoundException("El producto solicitado no existe");
        }

        $catId = CategoryId::fromString($categoryId);
        if (!$catId->equals($product->getCategoryId())) {
            $category = $this->categoryRepository->findById($catId);
            if ($category === null) {
                throw new UnknownCategoryException("La categoría especificada no existe");
            }
            $product->setCategory($catId);
        }

        $product->rename($name);
        $product->changePrice(new Money($price));
        if ($imageKey !== null) {
            $product->attachImage($imageKey);
        }

        $this->productRepository->update($product);

        return new ProductView(
            id: $product->getId()->getValue(),
            name: $product->getName(),
            price: $product->getPrice()->getAmountString(),
            stock: $product->getStock(),
            categoryId: $product->getCategoryId()->getValue(),
            imageKey: $product->getImageKey()
        );
    }

    public function deleteProduct(string $id): void
    {
        $prodId = ProductId::fromString($id);
        $product = $this->productRepository->findById($prodId);
        if ($product === null) {
            throw new ProductNotFoundException("El producto solicitado no existe");
        }

        $this->productRepository->softDelete($prodId);
    }

    public function listCategories(): array
    {
        $categories = $this->categoryRepository->findAll();

        return array_map(function ($cat) {
            return [
                'id' => $cat->getId()->getValue(),
                'name' => $cat->getName(),
            ];
        }, $categories);
    }

    public function uploadImage(string $productId, string $content, string $extension): ProductView
    {
        $prodId = ProductId::fromString($productId);
        $product = $this->productRepository->findById($prodId);
        if ($product === null) {
            throw new ProductNotFoundException("El producto solicitado no existe");
        }

        $key = $this->fileStorage->store($content, $extension);
        $product->attachImage($key);

        $this->productRepository->update($product);

        return new ProductView(
            id: $product->getId()->getValue(),
            name: $product->getName(),
            price: $product->getPrice()->getAmountString(),
            stock: $product->getStock(),
            categoryId: $product->getCategoryId()->getValue(),
            imageKey: $product->getImageKey()
        );
    }
}
