<?php

namespace App\Filament\SuperAdmin\Resources\MarketingSignups;

use App\Filament\SuperAdmin\Resources\MarketingSignups\Pages\ListMarketingSignups;
use App\Filament\SuperAdmin\Resources\MarketingSignups\Tables\MarketingSignupsTable;
use App\Models\MarketingSignup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * SuperAdmin view of the coming-soon waitlist. Read-only by design — rows
 * land here via the public signup endpoint, the operator's job is to
 * review + export at launch time, not to edit individual entries.
 */
class MarketingSignupResource extends Resource
{
    protected static ?string $model = MarketingSignup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static ?string $navigationLabel = 'Waitlist';

    protected static ?string $modelLabel = 'Signup';

    protected static ?string $pluralModelLabel = 'Waitlist';

    protected static ?int $navigationSort = 90;

    public static function table(Table $table): Table
    {
        return MarketingSignupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarketingSignups::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        // Signups happen via the public endpoint, not through Filament.
        return false;
    }
}
