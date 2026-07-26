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
                    ->label(__('admin.orders.field.order_number'))
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('customer_name')
                    ->label(__('admin.orders.field.customer'))
                    ->searchable()
                    ->description(fn (Order $r) => $r->customer_email),
                TextColumn::make('total_cents')
                    ->label(__('admin.orders.field.total'))
                    ->money(fn (Order $r) => $r->currency)
                    ->state(fn (Order $r) => $r->total_cents / 100)
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.orders.field.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => isset(Order::STATUSES[$state])
                        ? __('admin.orders.opt.status_' . $state)
                        : $state)
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'shipped' => 'info',
                        'pending' => 'warning',
                        'refunded', 'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('tracking_number')
                    ->label(__('admin.orders.field.tracking'))
                    ->placeholder('—')
                    ->copyable(),
                TextColumn::make('created_at')
                    ->label(__('admin.orders.field.placed'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.orders.field.status'))
                    ->options(fn (): array => collect(Order::STATUSES)
                        ->map(fn (string $label, string $status) => __('admin.orders.opt.status_' . $status))
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
