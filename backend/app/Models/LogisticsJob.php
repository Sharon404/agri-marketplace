<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'assigned_agent_id', // logistics agent user
        'pickup_location',
        'delivery_location',
        'scheduled_pickup_at',
        'scheduled_delivery_at',
        'actual_pickup_at',
        'actual_delivery_at',
        'status', // assigned, in_transit, delivered, failed
        'vehicle_details',
        'tracking_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_pickup_at' => 'datetime',
            'scheduled_delivery_at' => 'datetime',
            'actual_pickup_at' => 'datetime',
            'actual_delivery_at' => 'datetime',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function deliveryVerifications()
    {
        return $this->hasMany(DeliveryVerification::class);
    }

    /**
     * Check if job is completed.
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['delivered', 'failed']);
    }

    /**
     * Mark as picked up.
     */
    public function markPickedUp(): bool
    {
        if ($this->status === 'assigned') {
            $this->update([
                'status' => 'in_transit',
                'actual_pickup_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    /**
     * Mark as delivered.
     */
    public function markDelivered(): bool
    {
        if ($this->status === 'in_transit') {
            $this->update([
                'status' => 'delivered',
                'actual_delivery_at' => now(),
            ]);
            return true;
        }
        return false;
    }
}