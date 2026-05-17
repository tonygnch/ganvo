<?php

namespace App\Filament\StoreAdmin\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Order $r) => $r->customer_email),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->money(fn (Order $r) => $r->currency)
                    ->state(fn (Order $r) => $r->total_cents / 100)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'shipped' => 'info',
                        'pending' => 'warning',
                        'refunded', 'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('tracking_number')
                    ->label('Tracking')
                    ->placeholder('—')
                    ->copyable(),
                TextColumn::make('created_at')
                    ->label('Placed')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(Order::STATUSES),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
