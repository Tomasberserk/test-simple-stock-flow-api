<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Ports\Outbound\CategoryRepository;
use App\Domain\Model\Category;
use App\Domain\ValueObject\CategoryId;
use App\Infrastructure\Persistence\Mappers\CategoryMapper;
use App\Infrastructure\Persistence\Models\CategoryModel;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function findAll(): array
    {
        $models = CategoryModel::orderBy('name', 'asc')->get();
        return $models->map(fn(CategoryModel $m) => CategoryMapper::toDomain($m))->all();
    }

    public function findById(CategoryId $id): ?Category
    {
        $model = CategoryModel::find($id->getValue());
        return $model !== null ? CategoryMapper::toDomain($model) : null;
    }
}
