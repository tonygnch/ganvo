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
            Stat::make(__('admin.widgets.field.active_products'), $activeProducts)
                ->description(__('admin.widgets.help.active_products'))
                ->color('primary'),

            Stat::make(__('admin.widgets.field.total_orders'), number_format($totalOrders))
                ->description(__('admin.widgets.help.orders_last_7', ['count' => $ordersLast7]))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make(__('admin.widgets.field.revenue'), '$' . number_format($paidRevenue / 100, 2))
                ->description(__('admin.widgets.help.revenue_paid_only'))
                ->color('warning'),
        ];
    }
}
