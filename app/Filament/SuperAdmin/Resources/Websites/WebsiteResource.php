<?php

namespace App\Filament\SuperAdmin\Resources\Websites;

use App\Filament\SuperAdmin\Resources\Websites\Pages\CreateWebsite;
use App\Filament\SuperAdmin\Resources\Websites\Pages\EditWebsite;
use App\Filament\SuperAdmin\Resources\Websites\Pages\ListWebsites;
use App\Models\Tenant;
use App\Models\Website;
use App\Services\RoleMatrix;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Custom website clients — the hub side of the platform. Each record is a
 * hand-built client site hosted OUTSIDE Ganvo; the platform manages the
 * relationship: client (tenant), live URL, repo, stack notes, billing via
 * the tenant, and a lightweight up/down check.
 */
class WebsiteResource extends Resource
{
    protected static ?string $model = Website::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|\UnitEnum|null $navigationGroup = 'Clients';

    protected static ?string $navigationLabel = 'Websites';

    protected static ?string $recordTitleAttribute = 'url';

    public static function canViewAny(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_TENANTS);
    }

    public static function canCreate(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_TENANTS_MANAGE);
    }

    public static function canEdit($record): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_TENANTS_MANAGE);
    }

    public static function canDelete($record): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_TENANTS_MANAGE);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Client')
                ->columns(2)
                ->schema([
                    TextInput::make('client_name')
                        ->label('Client / site name')
                        ->required()
                        ->dehydrated(false)
                        ->helperText('Creates (or renames) the client tenant behind this website.'),
                    TextInput::make('client_email')
                        ->label('Contact email')
                        ->email()
                        ->dehydrated(false),
                    Select::make('client_status')
                        ->label('Status')
                        ->options(Tenant::STATUSES)
                        ->default(Tenant::STATUS_ACTIVE)
                        ->dehydrated(false),
                ]),
            Section::make('Site')
                ->columns(2)
                ->schema([
                    TextInput::make('url')
                        ->label('Live URL')
                        ->url()
                        ->placeholder('https://…')
                        ->columnSpanFull(),
                    TextInput::make('repo_url')
                        ->label('Repository')
                        ->placeholder('https://github.com/…'),
                    TextInput::make('stack')
                        ->placeholder('e.g. Laravel + Blade'),
                    Textarea::make('notes')
                        ->rows(3)
                        ->helperText('Hosting, deploy quirks, credential pointers — never secrets themselves.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')->label('Client')->searchable()->sortable(),
                TextColumn::make('url')->label('Live URL')->url(fn (Website $r) => $r->url, true)->placeholder('—'),
                TextColumn::make('stack')->placeholder('—')->toggleable(),
                TextColumn::make('tenant.status')->label('Status')->badge()
                    ->color(fn (string $state) => match ($state) {
                        Tenant::STATUS_ACTIVE => 'success',
                        Tenant::STATUS_SUSPENDED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('last_status')->label('Site check')->badge()->placeholder('never checked')
                    ->color(fn (?string $state) => match ($state) {
                        'up' => 'success',
                        'down' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('last_checked_at')->label('Checked')->since()->placeholder('—')->toggleable(),
            ])
            ->recordActions([
                Action::make('check')
                    ->label('Check now')
                    ->icon(Heroicon::OutlinedSignal)
                    ->action(function (Website $record): void {
                        $status = $record->checkNow();
                        Notification::make()
                            ->title("Site is {$status}")
                            ->{$status === 'up' ? 'success' : 'warning'}()
                            ->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebsites::route('/'),
            'create' => CreateWebsite::route('/create'),
            'edit' => EditWebsite::route('/{record}/edit'),
        ];
    }
}
