<?php

namespace App\Filament\StoreAdmin\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl('https://placehold.co/40x40?text=%E2%80%94'),
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->searchable()
                    ->color('gray')
                    ->size('sm'),
                TextColumn::make('price_cents')
                    ->label('Price')
                    // The store's base currency is the source of truth — the
                    // legacy per-product `currency` column is ignored now.
                    ->money(fn (Product $r) => $r->tenant?->store?->currency ?? 'USD')
                    ->state(fn (Product $r) => $r->price_cents / 100)
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
