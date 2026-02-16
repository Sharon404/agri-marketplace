<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductShipping extends Model
{
    use HasFactory;

    protected $table = 'product_shipping';

    protected $fillable = [
        'product_id',
        'shipping_type',
        'flat_shipping_fee',
        'free_shipping_minimum',
    ];

    protected function casts(): array
    {
        return [
            'flat_shipping_fee' => 'decimal:2',
            'free_shipping_minimum' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
