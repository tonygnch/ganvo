<?php

namespace App\Filament\StoreAdmin\Pages;

use App\Models\Store;
use App\Services\Money;
use App\Themes\ThemeRegistry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class StoreSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.store-admin.pages.store-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Store Settings';

    protected static ?string $title = 'Store Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $store = $this->getStore();
        $data = $store->only([
            'theme',
            'primary_color',
            'secondary_color',
            'font_family',
            'logo_path',
            'currency',
            'custom_domain',
            'is_live',
            'checkout_mode',
            'allow_registration',
        ]);
        // CheckboxList wants a flat array of codes.
        $data['display_currencies'] = $store->display_currencies ?: [];
        // KeyValue stores as strings; cast rates to numeric strings for editing.
        $data['fx_rates'] = collect($store->fx_rates ?? [])
            ->mapWithKeys(fn ($rate, $code) => [$code => (string) $rate])
            ->all();
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Theme')
                    ->description('Pick a starting point for your storefront.')
                    ->schema([
                        Radio::make('theme')
                            ->options(ThemeRegistry::options())
                            ->descriptions(collect(ThemeRegistry::all())->map(fn ($t) => $t['description'])->all())
                            ->required(),
                    ]),
                Section::make('Branding')
                    ->columns(2)
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->required()
                            ->helperText('Used for buttons, links, and accents.'),
                        ColorPicker::make('secondary_color')
                            ->required()
                            ->helperText('Used for header background and primary text.'),
                        Select::make('font_family')
                            ->options([
                                'Inter' => 'Inter',
                                'Roboto' => 'Roboto',
                                'Lato' => 'Lato',
                                'Merriweather' => 'Merriweather (serif)',
                                'Playfair Display' => 'Playfair Display (serif)',
                            ])
                            ->required()
                            ->columnSpanFull(),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('logos')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ]),
                Section::make('Currency')
                    ->description('Customers can switch the displayed currency in the storefront header; you\'re still paid in your base currency.')
                    ->schema([
                        Select::make('currency')
                            ->label('Base currency')
                            ->helperText('The currency you price products in and get paid in.')
                            ->options(Money::options())
                            ->required()
                            ->default('USD')
                            ->live(),
                        CheckboxList::make('display_currencies')
                            ->label('Customer display currencies')
                            ->helperText('Currencies customers can switch their view to. Your base currency is always available.')
                            ->options(Money::options())
                            ->columns(2)
                            ->bulkToggleable(),
                        KeyValue::make('fx_rates')
                            ->label('Exchange rates from base currency')
                            ->helperText('Units of target per 1 unit of base. E.g. if base is USD and 1 USD = 0.92 EUR, enter EUR → 0.92. You do not need a row for your base currency.')
                            ->keyLabel('Currency code')
                            ->valueLabel('Rate from base')
                            ->keyPlaceholder('EUR')
                            ->valuePlaceholder('0.92')
                            ->reorderable(false),
                    ]),

                Section::make('Custom domain')
                    ->description('Optional. Use your own domain instead of the *.ganvo.lvh.me subdomain.')
                    ->schema([
                        TextInput::make('custom_domain')
                            ->label('Domain')
                            ->placeholder('shop.acmecorp.com')
                            ->helperText('Lowercase, no scheme, no path. After saving, follow the instructions below to verify ownership.')
                            ->rule('regex:/^[a-z0-9][a-z0-9.\-]+[a-z0-9]$/')
                            ->maxLength(255)
                            ->unique(table: 'stores', column: 'custom_domain', ignorable: fn () => $this->getStore())
                            ->nullable(),
                    ]),
                Section::make('Customer accounts')
                    ->description('Decide whether shoppers must sign in to check out, or can buy as guests.')
                    ->schema([
                        Radio::make('checkout_mode')
                            ->label('Checkout mode')
                            ->options(\App\Models\Store::CHECKOUT_MODES)
                            ->default(\App\Models\Store::CHECKOUT_BOTH)
                            ->required(),
                        Toggle::make('allow_registration')
                            ->label('Allow new customer registrations')
                            ->helperText('When off, the storefront hides the "Create account" link. Existing customers can still sign in.')
                            ->default(true),
                    ]),

                Section::make('Visibility')
                    ->schema([
                        Toggle::make('is_live')
                            ->label('Storefront is live')
                            ->helperText('When off, visitors see a 404.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $store = $this->getStore();
        $newDomain = $this->data['custom_domain'] ?? null;
        $domainChanged = $store->custom_domain !== $newDomain;

        $state = $this->form->getState();

        // Sanitize currency state.
        $state['currency'] = strtoupper($state['currency'] ?? 'USD');

        // CheckboxList may return null; coerce + uppercase + drop the base
        // (it's always implicit) + de-dupe.
        $display = collect($state['display_currencies'] ?? [])
            ->map(fn ($c) => strtoupper((string) $c))
            ->reject(fn ($c) => $c === $state['currency'] || $c === '')
            ->unique()
            ->values()
            ->all();
        $state['display_currencies'] = $display;

        // FX rates: trim, uppercase keys, cast values to float, drop invalid
        // and drop the base currency entry.
        $rates = [];
        foreach (($state['fx_rates'] ?? []) as $code => $rate) {
            $code = strtoupper(trim((string) $code));
            if ($code === '' || $code === $state['currency']) {
                continue;
            }
            $rate = (float) $rate;
            if ($rate > 0) {
                $rates[$code] = $rate;
            }
        }
        $state['fx_rates'] = $rates;

        $store->update($state);

        // If the domain changed (added or modified), reset verification and rotate the token.
        if ($domainChanged) {
            $store->update([
                'custom_domain_verified_at' => null,
                'custom_domain_verification_token' => $newDomain ? null : null,
            ]);
            if ($newDomain) {
                $store->ensureVerificationToken();
            }
        }

        Notification::make()
            ->success()
            ->title('Store settings saved')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        $store = $this->getStore();

        return [
            Action::make('verify')
                ->label('Verify domain')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->color('info')
                ->visible(fn () => filled($store->custom_domain) && ! $store->hasVerifiedCustomDomain())
                ->action(function () use ($store) {
                    $token = $store->ensureVerificationToken();
                    $records = @dns_get_record($store->custom_domain, DNS_TXT);
                    $values = collect($records ?: [])->pluck('txt')->all();

                    if (in_array($token, $values, true)) {
                        $store->update(['custom_domain_verified_at' => now()]);
                        Notification::make()->success()->title('Domain verified')->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('TXT record not found')
                            ->body('No matching TXT record at ' . $store->custom_domain . '. DNS changes can take a few minutes to propagate.')
                            ->send();
                    }
                }),

            Action::make('forceVerify')
                ->label('Force verify (dev only)')
                ->icon(Heroicon::OutlinedBeaker)
                ->color('warning')
                ->visible(fn () => app()->environment('local') && filled($store->custom_domain) && ! $store->hasVerifiedCustomDomain())
                ->requiresConfirmation()
                ->modalDescription('Skip the real DNS check and mark this domain verified. Only available in local dev.')
                ->action(function () use ($store) {
                    $store->ensureVerificationToken();
                    $store->update(['custom_domain_verified_at' => now()]);
                    Notification::make()->success()->title('Domain force-verified')->send();
                }),

            Action::make('unverify')
                ->label('Remove verification')
                ->icon(Heroicon::OutlinedXMark)
                ->color('danger')
                ->visible(fn () => $store->hasVerifiedCustomDomain())
                ->requiresConfirmation()
                ->action(function () use ($store) {
                    $store->update([
                        'custom_domain_verified_at' => null,
                        'custom_domain_verification_token' => null,
                    ]);
                    Notification::make()->warning()->title('Domain unverified')->send();
                }),
        ];
    }

    public function getViewData(): array
    {
        return ['store' => $this->getStore()];
    }

    protected function getStore(): Store
    {
        $tenant = auth()->user()->tenant;
        return $tenant->store ?? $tenant->store()->create([]);
    }
}
