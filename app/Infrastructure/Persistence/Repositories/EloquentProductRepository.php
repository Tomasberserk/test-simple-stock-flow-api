<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Exception\ConcurrencyConflict;
use App\Application\Ports\Outbound\ProductRepository;
use App\Domain\Model\Product;
use App\Domain\ValueObject\CategoryId;
use App\Domain\ValueObject\ProductId;
use App\Infrastructure\Persistence\Mappers\ProductMapper;
use App\Infrastructure\Persistence\Models\ProductModel;
use Illuminate\Support\Facades\DB;

final class EloquentProductRepository implements ProductRepository
{
    public function findById(ProductId $id): ?Product
    {
        $model = ProductModel::where('id', $id->getValue())
            ->whereNull('deleted_at')
            ->first();

        return $model !== null ? ProductMapper::toDomain($model) : null;
    }

    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $rawIds = array_map(fn(ProductId $id) => $id->getValue(), $ids);

        $models = ProductModel::whereIn('id', $rawIds)
            ->whereNull('deleted_at')
            ->get();

        return $models->map(fn(ProductModel $m) => ProductMapper::toDomain($m))->all();
    }

    public function search(?string $query, ?CategoryId $categoryId, int $page, int $perPage): array
    {
        $builder = ProductModel::whereNull('deleted_at');

        if ($query !== null && trim($query) !== '') {
            $builder->where('name', 'LIKE', '%' . trim($query) . '%');
        }

        if ($categoryId !== null) {
            $builder->where('category_id', $categoryId->getValue());
        }

        $total = $builder->count();
        $totalPages = (int) ceil($total / $perPage);

        $models = $builder->orderBy('name', 'asc')
            ->forPage($page, $perPage)
            ->get();

        $items = $models->map(fn(ProductModel $m) => ProductMapper::toDomain($m))->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, $totalPages),
        ];
    }

    public function save(Product $product): void
    {
        ProductModel::create(ProductMapper::toPersistenceArray($product, version: 1));
    }

    public function update(Product $product): void
    {
        // ADR-002: Concurrencia Optimista mediante columna física `version`
        $current = ProductModel::where('id', $product->getId()->getValue())->select('version')->first();
        $currentVersion = $current !== null ? (int) $current->version : 1;

        $affected = DB::table('product')
            ->where('id', $product->getId()->getValue())
            ->where('version', $currentVersion)
            ->update([
                'name' => $product->getName(),
                'price' => (float) $product->getPrice()->getAmountString(),
                'stock' => $product->getStock(),
                'category_id' => $product->getCategoryId()->getValue(),
                'image_key' => $product->getImageKey(),
                'version' => $currentVersion + 1,
            ]);

        if ($affected === 0) {
            throw new ConcurrencyConflict("El producto '{$product->getName()}' fue modificado concurrentemente por otra operación");
        }
    }

    public function softDelete(ProductId $id): void
    {
        ProductModel::where('id', $id->getValue())->update([
            'deleted_at' => DB::raw('NOW(6)'),
        ]);
    }
}
