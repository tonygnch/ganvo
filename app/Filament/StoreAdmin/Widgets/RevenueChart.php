<?php

namespace App\Filament\StoreAdmin\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenue, last 14 days';

    protected function getData(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $start = Carbon::today()->subDays(13);

        $rows = Order::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE(created_at) as day, SUM(total_cents) as cents")
            ->groupBy('day')
            ->pluck('cents', 'day');

        $labels = [];
        $data = [];
        for ($i = 0; $i < 14; $i++) {
            $day = $start->copy()->addDays($i);
            $labels[] = $day->format('M j');
            $data[] = round(($rows[$day->toDateString()] ?? 0) / 100, 2);
        }

        // Label is the store's base currency so the chart legend stays honest
        // for non-EUR merchants. Falls back to EUR if the user/store isn't
        // resolvable (shouldn't happen in normal panel use).
        $currency = strtoupper(auth()->user()?->tenant?->store?->currency ?? 'EUR');

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (' . $currency . ')',
                    'data' => $data,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
