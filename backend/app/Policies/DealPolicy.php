<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    /**
     * Determine if the user can view a deal
     */
    public function view(User $user, Deal $deal): bool
    {
        return $user->id === $deal->farmer_id || $user->id === $deal->buyer_id;
    }

    /**
     * Determine if the user can update deal (status, notes, etc.)
     */
    public function update(User $user, Deal $deal): bool
    {
        // Both farmer and buyer can update their respective notes
        // Farmer can accept/reject, buyer can respond
        return $user->id === $deal->farmer_id || $user->id === $deal->buyer_id;
    }

    /**
     * Determine if user can accept a deal
     * Only the farmer can accept a deal from a listing
     * Only the buyer can accept a deal from a request
     */
    public function accept(User $user, Deal $deal): bool
    {
        if ($deal->farmer_listing_id) {
            // Deal created from listing - farmer must accept
            return $user->id === $deal->farmer_id && $deal->status === 'pending';
        } else {
            // Deal created from request - buyer must accept
            return $user->id === $deal->buyer_id && $deal->status === 'pending';
        }
    }

    /**
     * Determine if user can reject a deal
     */
    public function reject(User $user, Deal $deal): bool
    {
        return ($user->id === $deal->farmer_id || $user->id === $deal->buyer_id) 
            && in_array($deal->status, ['pending', 'accepted']);
    }

    /**
     * Determine if user can complete a deal
     */
    public function complete(User $user, Deal $deal): bool
    {
        // Both parties can mark as complete, but status must be 'accepted'
        return ($user->id === $deal->farmer_id || $user->id === $deal->buyer_id) 
            && $deal->status === 'accepted';
    }

    /**
     * Determine if user can cancel a deal
     */
    public function cancel(User $user, Deal $deal): bool
    {
        // Can cancel if pending or accepted (not completed)
        return ($user->id === $deal->farmer_id || $user->id === $deal->buyer_id)
            && in_array($deal->status, ['pending', 'accepted']);
    }

    /**
     * Determine if user can mark as delivered
     */
    public function markDelivered(User $user, Deal $deal): bool
    {
        // Buyer marks as delivered to confirm receipt
        return $user->id === $deal->buyer_id && $deal->status === 'accepted';
    }
}
