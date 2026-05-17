<?php

namespace App\Filament\StoreAdmin\Widgets;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOrders extends TableWidget
{
    protected static ?string $heading = 'Recent orders';

    protected int|string|array $columnSpan = 'full';

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
                    ->label('Order')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('customer_email')
                    ->label('Customer'),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->money(fn (Order $r) => $r->currency)
                    ->state(fn (Order $r) => $r->total_cents / 100),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'refunded', 'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->since(),
            ]);
    }
}
