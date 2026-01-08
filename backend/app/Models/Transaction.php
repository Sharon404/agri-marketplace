<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'amount',
        'currency',
        'status', // initiated, held, released, refunded
        'psp_reference', // external PSP transaction ID
        'psp_provider', // e.g., stripe, paypal, local PSP
        'held_at',
        'released_at',
        'refunded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'held_at' => 'datetime',
            'released_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Check if funds are currently held in escrow.
     */
    public function isHeld(): bool
    {
        return $this->status === 'held';
    }

    /**
     * Check if funds have been released.
     */
    public function isReleased(): bool
    {
        return $this->status === 'released';
    }

    /**
     * Release funds (mark as released).
     */
    public function release(): bool
    {
        if ($this->isHeld()) {
            $this->update([
                'status' => 'released',
                'released_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    /**
     * Refund funds (mark as refunded).
     */
    public function refund(): bool
    {
        if ($this->isHeld()) {
            $this->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);
            return true;
        }
        return false;
    }
}