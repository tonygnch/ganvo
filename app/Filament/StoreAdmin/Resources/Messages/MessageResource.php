<?php

namespace App\Filament\StoreAdmin\Resources\Messages;

use App\Filament\StoreAdmin\Resources\Messages\Pages\ListMessages;
use App\Filament\StoreAdmin\Resources\Messages\Pages\ViewMessage;
use App\Filament\StoreAdmin\Resources\Messages\Tables\MessagesTable;
use App\Models\StoreMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The merchant's inbox for enquiries sent from the storefront contact page.
 * Visitors write these; the merchant only ever triages them — open (which
 * marks them read), reply from their own mail client, archive.
 *
 * Scoped by the authenticated user's tenant_id like every other StoreAdmin
 * resource, so a merchant can never reach another store's enquiries.
 */
class MessageResource extends Resource
{
    protected static ?string $model = StoreMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $navigationLabel = 'Messages';

    protected static ?string $modelLabel = 'Message';

    protected static ?string $pluralModelLabel = 'Messages';

    // After the catalog tools (Categories 20 → Discounts 22) but well ahead
    // of Billing (90) — enquiries are day-to-day work, like orders.
    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    /** Status → badge colour: a new enquiry shouts, an archived one recedes. */
    public const STATUS_COLORS = [
        StoreMessage::STATUS_NEW => 'warning',
        StoreMessage::STATUS_READ => 'info',
        StoreMessage::STATUS_REPLIED => 'success',
        StoreMessage::STATUS_ARCHIVED => 'gray',
    ];

    public static function table(Table $table): Table
    {
        return MessagesTable::configure($table);
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
            'index' => ListMessages::route('/'),
            'view' => ViewMessage::route('/{record}'),
        ];
    }

    /**
     * Untouched enquiries, surfaced in the sidebar so they don't rot. Built
     * on getEloquentQuery() rather than a fresh model query so the tenant
     * scope is impossible to forget here.
     */
    public static function getNavigationBadge(): ?string
    {
        $new = static::getEloquentQuery()
            ->where('status', StoreMessage::STATUS_NEW)
            ->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        // Enquiries arrive via the storefront contact form, never through Filament.
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        // Nothing here is the merchant's text to change — status moves happen
        // through the explicit triage actions instead.
        return false;
    }

    /** Status labels for the filter + badges. */
    public static function statusOptions(): array
    {
        return array_combine(StoreMessage::STATUSES, array_map('ucfirst', StoreMessage::STATUSES));
    }

    /**
     * Show the merchant the same subject wording the visitor picked on the
     * storefront, so both sides of the conversation use one vocabulary. A
     * null subject means the visitor left the optional select alone, which
     * the form treats as a general enquiry.
     */
    public static function subjectLabel(?string $subject): string
    {
        $subject = filled($subject) ? $subject : 'general';

        return in_array($subject, StoreMessage::SUBJECTS, true)
            ? __('site.storefront.contact.subject_' . $subject)
            : $subject;
    }
}
