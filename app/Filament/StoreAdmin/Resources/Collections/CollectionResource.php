<?php

namespace App\Filament\StoreAdmin\Resources\Collections;

use App\Filament\StoreAdmin\Resources\Collections\Pages\CreateCollection;
use App\Filament\StoreAdmin\Resources\Collections\Pages\EditCollection;
use App\Filament\StoreAdmin\Resources\Collections\Pages\ListCollections;
use App\Filament\StoreAdmin\Resources\Collections\Schemas\CollectionForm;
use App\Filament\StoreAdmin\Resources\Collections\Tables\CollectionsTable;
use App\Models\Collection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Tenant-scoped curated product groupings (a.k.a. "featured collections").
 * Unlike Categories (taxonomic, hierarchical, every product slots into a
 * category) Collections are a pure merchandising tool — operator hand-
 * picks the products + the order, and toggles `is_featured` to surface
 * the collection as a strip on the storefront homepage.
 */
class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Collections';

    // Slot between Categories (20) and below — collections are a
    // merchandising layer the operator reaches for less often.
    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return CollectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollectionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()?->tenant_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'create' => CreateCollection::route('/create'),
            'edit' => EditCollection::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
