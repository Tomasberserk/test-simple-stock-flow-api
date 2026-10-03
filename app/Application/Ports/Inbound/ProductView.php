<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

final class ProductView
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $price,
        public readonly int $stock,
        public readonly string $categoryId,
        public readonly ?string $imageKey = null
    ) {}
}
