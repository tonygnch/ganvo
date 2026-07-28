<?php

namespace App\Filament\StoreAdmin\Resources\Orders;

use App\Filament\StoreAdmin\Resources\Orders\Pages\EditOrder;
use App\Filament\StoreAdmin\Resources\Orders\Pages\ListOrders;
use App\Filament\StoreAdmin\Resources\Orders\Pages\ViewOrder;
use App\Filament\StoreAdmin\Resources\Orders\Schemas\OrderForm;
use App\Filament\StoreAdmin\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    // Navigation + model labels come from translations rather than the
    // static $navigationLabel / $modelLabel properties: a property
    // initialiser cannot call __(), and Filament's fallback derivation
    // from the class name is English-only.
    /*
     | Grouped rather than one flat list of eleven. getNavigationGroup() and not
     | the static $navigationGroup the SuperAdmin panel uses: a static property
     | initialiser cannot call __(), so that one is stuck in English forever.
     */
    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.group.sales');
    }

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('admin.orders.nav.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.orders.nav.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.orders.nav.model_plural');
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
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
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /*
     | EDITING IS ALLOWED; DELETING IS NOT.
     |
     | This used to return false, which is what actually made orders
     | untouchable — a merchant could not correct an address, adjust a
     | quantity agreed by phone, cancel an order, or price an enquiry, which
     | on the enquiry flow is the entire job.
     |
     | Deleting stays refused. An order is a financial record and the shop's
     | own history; the way to make one go away is to set its status to
     | cancelled, which keeps the trail. Removing the row would take the
     | line items and the payment references with it.
     |
     | Tenant scoping is not repeated here — getEloquentQuery() already limits
     | every lookup on this resource to the signed-in merchant's own orders,
     | so a record from another shop is never resolved in the first place.
     */
    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
