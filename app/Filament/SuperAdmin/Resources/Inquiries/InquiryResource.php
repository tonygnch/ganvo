<?php

namespace App\Filament\SuperAdmin\Resources\Inquiries;

use App\Filament\SuperAdmin\Resources\Inquiries\Pages\EditInquiry;
use App\Filament\SuperAdmin\Resources\Inquiries\Pages\ListInquiries;
use App\Filament\SuperAdmin\Resources\Inquiries\Tables\InquiriesTable;
use App\Models\ProjectInquiry;
use App\Services\RoleMatrix;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * SuperAdmin inbox for "Start a project" inquiries from the marketing
 * homepage — the studio's lead pipeline. Rows arrive via the public form;
 * the operator reads the message and moves each lead through its status.
 */
class InquiryResource extends Resource
{
    protected static ?string $model = ProjectInquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $navigationLabel = 'Inquiries';

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = 'Inquiry';

    protected static ?string $pluralModelLabel = 'Inquiries';

    // Above the waitlist (90) — inbound project leads matter more day to day.
    protected static ?int $navigationSort = 85;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_WAITLIST);
    }

    public static function canEdit($record): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_WAITLIST_MANAGE);
    }

    public static function canDelete($record): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_WAITLIST_MANAGE);
    }

    public static function canCreate(): bool
    {
        // Inquiries arrive via the public form, never through Filament.
        return false;
    }

    /**
     * The edit page doubles as the read view: the lead's fields are shown
     * read-only, and the one thing the operator changes — status — is the
     * only editable control.
     */
    public static function form(Schema $schema): Schema
    {
        $statusOptions = array_combine(ProjectInquiry::STATUSES, array_map('ucfirst', ProjectInquiry::STATUSES));

        return $schema->components([
            Section::make('Lead')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->disabled()->dehydrated(false),
                    TextInput::make('email')->disabled()->dehydrated(false),
                    TextInput::make('company')->placeholder('—')->disabled()->dehydrated(false),
                    Select::make('status')
                        ->options($statusOptions)
                        ->required()
                        ->native(false),
                    TextInput::make('project_type')->label('Project type')->placeholder('—')->disabled()->dehydrated(false),
                    TextInput::make('budget')->placeholder('—')->disabled()->dehydrated(false),
                ]),
            Section::make('Message')
                ->schema([
                    Textarea::make('message')->hiddenLabel()->rows(8)->disabled()->dehydrated(false)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return InquiriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInquiries::route('/'),
            'edit' => EditInquiry::route('/{record}/edit'),
        ];
    }
}
