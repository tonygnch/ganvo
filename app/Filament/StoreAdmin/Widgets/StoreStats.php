<?php

namespace App\Filament\StoreAdmin\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $activeProducts = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();

        $totalOrders = Order::where('tenant_id', $tenantId)->count();

        $paidRevenue = Order::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->sum('total_cents');

        $ordersLast7 = Order::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            Stat::make('Active products', $activeProducts)
                ->description('Visible in your storefront')
                ->color('primary'),

            Stat::make('Total orders', number_format($totalOrders))
                ->description($ordersLast7 . ' in the last 7 days')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Revenue', '$' . number_format($paidRevenue / 100, 2))
                ->description('Paid orders only')
                ->color('warning'),
        ];
    }
}
