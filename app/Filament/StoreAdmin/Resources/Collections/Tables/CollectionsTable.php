<?php

namespace App\Filament\StoreAdmin\Resources\Collections\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('banner_path')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->size(40),
                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($r) => '/collections/' . $r->slug),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->badge()
                    ->color('info'),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable()
                    ->tooltip('Shown as a strip on the storefront homepage'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Sort')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('featured_only')
                    ->label('Featured only')
                    ->query(fn ($query) => $query->where('is_featured', true)),
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
            ->emptyStateHeading('No collections yet')
            ->emptyStateDescription('Create your first curated grouping — Summer Sale, Staff Picks, New Arrivals.');
    }
}
