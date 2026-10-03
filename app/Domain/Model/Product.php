<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Exception\BusinessRuleViolation;
use App\Domain\Exception\InsufficientStockException;
use App\Domain\Exception\InvalidPriceException;
use App\Domain\ValueObject\CategoryId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductId;
use App\Domain\ValueObject\Quantity;

final class Product
{
    private ProductId $id;
    private string $name;
    private Money $price;
    private int $stock;
    private CategoryId $categoryId;
    private ?string $imageKey;

    public function __construct(
        ProductId $id,
        string $name,
        Money $price,
        int $stock,
        CategoryId $categoryId,
        ?string $imageKey = null
    ) {
        $trimmedName = trim($name);
        if ($trimmedName === '') {
            throw new class("El nombre del producto no puede estar vacío") extends BusinessRuleViolation {};
        }

        if (!$price->isPositive()) {
            throw new InvalidPriceException("El precio del producto debe ser estrictamente mayor que cero");
        }

        if ($stock < 0) {
            throw new class("El stock inicial no puede ser negativo") extends BusinessRuleViolation {};
        }

        $this->id = $id;
        $this->name = $trimmedName;
        $this->price = $price;
        $this->stock = $stock;
        $this->categoryId = $categoryId;
        $this->imageKey = ($imageKey !== null && trim($imageKey) !== '') ? trim($imageKey) : null;
    }

    public function getId(): ProductId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getCategoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function getImageKey(): ?string
    {
        return $this->imageKey;
    }

    public function rename(string $newName): void
    {
        $trimmed = trim($newName);
        if ($trimmed === '') {
            throw new class("El nombre del producto no puede estar vacío") extends BusinessRuleViolation {};
        }
        $this->name = $trimmed;
    }

    public function changePrice(Money $newPrice): void
    {
        if (!$newPrice->isPositive()) {
            throw new InvalidPriceException("El precio del producto debe ser estrictamente mayor que cero");
        }
        $this->price = $newPrice;
    }

    public function setCategory(CategoryId $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function attachImage(?string $imageKey): void
    {
        $this->imageKey = ($imageKey !== null && trim($imageKey) !== '') ? trim($imageKey) : null;
    }

    public function withdrawStock(Quantity $quantity): void
    {
        $qty = $quantity->getValue();
        if ($qty > $this->stock) {
            throw new InsufficientStockException("Stock insuficiente para el producto '{$this->name}'. Disponible: {$this->stock}, Solicitado: {$qty}");
        }
        $this->stock -= $qty;
    }

    public function restock(Quantity $quantity): void
    {
        $this->stock += $quantity->getValue();
    }
}
