<?php

namespace App\Filament\StoreAdmin\Resources\Categories;

use App\Filament\StoreAdmin\Resources\Categories\Pages\CreateCategory;
use App\Filament\StoreAdmin\Resources\Categories\Pages\EditCategory;
use App\Filament\StoreAdmin\Resources\Categories\Pages\ListCategories;
use App\Filament\StoreAdmin\Resources\Categories\Schemas\CategoryForm;
use App\Filament\StoreAdmin\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Tenant-scoped category management. Categories are how merchants
 * organize their catalog — a product can belong to multiple categories,
 * the storefront nav lists them, /categories/{slug} pages browse them.
 *
 * Like ProductResource, scoped by the authenticated user's tenant_id
 * so merchants only see their own categories.
 */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;


    // Nav + model labels are set explicitly instead of as static
    // properties: Filament would otherwise derive them from the model
    // class name ("Category" / "Categories") in English only, and the
    // plural is pluralized with English rules. They feed the sidebar,
    // the page headings, the breadcrumbs and every "Create category" /
    // "Delete category" string Filament builds for us.
    /*
     | Grouped rather than one flat list of eleven. getNavigationGroup() and not
     | the static $navigationGroup the SuperAdmin panel uses: a static property
     | initialiser cannot call __(), so that one is stuck in English forever.
     */
    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.group.catalog');
    }

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('admin.categories.nav.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.categories.nav.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.categories.nav.model_plural');
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()?->tenant_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
