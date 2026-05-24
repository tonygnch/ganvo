<?php

namespace App\Filament\StoreAdmin\Resources\Discounts;

use App\Filament\StoreAdmin\Resources\Discounts\Pages\CreateDiscount;
use App\Filament\StoreAdmin\Resources\Discounts\Pages\EditDiscount;
use App\Filament\StoreAdmin\Resources\Discounts\Pages\ListDiscounts;
use App\Filament\StoreAdmin\Resources\Discounts\Schemas\DiscountForm;
use App\Filament\StoreAdmin\Resources\Discounts\Tables\DiscountsTable;
use App\Models\Discount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Tenant-scoped discount management.
 *
 * Two flavors handled by the same model + admin UI:
 *   - Code-based: customer types the code at checkout.
 *   - Auto:       applies silently when cart meets conditions
 *                 (best one wins; see DiscountEngine).
 *
 * The form's is_auto toggle drives which flavor — auto-discounts ignore
 * the code field (we allow it for searchable naming but customers can't
 * reach it via the code input).
 */
class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $navigationLabel = 'Discounts';

    // Sit after Collections (21) — promotional plumbing belongs near
    // the merchandising tools, distinct from the day-to-day Orders nav.
    protected static ?int $navigationSort = 22;

    public static function form(Schema $schema): Schema
    {
        return DiscountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscountsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()?->tenant_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscounts::route('/'),
            'create' => CreateDiscount::route('/create'),
            'edit' => EditDiscount::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
