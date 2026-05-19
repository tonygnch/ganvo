<?php

namespace App\Filament\SuperAdmin\Resources\Plans\Schemas;

use App\Services\Money;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan basics')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(60)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => $get('slug')
                                ? null
                                : $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(60)
                            ->unique(table: 'plans', column: 'slug', ignoreRecord: true)
                            ->helperText('URL-safe identifier; used by tenants.subscription_plan. Avoid renaming after launch.'),
                        TextInput::make('tagline')
                            ->maxLength(160)
                            ->columnSpanFull(),
                        TagsInput::make('features')
                            ->placeholder('Add a feature bullet…')
                            ->helperText('One bullet per chip. Order is preserved on the wizard card.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Pricing')
                    ->description('Both monthly and yearly prices are stored. Yearly is typically 10× monthly (two months free), but it\'s your call.')
                    ->columns(3)
                    ->schema([
                        Select::make('currency')
                            ->label('Currency')
                            ->options(Money::options())
                            ->required()
                            ->default('USD'),
                        TextInput::make('price_monthly_cents')
                            ->label('Monthly price')
                            ->required()
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0)
                            ->prefix(fn ($get) => Money::symbol($get('currency') ?: 'USD'))
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format(((int) $state) / 100, 2, '.', '') : null)
                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                        TextInput::make('price_yearly_cents')
                            ->label('Yearly price')
                            ->required()
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0)
                            ->prefix(fn ($get) => Money::symbol($get('currency') ?: 'USD'))
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format(((int) $state) / 100, 2, '.', '') : null)
                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                    ]),

                Section::make('Visibility & order')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('When off, the plan is hidden from the wizard. Existing tenants on this plan keep their slug.')
                            ->default(true),
                        Toggle::make('is_popular')
                            ->label('Mark as popular')
                            ->helperText('Highlights the card with a "Most popular" badge on the wizard.'),
                        TextInput::make('sort_order')
                            ->label('Sort order')
                            ->numeric()
                            ->minValue(0)
                            ->default(10)
                            ->helperText('Lower numbers come first.'),
                    ]),

                Section::make('Translations')
                    ->description('Optional per-locale overrides for name, tagline, and features. The English values above are used as the fallback when an override is blank.')
                    ->schema([
                        Repeater::make('translations')
                            ->label('')
                            ->schema([
                                Select::make('locale')
                                    ->label('Locale')
                                    ->options([
                                        // Mirrors SetLocale::SUPPORTED minus English (the canonical column).
                                        'bg' => 'Български (bg)',
                                    ])
                                    ->required(),
                                TextInput::make('name')
                                    ->label('Name')
                                    ->maxLength(60),
                                TextInput::make('tagline')
                                    ->label('Tagline')
                                    ->maxLength(160),
                                TagsInput::make('features')
                                    ->label('Features')
                                    ->placeholder('Add a feature bullet…'),
                            ])
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => isset($state['locale']) ? strtoupper($state['locale']) : null)
                            ->defaultItems(0)
                            ->addActionLabel('Add a locale'),
                    ]),

                Section::make('Promotional discount')
                    ->description('Optional. Applies to both billing periods within the date window.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('discount_percent')
                            ->label('Discount %')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(99)
                            ->nullable()
                            ->helperText('Leave blank for no promo. 1–99.'),
                        TextInput::make('discount_label')
                            ->label('Promo label')
                            ->placeholder('Spring sale')
                            ->maxLength(60)
                            ->nullable()
                            ->helperText('Shown on the card next to the discounted price.'),
                        DateTimePicker::make('discount_starts_at')
                            ->label('Starts at')
                            ->seconds(false)
                            ->nullable(),
                        DateTimePicker::make('discount_ends_at')
                            ->label('Ends at')
                            ->seconds(false)
                            ->nullable()
                            ->after('discount_starts_at'),
                    ]),
            ]);
    }
}
