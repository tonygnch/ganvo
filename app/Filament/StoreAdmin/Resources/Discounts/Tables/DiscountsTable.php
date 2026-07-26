<?php

namespace App\Filament\StoreAdmin\Resources\Discounts\Tables;

use App\Models\Discount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.shared.field.name'))
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Discount $d) => $d->code
                        ? __('admin.discounts.help.row_code', ['code' => $d->code])
                        : __('admin.discounts.help.row_auto')),
                TextColumn::make('type')
                    ->label(__('admin.discounts.field.type'))
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        Discount::TYPE_PERCENTAGE   => __('admin.discounts.opt.type_short_percentage'),
                        Discount::TYPE_FIXED        => __('admin.discounts.opt.type_short_fixed'),
                        Discount::TYPE_FREE_SHIPPING => __('admin.discounts.opt.type_short_free_shipping'),
                        default                     => $state,
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Discount::TYPE_PERCENTAGE   => 'success',
                        Discount::TYPE_FIXED        => 'info',
                        Discount::TYPE_FREE_SHIPPING => 'warning',
                        default                     => 'gray',
                    }),
                TextColumn::make('value')
                    ->label(__('admin.discounts.field.value'))
                    ->formatStateUsing(function ($state, Discount $d) {
                        return match ($d->type) {
                            Discount::TYPE_PERCENTAGE   => $state . '%',
                            Discount::TYPE_FIXED        => \App\Services\Money::format((int) $state, $d->tenant?->store?->currency ?? 'EUR'),
                            Discount::TYPE_FREE_SHIPPING => '—',
                            default                     => $state,
                        };
                    }),
                TextColumn::make('times_used')
                    ->label(__('admin.discounts.field.times_used'))
                    ->numeric()
                    ->sortable()
                    ->description(fn (Discount $d) => $d->usage_limit
                        ? __('admin.discounts.help.row_of_limit', ['limit' => $d->usage_limit])
                        : null),
                IconColumn::make('is_auto')
                    ->label(__('admin.discounts.field.is_auto_short'))
                    ->boolean()
                    ->tooltip(__('admin.discounts.help.is_auto_tooltip')),
                IconColumn::make('is_active')
                    ->label(__('admin.shared.field.active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('admin.discounts.field.ends_short'))
                    ->dateTime('M j, Y')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('active_only')
                    ->label(__('admin.discounts.field.filter_active'))
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->default(),
                Filter::make('auto_only')
                    ->label(__('admin.discounts.field.filter_auto'))
                    ->query(fn ($query) => $query->where('is_auto', true)),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.discounts.empty.heading'))
            ->emptyStateDescription(__('admin.discounts.empty.description'));
    }
}
