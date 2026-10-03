<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidRoleException;

final class Role
{
    public const ADMIN = 'admin';
    public const SELLER = 'seller';

    private string $value;

    public function __construct(string $value)
    {
        $val = trim($value);
        if (!in_array($val, [self::ADMIN, self::SELLER], true)) {
            throw new InvalidRoleException("El rol '{$value}' no es válido. Debe ser 'admin' o 'seller'");
        }
        $this->value = $val;
    }

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    public static function seller(): self
    {
        return new self(self::SELLER);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isAdmin(): bool
    {
        return $this->value === self::ADMIN;
    }

    public function isSeller(): bool
    {
        return $this->value === self::SELLER;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
