<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'buyer_id',
        'amount',
        'status',
        'payment_method',
        'transaction_reference',
        'escrow_released_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'escrow_released_at' => 'datetime',
        ];
    }

    // Status values: pending, escrowed, released, refunded, failed

    // Relationships
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    // Scopes
    public function scopeEscrowed($query)
    {
        return $query->where('status', 'escrowed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReleased($query)
    {
        return $query->where('status', 'released');
    }

    public function scopeByDeal($query, $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeByBuyer($query, $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    // Methods
    public function holdInEscrow(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update(['status' => 'escrowed']);
        return true;
    }

    public function releaseEscrow(): bool
    {
        if ($this->status !== 'escrowed') {
            return false;
        }

        $this->update([
            'status' => 'released',
            'escrow_released_at' => now(),
        ]);

        return true;
    }

    public function refund(): bool
    {
        if (!in_array($this->status, ['pending', 'escrowed'])) {
            return false;
        }

        $this->update(['status' => 'refunded']);
        return true;
    }
}
