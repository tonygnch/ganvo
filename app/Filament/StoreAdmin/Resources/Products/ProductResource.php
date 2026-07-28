<?php

namespace App\Filament\StoreAdmin\Resources\Products;

use App\Filament\StoreAdmin\Resources\Products\Pages\CreateProduct;
use App\Filament\StoreAdmin\Resources\Products\Pages\EditProduct;
use App\Filament\StoreAdmin\Resources\Products\Pages\ListProducts;
use App\Filament\StoreAdmin\Resources\Products\Schemas\ProductForm;
use App\Filament\StoreAdmin\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    // Resolved in methods rather than static properties: a property initialiser
    // has to be a constant expression, and __() runs before the locale is set
    // there anyway.
    /*
     | Grouped rather than one flat list of eleven. getNavigationGroup() and not
     | the static $navigationGroup the SuperAdmin panel uses: a static property
     | initialiser cannot call __(), so that one is stuck in English forever.
     */
    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.group.catalog');
    }

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('admin.products.nav.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.products.nav.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.products.nav.model_plural');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()?->tenant_id);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
