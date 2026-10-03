<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class ProductModel extends Model
{
    protected $table = 'product';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'price',
        'stock',
        'category_id',
        'image_key',
        'deleted_at',
        'version',
    ];

    protected $casts = [
        'price' => 'float',
        'stock' => 'integer',
        'version' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category_id', 'id');
    }
}
