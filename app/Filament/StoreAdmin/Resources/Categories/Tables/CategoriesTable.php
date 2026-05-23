<?php

namespace App\Filament\StoreAdmin\Resources\Categories\Tables;

use App\Models\Category;
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

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Root categories first then alphabetical — the natural
            // browsing order. Filament's reorder UI could replace this
            // later if the operator wants explicit drag-to-sort.
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->size(40),
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Category $r) => '/categories/' . $r->slug),
                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('— root —')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->badge()
                    ->color('info'),
                TextColumn::make('sort_order')
                    ->label('Sort')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('only_roots')
                    ->label('Top-level only')
                    ->query(fn ($query) => $query->whereNull('parent_id')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (Category $record) {
                        // Prevent deleting a category that still has children
                        // — the FK is restrictOnDelete, so the DB would
                        // throw a cryptic error. Surface a friendly one.
                        if ($record->children()->exists()) {
                            throw new \RuntimeException(
                                "Can't delete a category that has sub-categories. Move or delete the children first."
                            );
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No categories yet')
            ->emptyStateDescription('Add your first category to organize your catalog.');
    }
}
