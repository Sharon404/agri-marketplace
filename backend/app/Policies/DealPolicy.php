<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    /**
     * Determine if the user can view a deal
     * Users can only view deals they're part of (farmer or buyer)
     */
    public function view(User $user, Deal $deal): bool
    {
        return $user->id === $deal->farmer_id || $user->id === $deal->buyer_id;
    }

    /**
     * Determine if the user can update deal (status, notes, etc.)
     * In managed marketplace, only farmers/buyers in confirmation phase can accept/reject
     */
    public function update(User $user, Deal $deal): bool
    {
        return $user->id === $deal->farmer_id || $user->id === $deal->buyer_id;
    }

    /**
     * Determine if user can accept a deal
     * MANAGED MARKETPLACE: Users accept when admin creates deal
     * - Buyer can accept when status = pending_buyer_confirmation
     * - Farmer can accept when status = pending_farmer_confirmation
     */
    public function accept(User $user, Deal $deal): bool
    {
        // Buyer accepts in pending_buyer_confirmation status
        if ($user->id === $deal->buyer_id) {
            return $deal->status === 'pending_buyer_confirmation';
        }
        
        // Farmer accepts in pending_farmer_confirmation status
        if ($user->id === $deal->farmer_id) {
            return $deal->status === 'pending_farmer_confirmation';
        }
        
        return false;
    }

    /**
     * Determine if user can reject a deal
     * Can reject if deal is still in confirmation phase (not both_confirmed yet)
     */
    public function reject(User $user, Deal $deal): bool
    {
        $canReject = in_array($deal->status, [
            'pending_buyer_confirmation',
            'pending_farmer_confirmation',
        ]);

        return ($user->id === $deal->farmer_id || $user->id === $deal->buyer_id) && $canReject;
    }

    /**
     * Determine if user can complete a deal
     * Admin marks as completed after delivery confirmed
     */
    public function complete(User $user, Deal $deal): bool
    {
        return $user->role === 'admin' && $deal->status === 'delivered';
    }

    /**
     * Determine if user can cancel a deal
     * Admin can cancel at any point; users cannot cancel
     */
    public function cancel(User $user, Deal $deal): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if user can mark as delivered
     * Farmer marks as delivered when shipment is ready/sent
     * Buyer marks as delivered when product received
     */
    public function markDelivered(User $user, Deal $deal): bool
    {
        return ($user->id === $deal->farmer_id || $user->id === $deal->buyer_id) 
            && $deal->status === 'accepted';
    }

    /**
     * Determine if user can release escrow (payment)
     * Only admin can release escrow after delivery confirmed
     */
    public function releaseEscrow(User $user, Deal $deal): bool
    {
        return $user->role === 'admin' && $deal->status === 'delivered' 
            && $deal->payment && $deal->payment->status === 'escrowed';
    }

    /**
     * Determine if user can create a deal
     * MANAGED MARKETPLACE: Only admins can create deals
     * Farmers and buyers cannot directly create deals
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }
}
