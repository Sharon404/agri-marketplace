<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketplaceStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $usersCount = User::query()->count();
        $ordersCount = Order::query()->count();
        $productsCount = Product::query()->count();
        $revenue = Order::query()->sum('total_amount');

        return [
            Stat::make('Users', number_format($usersCount)),
            Stat::make('Orders', number_format($ordersCount)),
            Stat::make('Products', number_format($productsCount)),
            Stat::make('Revenue', '₱' . number_format((float) $revenue, 2)),
        ];
    }
}
