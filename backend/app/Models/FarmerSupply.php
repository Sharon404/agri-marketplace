<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmerSupply extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'product_id',
        'quantity_available',
        'unit',
        'price_per_unit',
        'available_from',
        'available_until',
        'description',
        'delivery_terms',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quantity_available' => 'decimal:2',
            'price_per_unit' => 'decimal:2',
            'available_from' => 'date',
            'available_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'farmer_supply_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('quantity_available', '>', 0)
            ->where('available_from', '<=', now()->toDateString())
            ->where('available_until', '>=', now()->toDateString());
    }

    public function scopeByFarmer($query, $farmerId)
    {
        return $query->where('farmer_id', $farmerId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Mutators
    public function getRemainingQuantityAttribute()
    {
        // Calculate remaining quantity after deals
        $dealtQuantity = $this->deals()
            ->whereIn('status', ['accepted', 'in_transit', 'delivered', 'completed'])
            ->sum('quantity');
        
        return $this->quantity_available - $dealtQuantity;
    }
}
