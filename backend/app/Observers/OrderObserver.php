<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\SellerPayout;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if ($order->wasChanged('status') && $order->status === 'delivered') {
            SellerPayout::where('order_id', $order->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'released',
                    'released_at' => now(),
                ]);
        }
    }
}
