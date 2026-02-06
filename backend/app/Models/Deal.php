<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'buyer_id',
        'product_id',
        'farmer_listing_id',
        'buyer_request_id',
        'quantity',
        'agreed_price',
        'total_amount',
        'status',
        'payment_status',
        'delivery_location',
        'delivery_date',
        'delivery_notes',
        'farmer_notes',
        'buyer_notes',
        'accepted_at',
        'delivered_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'agreed_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'delivery_date' => 'date',
            'accepted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function farmerListing(): BelongsTo
    {
        return $this->belongsTo(FarmerListing::class);
    }

    public function buyerRequest(): BelongsTo
    {
        return $this->belongsTo(BuyerRequest::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['accepted', 'in_transit', 'delivered']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Helper methods
    public function canBeAcceptedBy(User $user)
    {
        return $this->status === 'pending' && 
               ($this->farmer_id === $user->id || $this->buyer_id === $user->id);
    }

    public function canBeUpdatedBy(User $user)
    {
        return $this->farmer_id === $user->id || $this->buyer_id === $user->id;
    }

    public function logisticsJobs()
    {
        return $this->hasMany(LogisticsJob::class);
    }

    public function disputes()
    {
        return $this->hasMany(Dispute::class);
    }

    /**
     * Check if deal can transition to a new status.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $transitions = [
            'pending' => ['negotiated', 'cancelled'],
            'negotiated' => ['accepted', 'cancelled'],
            'accepted' => ['logistics_assigned', 'cancelled'],
            'logistics_assigned' => ['delivered', 'cancelled'],
            'delivered' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ];

        return in_array($newStatus, $transitions[$this->status] ?? []);
    }

    /**
     * Transition deal to new status.
     */
    public function transitionTo(string $newStatus): bool
    {
        if ($this->canTransitionTo($newStatus)) {
            $this->update(['status' => $newStatus]);
            return true;
        }
        return false;
    }
}