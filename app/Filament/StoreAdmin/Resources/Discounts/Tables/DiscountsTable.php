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
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Discount $d) => $d->code ? 'Code: ' . $d->code : 'Auto-applied'),
                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        Discount::TYPE_PERCENTAGE   => 'Percent off',
                        Discount::TYPE_FIXED        => 'Amount off',
                        Discount::TYPE_FREE_SHIPPING => 'Free shipping',
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
                    ->label('Value')
                    ->formatStateUsing(function ($state, Discount $d) {
                        return match ($d->type) {
                            Discount::TYPE_PERCENTAGE   => $state . '%',
                            Discount::TYPE_FIXED        => \App\Services\Money::format((int) $state, $d->tenant?->store?->currency ?? 'EUR'),
                            Discount::TYPE_FREE_SHIPPING => '—',
                            default                     => $state,
                        };
                    }),
                TextColumn::make('times_used')
                    ->label('Used')
                    ->numeric()
                    ->sortable()
                    ->description(fn (Discount $d) => $d->usage_limit ? 'of ' . $d->usage_limit : null),
                IconColumn::make('is_auto')
                    ->label('Auto')
                    ->boolean()
                    ->tooltip('Applied automatically without a code'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime('M j, Y')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('active_only')
                    ->label('Active only')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->default(),
                Filter::make('auto_only')
                    ->label('Auto-discounts only')
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
            ->emptyStateHeading('No discounts yet')
            ->emptyStateDescription('Create a discount code (e.g. SUMMER10) or an auto-applied promo for big carts.');
    }
}
