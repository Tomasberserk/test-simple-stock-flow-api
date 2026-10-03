<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final class CreateProductDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly int $stock,
        public readonly string $categoryId,
        public readonly ?string $imageKey = null
    ) {}
}
