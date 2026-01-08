<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_listing_id',
        'buyer_request_id',
        'broker_id', // admin/agent user
        'agreed_quantity',
        'agreed_price',
        'status', // pending, negotiated, accepted, logistics_assigned, delivered, completed, cancelled
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'agreed_quantity' => 'decimal:2',
            'agreed_price' => 'decimal:2',
        ];
    }

    public function farmerListing(): BelongsTo
    {
        return $this->belongsTo(FarmerListing::class);
    }

    public function buyerRequest(): BelongsTo
    {
        return $this->belongsTo(BuyerRequest::class);
    }

    public function broker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'broker_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
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