<?php

namespace App\Filament\StoreAdmin\Widgets;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class RecentOrders extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    // The heading has to come from getTableHeading() rather than the static
    // $heading property: property initialisers can't call __(), and TableWidget
    // applies this method's return value to the table after table() has run.
    protected function getTableHeading(): string | Htmlable | null
    {
        return __('admin.widgets.section.recent_orders');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Order::query()
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->latest()
                    ->limit(10)
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('order_number')
                    ->label(__('admin.widgets.field.order_number'))
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('customer_email')
                    ->label(__('admin.widgets.field.customer')),
                TextColumn::make('total_cents')
                    ->label(__('admin.widgets.field.total'))
                    ->money(fn (Order $r) => $r->currency)
                    ->state(fn (Order $r) => $r->total_cents / 100),
                TextColumn::make('status')
                    ->label(__('admin.widgets.field.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'refunded', 'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label(__('admin.shared.field.created_at'))
                    ->since(),
            ]);
    }
}
