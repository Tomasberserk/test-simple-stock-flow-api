<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Exception\EmptySaleException;
use App\Domain\Exception\RepeatedProductException;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\SaleId;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;
use DateTimeImmutable;

final class Sale
{
    private SaleId $id;
    private DateTimeImmutable $soldAt;
    private Username $soldByUsername;
    private UserId $soldByUserId;
    /** @var SaleItem[] */
    private array $items = [];

    /**
     * @param SaleItem[] $items
     */
    public function __construct(
        SaleId $id,
        DateTimeImmutable $soldAt,
        Username $soldByUsername,
        UserId $soldByUserId,
        array $items = []
    ) {
        $this->id = $id;
        $this->soldAt = $soldAt;
        $this->soldByUsername = $soldByUsername;
        $this->soldByUserId = $soldByUserId;

        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    public function getId(): SaleId
    {
        return $this->id;
    }

    public function getSoldAt(): DateTimeImmutable
    {
        return $this->soldAt;
    }

    public function getSoldByUsername(): Username
    {
        return $this->soldByUsername;
    }

    public function getSoldByUserId(): UserId
    {
        return $this->soldByUserId;
    }

    /**
     * @return SaleItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(SaleItem $item): void
    {
        // CA-04.4: Dado el mismo producto repetido en dos líneas, se rechaza
        foreach ($this->items as $existingItem) {
            if ($existingItem->getProductId()->equals($item->getProductId())) {
                throw new RepeatedProductException("El producto '{$item->getProductName()}' ya fue agregado a esta venta");
            }
        }

        $this->items[] = $item;
    }

    public function ensureConfirmable(): void
    {
        if (count($this->items) === 0) {
            throw new EmptySaleException("Una venta debe contener al menos un producto");
        }
    }

    /**
     * Calcula el total sumando los subtotales de cada línea (Artículo VII).
     * NUNCA se almacena en base de datos.
     */
    public function getTotal(): Money
    {
        $total = Money::zero();
        foreach ($this->items as $item) {
            $total = $total->add($item->getSubtotal());
        }
        return $total;
    }
}
