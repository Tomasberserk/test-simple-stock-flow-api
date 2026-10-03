<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class SaleItemModel extends Model
{
    protected $table = 'sale_item';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'sale_id',
        'product_id',
        'product_name',
        'category_name',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(SaleModel::class, 'sale_id', 'id');
    }
}
