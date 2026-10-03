<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\EmptySaleException;
use App\Domain\Exception\RepeatedProductException;
use App\Domain\Model\Sale;
use App\Domain\Model\SaleItem;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductId;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\SaleId;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class SaleTest extends TestCase
{
    public function test_sale_total_is_calculated_dynamically_from_items(): void
    {
        $saleId = SaleId::generate();
        $soldAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $seller = Username::fromString('carlos');
        $sellerId = UserId::generate();

        $item1 = new SaleItem(
            id: 'item-1',
            saleId: $saleId,
            productId: ProductId::generate(),
            productName: 'Tornillos',
            categoryName: 'Ferretería',
            quantity: new Quantity(5),
            unitPrice: new Money('10.50')
        );

        $item2 = new SaleItem(
            id: 'item-2',
            saleId: $saleId,
            productId: ProductId::generate(),
            productName: 'Tuercas',
            categoryName: 'Ferretería',
            quantity: new Quantity(2),
            unitPrice: new Money('5.25')
        );

        $sale = new Sale(
            id: $saleId,
            soldAt: $soldAt,
            soldByUsername: $seller,
            soldByUserId: $sellerId,
            items: [$item1, $item2]
        );

        // Subtotales: 5 * 10.50 = 52.50, 2 * 5.25 = 10.50. Total = 63.00
        $this->assertSame('52.50', $item1->getSubtotal()->getAmountString());
        $this->assertSame('10.50', $item2->getSubtotal()->getAmountString());
        $this->assertSame('63.00', $sale->getTotal()->getAmountString());
    }

    public function test_sale_rejects_duplicate_product_in_same_sale(): void
    {
        $saleId = SaleId::generate();
        $prodId = ProductId::generate();

        $item1 = new SaleItem('1', $saleId, $prodId, 'Pintura', 'Pinturas', new Quantity(1), new Money('20.00'));
        $item2 = new SaleItem('2', $saleId, $prodId, 'Pintura', 'Pinturas', new Quantity(2), new Money('20.00'));

        $this->expectException(RepeatedProductException::class);
        new Sale(
            id: $saleId,
            soldAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            soldByUsername: Username::fromString('ana'),
            soldByUserId: UserId::generate(),
            items: [$item1, $item2]
        );
    }

    public function test_sale_rejects_empty_sale_confirmation(): void
    {
        $sale = new Sale(
            id: SaleId::generate(),
            soldAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            soldByUsername: Username::fromString('ana'),
            soldByUserId: UserId::generate(),
            items: []
        );

        $this->expectException(EmptySaleException::class);
        $sale->ensureConfirmable();
    }
}
