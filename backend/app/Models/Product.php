<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'category_id',
        'name',
        'description',
        'price',
        'minimum_order_quantity',
        'stock_quantity',
        'weight_per_unit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'minimum_order_quantity' => 'integer',
            'stock_quantity' => 'integer',
            'weight_per_unit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function sellerProfile()
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function shipping()
    {
        return $this->hasOne(ProductShipping::class);
    }
}
