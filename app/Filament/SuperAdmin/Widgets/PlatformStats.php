<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Order;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $activeClients = Tenant::where('status', 'active')->count();
        $newThisWeek = Tenant::where('created_at', '>=', now()->subDays(7))->count();
        $totalOrders = Order::count();
        $grossVolume = Order::where('status', 'paid')->sum('total_cents');

        return [
            Stat::make('Active clients', $activeClients)
                ->description($newThisWeek . ' new in the last 7 days')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Platform orders', number_format($totalOrders))
                ->description('All-time order count')
                ->color('primary'),

            Stat::make('Gross volume', '$' . number_format($grossVolume / 100, 2))
                ->description('Sum of paid orders, all tenants')
                ->color('warning'),

            Stat::make('MRR', '—')
                ->description('Subscription billing not yet wired')
                ->color('gray'),
        ];
    }
}
