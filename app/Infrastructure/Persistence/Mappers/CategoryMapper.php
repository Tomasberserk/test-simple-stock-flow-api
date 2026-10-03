<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Model\Category;
use App\Domain\ValueObject\CategoryId;
use App\Infrastructure\Persistence\Models\CategoryModel;

final class CategoryMapper
{
    public static function toDomain(CategoryModel $model): Category
    {
        return new Category(
            id: CategoryId::fromString((string) $model->id),
            name: (string) $model->name
        );
    }
}
