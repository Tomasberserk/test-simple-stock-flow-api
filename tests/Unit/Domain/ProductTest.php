<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\InsufficientStockException;
use App\Domain\Exception\InvalidPriceException;
use App\Domain\Model\Product;
use App\Domain\ValueObject\CategoryId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductId;
use App\Domain\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function test_can_create_valid_product(): void
    {
        $product = new Product(
            id: ProductId::generate(),
            name: "Martillo de acero",
            price: new Money("25.50"),
            stock: 10,
            categoryId: new CategoryId("22222222-2222-4222-8222-222222222222")
        );

        $this->assertSame("Martillo de acero", $product->getName());
        $this->assertSame("25.50", $product->getPrice()->getAmountString());
        $this->assertSame(10, $product->getStock());
    }

    public function test_withdraw_stock_reduces_stock_accurately(): void
    {
        $product = new Product(
            id: ProductId::generate(),
            name: "Taladro",
            price: new Money("120.00"),
            stock: 5,
            categoryId: new CategoryId("22222222-2222-4222-8222-222222222222")
        );

        $product->withdrawStock(new Quantity(2));
        $this->assertSame(3, $product->getStock());
    }

    public function test_withdraw_more_than_available_stock_throws_insufficient_stock_exception(): void
    {
        $product = new Product(
            id: ProductId::generate(),
            name: "Sierra circular",
            price: new Money("85.00"),
            stock: 2,
            categoryId: new CategoryId("22222222-2222-4222-8222-222222222222")
        );

        $this->expectException(InsufficientStockException::class);
        $product->withdrawStock(new Quantity(3));
    }

    public function test_rejects_zero_or_negative_price(): void
    {
        $this->expectException(InvalidPriceException::class);
        new Product(
            id: ProductId::generate(),
            name: "Clavos",
            price: new Money("0.00"),
            stock: 100,
            categoryId: new CategoryId("22222222-2222-4222-8222-222222222222")
        );
    }
}
