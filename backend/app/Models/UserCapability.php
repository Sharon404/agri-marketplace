<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCapability extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'can_buy',
        'can_sell',
        'buy_requested_at',
        'sell_requested_at',
        'buy_approved_at',
        'sell_approved_at',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'can_buy' => 'boolean',
            'can_sell' => 'boolean',
            'buy_requested_at' => 'datetime',
            'sell_requested_at' => 'datetime',
            'buy_approved_at' => 'datetime',
            'sell_approved_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this capability record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user can buy (capability granted and active).
     */
    public function canBuy(): bool
    {
        return $this->can_buy && $this->status === 'active';
    }

    /**
     * Check if user can sell (capability granted and active).
     */
    public function canSell(): bool
    {
        return $this->can_sell && $this->status === 'active';
    }

    /**
     * Check if buy capability is pending approval.
     */
    public function isBuyPending(): bool
    {
        return $this->buy_requested_at !== null && $this->buy_approved_at === null;
    }

    /**
     * Check if sell capability is pending approval.
     */
    public function isSellPending(): bool
    {
        return $this->sell_requested_at !== null && $this->sell_approved_at === null;
    }

    /**
     * Request buy capability.
     */
    public function requestBuyCapability(): void
    {
        if ($this->buy_requested_at === null) {
            $this->buy_requested_at = now();
            $this->save();
        }
    }

    /**
     * Request sell capability.
     */
    public function requestSellCapability(): void
    {
        if ($this->sell_requested_at === null) {
            $this->sell_requested_at = now();
            $this->save();
        }
    }

    /**
     * Approve buy capability.
     */
    public function approveBuyCapability(): void
    {
        $this->can_buy = true;
        $this->buy_approved_at = now();
        $this->save();
    }

    /**
     * Approve sell capability.
     */
    public function approveSellCapability(): void
    {
        $this->can_sell = true;
        $this->sell_approved_at = now();
        $this->save();
    }

    /**
     * Revoke buy capability.
     */
    public function revokeBuyCapability(): void
    {
        $this->can_buy = false;
        $this->save();
    }

    /**
     * Revoke sell capability.
     */
    public function revokeSellCapability(): void
    {
        $this->can_sell = false;
        $this->save();
    }

    /**
     * Suspend user capabilities.
     */
    public function suspend(): void
    {
        $this->status = 'suspended';
        $this->save();
    }

    /**
     * Activate user capabilities.
     */
    public function activate(): void
    {
        $this->status = 'active';
        $this->save();
    }

    /**
     * Check if capabilities are suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if user has any approved capabilities.
     */
    public function hasAnyCapability(): bool
    {
        return ($this->can_buy || $this->can_sell) && $this->status === 'active';
    }
}
