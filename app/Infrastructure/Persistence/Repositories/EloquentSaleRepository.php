<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Model\DateRange;
use App\Application\Ports\Outbound\SaleRepository;
use App\Domain\Model\Sale;
use App\Domain\ValueObject\SaleId;
use App\Infrastructure\Persistence\Mappers\SaleMapper;
use App\Infrastructure\Persistence\Models\SaleItemModel;
use App\Infrastructure\Persistence\Models\SaleModel;

final class EloquentSaleRepository implements SaleRepository
{
    public function save(Sale $sale): void
    {
        SaleModel::create([
            'id' => $sale->getId()->getValue(),
            'sold_at' => $sale->getSoldAt()->format('Y-m-d H:i:s.u'),
            'sold_by_username' => $sale->getSoldByUsername()->getValue(),
            'sold_by_user_id' => $sale->getSoldByUserId()->getValue(),
        ]);

        foreach ($sale->getItems() as $item) {
            SaleItemModel::create([
                'id' => $item->getId(),
                'sale_id' => $sale->getId()->getValue(),
                'product_id' => $item->getProductId()->getValue(),
                'product_name' => $item->getProductName(),
                'category_name' => $item->getCategoryName(),
                'quantity' => $item->getQuantity()->getValue(),
                'unit_price' => (float) $item->getUnitPrice()->getAmountString(),
            ]);
        }
    }

    public function findById(SaleId $id): ?Sale
    {
        $model = SaleModel::with('items')->find($id->getValue());
        return $model !== null ? SaleMapper::toDomain($model) : null;
    }

    public function findByDateRange(DateRange $range, int $page, int $perPage): array
    {
        $startStr = $range->getFrom()->format('Y-m-d 00:00:00.000000');
        $endStr = $range->getTo()->format('Y-m-d 23:59:59.999999');

        $query = SaleModel::with('items')
            ->whereBetween('sold_at', [$startStr, $endStr]);

        $total = $query->count();
        $totalPages = (int) ceil($total / $perPage);

        $models = $query->orderBy('sold_at', 'desc')
            ->forPage($page, $perPage)
            ->get();

        $items = $models->map(fn(SaleModel $m) => SaleMapper::toDomain($m))->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, $totalPages),
        ];
    }
}
