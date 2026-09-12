<?php

namespace App\Filament\StoreAdmin\Resources\RackConfigurations;

use App\Filament\StoreAdmin\Resources\RackConfigurations\Pages\ListRackConfigurations;
use App\Filament\StoreAdmin\Resources\RackConfigurations\Tables\RackConfigurationsTable;
use App\Models\RackConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every rack a customer has built and kept.
 *
 * The configurator writes one of these on Save and on Add-to-request, so this
 * is the record of what people are actually pricing. The interesting rows are
 * the ones that never reached an order: somebody costed a rack, saw the figure
 * and closed the tab, and until now that left a row nobody could see.
 *
 * Nothing here is the merchant's to write. They are customers' drawings — read
 * them, open them in the configurator, delete the stale ones.
 */
class RackConfigurationResource extends Resource
{
    protected static ?string $model = RackConfiguration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmarkSquare;

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?int $navigationSort = 40;   // after the price book (30)

    /*
     | getNavigationGroup() as a method, never the static property: a static
     | initialiser cannot call __(), which would pin this to English.
     */
    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.group.shop');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.rack_configs.nav.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.rack_configs.nav.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.rack_configs.nav.model_plural');
    }

    /** The same opt-in flag that gates the storefront page and the price book. */
    private static function enabled(): bool
    {
        return (bool) (auth()->user()?->tenant?->store?->rackConfigurator()['enabled'] ?? false);
    }

    public static function canViewAny(): bool
    {
        return static::enabled();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::enabled();
    }

    public static function canCreate(): bool
    {
        // Racks are drawn by customers in the configurator, never typed here.
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        // Editing somebody's saved drawing would change what their share link
        // shows them, behind their back.
        return false;
    }

    public static function table(Table $table): Table
    {
        return RackConfigurationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()?->tenant_id)
            // "did this one ever get ordered" is a column in the table, and
            // counting it here keeps it one query rather than one per row.
            ->withCount('orderItems');
    }

    public static function getPages(): array
    {
        return ['index' => ListRackConfigurations::route('/')];
    }

    /**
     * The public address of this storefront.
     *
     * Built from the request rather than hard-coded to :8000, the way the
     * theme-customizer's preview link is — that link is correct on a laptop
     * and wrong on the live site. A verified custom domain wins, because that
     * is the address the customer's own link uses.
     */
    public static function storefrontBase(): string
    {
        $tenant = auth()->user()?->tenant;
        $store = $tenant?->store;

        if ($store?->custom_domain && $store->custom_domain_verified_at) {
            return 'https://'.$store->custom_domain;
        }

        $request = request();
        $base = $request->getScheme().'://'.$tenant?->slug.'.'.config('ganvo.central_domain');
        $port = $request->getPort();

        return in_array((int) $port, [80, 443], true) ? $base : $base.':'.$port;
    }
}
