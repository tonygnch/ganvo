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
                    ->label('')   // table columns have no hiddenLabel(); '' is an empty header here
                    ->disk('public')
                    ->square()
                    ->size(40),
                TextColumn::make('title')
                    // Explicit label — Filament's fallback derivation from
                    // the column name is English-only.
                    ->label(__('admin.collections.field.title'))
                    ->searchable()
                    ->weight('bold')
                    // Null-guard: Filament occasionally calls the
                    // description closure with no record (column-header
                    // resolution paths). $r? lets that fall through
                    // cleanly instead of throwing.
                    ->description(fn ($r) => $r?->slug ? '/collections/' . $r->slug : null),
                TextColumn::make('products_count')
                    ->label(__('admin.collections.field.products_count'))
                    ->counts('products')
                    ->badge()
                    ->color('info'),
                IconColumn::make('is_featured')
                    ->label(__('admin.collections.field.featured'))
                    ->boolean()
                    ->sortable()
                    ->tooltip(__('admin.collections.help.featured')),
                IconColumn::make('is_active')
                    ->label(__('admin.shared.field.active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label(__('admin.shared.field.sort_order'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('featured_only')
                    ->label(__('admin.collections.field.featured_only'))
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
            ->emptyStateHeading(__('admin.collections.empty.heading'))
            ->emptyStateDescription(__('admin.collections.empty.description'));
    }
}
