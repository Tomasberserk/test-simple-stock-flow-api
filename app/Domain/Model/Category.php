<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Exception\BusinessRuleViolation;
use App\Domain\ValueObject\CategoryId;

final class Category
{
    private CategoryId $id;
    private string $name;

    public function __construct(CategoryId $id, string $name)
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new class("El nombre de la categoría no puede estar vacío") extends BusinessRuleViolation {};
        }

        $this->id = $id;
        $this->name = $trimmed;
    }

    public function getId(): CategoryId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function rename(string $newName): void
    {
        $trimmed = trim($newName);
        if ($trimmed === '') {
            throw new class("El nombre de la categoría no puede estar vacío") extends BusinessRuleViolation {};
        }
        $this->name = $trimmed;
    }
}
