<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductId;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\SaleId;

final class SaleItem
{
    private string $id;
    private SaleId $saleId;
    private ProductId $productId;
    private string $productName;
    private string $categoryName;
    private Quantity $quantity;
    private Money $unitPrice;

    public function __construct(
        string $id,
        SaleId $saleId,
        ProductId $productId,
        string $productName,
        string $categoryName,
        Quantity $quantity,
        Money $unitPrice
    ) {
        $this->id = $id;
        $this->saleId = $saleId;
        $this->productId = $productId;
        $this->productName = $productName;
        $this->categoryName = $categoryName;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSaleId(): SaleId
    {
        return $this->saleId;
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getCategoryName(): string
    {
        return $this->categoryName;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    /**
     * Subtotal dinámico calculado en tiempo real con precisión matemática (Artículo VII).
     */
    public function getSubtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}
