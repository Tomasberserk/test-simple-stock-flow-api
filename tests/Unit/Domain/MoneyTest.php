<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\InvalidPriceException;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use Brick\Math\BigDecimal;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_money_rounds_half_up_to_two_decimals(): void
    {
        $m1 = new Money('12.344');
        $this->assertSame('12.34', $m1->getAmountString());

        $m2 = new Money('12.345');
        $this->assertSame('12.35', $m2->getAmountString());
    }

    public function test_money_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidPriceException::class);
        new Money('-0.01');
    }

    public function test_money_addition_and_multiplication_precision(): void
    {
        $price = new Money('19.99');
        $qty = new Quantity(3);

        $total = $price->multiply($qty);
        $this->assertSame('59.97', $total->getAmountString());

        $added = $total->add(new Money('10.03'));
        $this->assertSame('70.00', $added->getAmountString());
    }
}
