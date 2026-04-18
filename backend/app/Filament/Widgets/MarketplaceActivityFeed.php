<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class MarketplaceActivityFeed extends Widget
{
    protected static string $view = 'filament.widgets.marketplace-activity-feed';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'activities' => $this->getActivities(),
        ];
    }

    protected function getActivities(): Collection
    {
        $userActivities = User::query()
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (User $user) => [
                'type' => 'User',
                'title' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->email,
                'description' => $user->email,
                'status' => $user->status ?? 'active',
                'timestamp' => $user->created_at,
            ]);

        $orderActivities = Order::query()
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Order $order) => [
                'type' => 'Order',
                'title' => 'Order #' . $order->id,
                'description' => 'Total ₱' . number_format((float) $order->total_amount, 2),
                'status' => $order->status,
                'timestamp' => $order->created_at,
            ]);

        $productActivities = Product::query()
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Product $product) => [
                'type' => 'Product',
                'title' => $product->name,
                'description' => 'Price ₱' . number_format((float) $product->price, 2),
                'status' => $product->is_active ? 'active' : 'inactive',
                'timestamp' => $product->created_at,
            ]);

        return collect()
            ->concat($userActivities)
            ->concat($orderActivities)
            ->concat($productActivities)
            ->sortByDesc('timestamp')
            ->take(15)
            ->values();
    }
}
