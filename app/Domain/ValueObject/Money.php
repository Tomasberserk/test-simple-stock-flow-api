<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidPriceException;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class Money
{
    public const CURRENCY = 'COP';
    public const SCALE = 2;

    private BigDecimal $amount;

    public function __construct(BigDecimal|string|int $amount)
    {
        $bigDecimal = is_numeric($amount) || is_string($amount) 
            ? BigDecimal::of((string) $amount) 
            : $amount;

        // Rechaza importes negativos
        if ($bigDecimal->isNegative()) {
            throw new InvalidPriceException("El precio o importe no puede ser negativo");
        }

        // Redondeo exacto a 2 decimales con HALF_UP (aleja de cero en el empate)
        $this->amount = $bigDecimal->toScale(self::SCALE, RoundingMode::HALF_UP);
    }

    public static function of(string|int $amount): self
    {
        return new self($amount);
    }

    public static function zero(): self
    {
        return new self(BigDecimal::zero());
    }

    public function getAmount(): BigDecimal
    {
        return $this->amount;
    }

    public function getAmountString(): string
    {
        return (string) $this->amount;
    }

    public function isPositive(): bool
    {
        return $this->amount->isPositive();
    }

    public function isZero(): bool
    {
        return $this->amount->isZero();
    }

    public function add(self $other): self
    {
        return new self($this->amount->plus($other->amount));
    }

    public function multiply(Quantity $quantity): self
    {
        return new self($this->amount->multipliedBy($quantity->getValue()));
    }

    public function equals(self $other): bool
    {
        return $this->amount->isEqualTo($other->amount);
    }

    public function __toString(): string
    {
        return (string) $this->amount;
    }
}
