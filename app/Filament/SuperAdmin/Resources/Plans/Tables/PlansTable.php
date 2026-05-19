<?php

namespace App\Filament\SuperAdmin\Resources\Plans\Tables;

use App\Models\Plan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->color('gray')
                    ->size('sm'),
                TextColumn::make('price_monthly_cents')
                    ->label('Monthly')
                    ->money(fn (Plan $r) => $r->currency)
                    ->state(fn (Plan $r) => $r->price_monthly_cents / 100)
                    ->sortable(),
                TextColumn::make('price_yearly_cents')
                    ->label('Yearly')
                    ->money(fn (Plan $r) => $r->currency)
                    ->state(fn (Plan $r) => $r->price_yearly_cents / 100)
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('Curr.')
                    ->size('sm'),
                IconColumn::make('is_popular')
                    ->label('Popular')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('discount_percent')
                    ->label('Promo')
                    ->state(fn (Plan $r) => $r->hasActiveDiscount() ? $r->discount_percent . '% off' : null)
                    ->placeholder('—')
                    ->color('warning'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
