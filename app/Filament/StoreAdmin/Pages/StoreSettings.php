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
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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
            'admin_logo_path',
            'admin_accent_color',
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

        // Storefront chrome: hydrate into the shape the form sections expect.
        // Helpers on the model normalize defaults so a freshly-onboarded
        // store (with NULL columns) doesn't dump warnings into the form.
        $announcement = $store->announcementBar();
        $data['announcement_enabled'] = $announcement['enabled'];
        $data['announcement_text']    = $announcement['text'];
        $data['announcement_link']    = $announcement['link'];
        $data['announcement_speed']   = $announcement['speed'];

        // For nav_menu, we hydrate the FORM from the stored config, not
        // from navMenuItems() — the latter auto-injects categories/collections
        // children for display, but those auto rows shouldn't be saved back
        // into the JSON. We need the raw stored shape so the merchant edits
        // their config (auto_source flag) rather than a materialized copy.
        $rawNav = (array) ($store->nav_menu ?? []);
        $data['nav_menu'] = collect($rawNav)
            ->filter(fn ($r) => is_array($r) && ! empty($r['label']))
            ->map(function ($r) {
                $autoSource = $r['auto_source'] ?? 'none';
                if (! in_array($autoSource, ['none', 'categories', 'collections'], true)) {
                    $autoSource = 'none';
                }
                // Auto-source rows shouldn't carry stale manual children;
                // make sure they hydrate with an empty children array so
                // the conditionally-hidden Repeater starts fresh.
                $children = $autoSource === 'none'
                    ? collect((array) ($r['children'] ?? []))
                        ->filter(fn ($c) => is_array($c)
                            && ! empty($c['label']))
                        ->map(fn ($c) => [
                            'label'      => (string) $c['label'],
                            'url'        => (string) ($c['url'] ?? ''),
                            'sort_order' => (int) ($c['sort_order'] ?? 0),
                        ])
                        ->all()
                    : [];
                return [
                    'label'       => (string) $r['label'],
                    'url'         => (string) ($r['url'] ?? ''),
                    'sort_order'  => (int) ($r['sort_order'] ?? 0),
                    'auto_source' => $autoSource,
                    'children'    => $children,
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();

        $hero = $store->heroBanner();
        $data['hero_enabled']   = $hero['enabled'];
        $data['hero_title']     = $hero['title'];
        $data['hero_subtitle']  = $hero['subtitle'];
        $data['hero_image_path']= $hero['image_path'];

        // Signup-field config: flatten the per-field map into
        // signup_FIELD_enabled / signup_FIELD_required input names so each
        // toggle gets its own Filament component.
        foreach ($store->signupFieldsConfig() as $field => $cfg) {
            $data["signup_{$field}_enabled"]  = $cfg['enabled'];
            $data["signup_{$field}_required"] = $cfg['required'];
        }
        $data['hero_cta_label'] = $hero['cta_label'];
        $data['hero_cta_url']   = $hero['cta_url'];

        // Collection strip appearance. Hydrate the preset keyword from the
        // resolver, but pre-fill the custom-px boxes from the RAW stored px so a
        // merchant's last custom value survives switching to a preset and back
        // (the resolver returns the preset px for non-custom keys, which would
        // otherwise overwrite it). Falls back to the resolved px when no custom
        // value was ever entered.
        $cd = $store->collectionDisplay();
        $cdRaw = (array) ($store->collection_display ?? []);
        $data['collection_band_height']    = $cd['band_height'];
        $data['collection_band_height_px'] = (int) ($cdRaw['band_height_px'] ?? $cd['band_height_px']);
        $data['collection_title_size']     = $cd['title_size'];
        $data['collection_title_size_px']  = (int) ($cdRaw['title_size_px'] ?? $cd['title_size_px']);

        // Storefront effects.
        $data['number_animation'] = $store->numberAnimation();

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Store settings')
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([

                // ───────────────────────── DESIGN ─────────────────────────
                Tab::make('Design')
                    ->icon(Heroicon::OutlinedSwatch)
                    ->schema([
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

                Section::make('Storefront effects')
                    ->description('Fine-tune the motion of your storefront.')
                    ->schema([
                        Select::make('number_animation')
                            ->label('Cart number animation')
                            ->options(\App\Models\Store::NUMBER_ANIMATIONS)
                            ->default('count')
                            ->required()
                            ->native(false)
                            ->helperText('How prices and quantities animate when the cart updates without a page reload. Honors a visitor’s “reduce motion” setting automatically.'),
                    ]),
                    ]), // end Design tab

                // ─────────────────────── STOREFRONT ───────────────────────
                Tab::make('Storefront')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->schema([
                Section::make('Announcement bar')
                    ->description('A thin promo strip shown at the top of every page on your storefront.')
                    ->collapsible()
                    ->schema([
                        Toggle::make('announcement_enabled')
                            ->label('Show announcement bar'),
                        TextInput::make('announcement_text')
                            ->label('Text')
                            ->placeholder('Free shipping on orders over $50.')
                            ->maxLength(180),
                        TextInput::make('announcement_link')
                            ->label('Click-through link (optional)')
                            ->placeholder('https://example.com/shipping')
                            ->url()
                            ->maxLength(500),
                        Select::make('announcement_speed')
                            ->label('Scroll speed')
                            ->options(\App\Models\Store::announcementSpeedOptions())
                            ->default('normal')
                            ->native(false)
                            ->helperText('How fast the bar scrolls on themes that animate it (e.g. Brick). Choose “Static” to stop it scrolling. Themes with a non-moving bar ignore this.'),
                    ]),

                Section::make('Header menu')
                    ->description('Top-level navigation links shown in your storefront header. Drag rows to reorder. Add sub-links to turn a top-level item into a dropdown.')
                    ->collapsible()
                    ->schema([
                        Repeater::make('nav_menu')
                            ->label('')
                            ->schema([
                                TextInput::make('label')
                                    ->required()
                                    ->maxLength(60)
                                    ->placeholder('Shop')
                                    ->columnSpan(1),
                                TextInput::make('url')
                                    ->maxLength(500)
                                    ->placeholder('/')
                                    ->helperText('Leave blank to make this a dropdown-only header (no own page).')
                                    ->columnSpan(1),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText('Lower numbers come first.')
                                    ->columnSpan(1),
                                Select::make('auto_source')
                                    ->label('Dropdown contents')
                                    ->options([
                                        'none'        => 'Manual sub-links',
                                        'categories' => 'Auto: all categories tagged "Show in menu"',
                                        'collections'=> 'Auto: all collections tagged "Show in menu"',
                                    ])
                                    ->default('none')
                                    ->live()
                                    ->helperText('Pick "Auto" to have this dropdown stay in sync with your Categories or Collections list — no manual upkeep.')
                                    ->columnSpanFull(),
                                Repeater::make('children')
                                    ->label('Sub-links (dropdown)')
                                    ->visible(fn (Get $get): bool => ($get('auto_source') ?? 'none') === 'none')
                                    ->schema([
                                        TextInput::make('label')
                                            ->required()
                                            ->maxLength(60)
                                            ->placeholder('Apparel'),
                                        TextInput::make('url')
                                            ->required()
                                            ->maxLength(500)
                                            ->placeholder('/categories/apparel'),
                                        TextInput::make('sort_order')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
                                    ])
                                    ->columns(3)
                                    ->reorderable()
                                    ->reorderableWithDragAndDrop()
                                    ->collapsible()
                                    ->cloneable()
                                    ->defaultItems(0)
                                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                    ->addActionLabel('Add a sub-link')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->reorderable()
                            ->reorderableWithDragAndDrop()
                            ->collapsible()
                            ->cloneable()
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel('Add a link'),
                    ]),

                Section::make('Hero banner')
                    ->description('A large welcome panel above the product grid on your storefront home.')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Toggle::make('hero_enabled')
                            ->label('Show hero banner')
                            ->columnSpanFull(),
                        TextInput::make('hero_title')
                            ->label('Title')
                            ->placeholder('Spring collection')
                            ->maxLength(120),
                        TextInput::make('hero_subtitle')
                            ->label('Subtitle')
                            ->placeholder('Bright pieces for sunny days.')
                            ->maxLength(200),
                        TextInput::make('hero_cta_label')
                            ->label('Button text (optional)')
                            ->placeholder('Shop the collection')
                            ->maxLength(40),
                        TextInput::make('hero_cta_url')
                            ->label('Button link (optional)')
                            ->placeholder('/')
                            ->maxLength(500),
                        FileUpload::make('hero_image_path')
                            ->label('Background image (optional)')
                            ->image()
                            ->disk('public')
                            ->directory('hero-banners')
                            ->maxSize(4096)
                            ->columnSpanFull(),
                    ]),
                Section::make('Collection strips')
                    ->description('How featured-collection strips look on your storefront home. Honored by themes with a banner band (e.g. Brick).')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Select::make('collection_band_height')
                            ->label('Banner band height')
                            ->options([
                                'compact'  => 'Compact',
                                'standard' => 'Standard',
                                'tall'     => 'Tall',
                                'custom'   => 'Custom (px)',
                            ])
                            ->default('standard')
                            ->selectablePlaceholder(false)
                            ->native(false)
                            ->live()
                            ->helperText('Height of the image band behind a collection title.'),
                        TextInput::make('collection_band_height_px')
                            ->label('Custom band height')
                            ->numeric()
                            ->suffix('px')
                            ->minValue(Store::COLLECTION_BAND_MIN)
                            ->maxValue(Store::COLLECTION_BAND_MAX)
                            ->visible(fn (Get $get): bool => ($get('collection_band_height') ?? 'standard') === 'custom')
                            ->required(fn (Get $get): bool => ($get('collection_band_height') ?? 'standard') === 'custom')
                            // Keep the value in state even while hidden so toggling to a
                            // preset and back doesn't wipe the merchant's custom number.
                            ->dehydratedWhenHidden()
                            ->helperText('Between ' . Store::COLLECTION_BAND_MIN . ' and ' . Store::COLLECTION_BAND_MAX . ' px.'),
                        Select::make('collection_title_size')
                            ->label('Collection title size')
                            ->options([
                                'small'  => 'Small',
                                'medium' => 'Medium',
                                'large'  => 'Large',
                                'custom' => 'Custom (px)',
                            ])
                            ->default('medium')
                            ->selectablePlaceholder(false)
                            ->native(false)
                            ->live()
                            ->helperText('Size of each collection’s title.'),
                        TextInput::make('collection_title_size_px')
                            ->label('Custom title size')
                            ->numeric()
                            ->suffix('px')
                            ->minValue(Store::COLLECTION_TITLE_MIN)
                            ->maxValue(Store::COLLECTION_TITLE_MAX)
                            ->visible(fn (Get $get): bool => ($get('collection_title_size') ?? 'medium') === 'custom')
                            ->required(fn (Get $get): bool => ($get('collection_title_size') ?? 'medium') === 'custom')
                            ->dehydratedWhenHidden()
                            ->helperText('Between ' . Store::COLLECTION_TITLE_MIN . ' and ' . Store::COLLECTION_TITLE_MAX . ' px.'),
                    ]),
                    ]), // end Storefront tab

                // ───────────────────── CURRENCY & DOMAIN ─────────────────────
                Tab::make('Currency & domain')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->schema([
                Section::make('Currency')
                    ->description('Customers can switch the displayed currency in the storefront header; you\'re still paid in your base currency.')
                    ->schema([
                        Select::make('currency')
                            ->label('Base currency')
                            ->helperText('The currency you price products in and get paid in.')
                            ->options(Money::options())
                            ->required()
                            ->default('EUR')
                            ->live(),
                        CheckboxList::make('display_currencies')
                            ->label('Customer display currencies')
                            ->helperText('Currencies customers can switch their view to. Your base currency is always available.')
                            ->options(Money::options())
                            ->columns(2)
                            ->bulkToggleable(),
                        KeyValue::make('fx_rates')
                            ->label('Exchange rates from base currency')
                            ->helperText('Units of target per 1 unit of base. E.g. if base is EUR and 1 EUR = 1.09 USD, enter USD → 1.09. You do not need a row for your base currency.')
                            ->keyLabel('Currency code')
                            ->valueLabel('Rate from base')
                            ->keyPlaceholder('USD')
                            ->valuePlaceholder('1.09')
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
                    ]), // end Currency & domain tab

                // ─────────────────── CHECKOUT & SHIPPING ───────────────────
                Tab::make('Checkout & shipping')
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->schema([
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

                Section::make('Signup form fields')
                    ->description('Choose which optional fields your storefront signup form collects. Each field can be enabled and, separately, marked required.')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        // Phone
                        Toggle::make('signup_phone_enabled')->label('Collect phone number')->columnSpan(1),
                        Toggle::make('signup_phone_required')->label('Required')->helperText('Only honored when "Collect" is on.')->columnSpan(1),
                        // Birthday
                        Toggle::make('signup_birthday_enabled')->label('Collect birthday')->columnSpan(1),
                        Toggle::make('signup_birthday_required')->label('Required')->columnSpan(1),
                        // Shipping address (4 sub-inputs rendered together at signup)
                        Toggle::make('signup_shipping_address_enabled')->label('Collect shipping address')->columnSpan(1),
                        Toggle::make('signup_shipping_address_required')->label('Required')->columnSpan(1),
                        // Marketing opt-in
                        Toggle::make('signup_marketing_optin_enabled')->label('Show marketing opt-in checkbox')->columnSpan(1),
                        Toggle::make('signup_marketing_optin_required')->label('Required (must check to sign up)')->helperText('Useful for double opt-in flows; less common.')->columnSpan(1),
                    ]),

                Section::make('Shipping methods')
                    ->description('Set the shipping options customers can choose at checkout. The first row is pre-selected. Leave the list empty to use the built-in Standard (free over €50) + Express (€15) defaults.')
                    ->collapsible()
                    ->schema([
                        Repeater::make('shipping_methods')
                            ->label('')
                            ->addActionLabel('Add shipping method')
                            ->reorderableWithDragAndDrop()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->defaultItems(0)
                            ->columns(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('label')
                                    ->label('Display label')
                                    ->required()
                                    ->maxLength(120)
                                    ->placeholder('Standard shipping')
                                    ->columnSpan(2),
                                \Filament\Forms\Components\TextInput::make('description')
                                    ->label('Subtitle')
                                    ->maxLength(160)
                                    ->placeholder('3–5 business days')
                                    ->columnSpan(2),
                                \Filament\Forms\Components\TextInput::make('price_cents')
                                    ->label('Price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->prefix(fn () => \App\Services\Money::symbol(
                                        auth()->user()?->tenant?->store?->currency ?? 'EUR'
                                    ))
                                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((int) $state / 100, 2, '.', '') : '0.00')
                                    ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100))
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('free_threshold_cents')
                                    ->label('Free over')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->nullable()
                                    ->prefix(fn () => \App\Services\Money::symbol(
                                        auth()->user()?->tenant?->store?->currency ?? 'EUR'
                                    ))
                                    ->formatStateUsing(fn ($state) => ($state === null || $state === '') ? null : number_format((int) $state / 100, 2, '.', ''))
                                    ->dehydrateStateUsing(fn ($state) => ($state === null || $state === '') ? null : (int) round(((float) $state) * 100))
                                    ->helperText('Subtotal threshold above which this method is free. Leave blank to always charge the price.'),
                            ]),
                    ]),

                Section::make('Visibility')
                    ->schema([
                        Toggle::make('is_live')
                            ->label('Storefront is live')
                            ->helperText('When off, visitors see a 404.'),
                    ]),
                    ]), // end Checkout & shipping tab

                // ───────────────────────── ADMIN PANEL ─────────────────────────
                Tab::make('Admin panel')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->schema([
                Section::make('Admin panel appearance')
                    ->description('Brand your own admin workspace. This changes the logo and accent color you see here in the dashboard — it does not affect your storefront.')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('admin_logo_path')
                            ->label('Admin logo')
                            ->image()
                            ->disk('public')
                            ->directory('admin-logos')
                            ->maxSize(2048)
                            ->helperText('Shown in the top-left of your admin panel, replacing the Ganvo logo. Leave empty to keep the Ganvo mark. Takes effect on your next page load.')
                            ->columnSpanFull(),
                        ColorPicker::make('admin_accent_color')
                            ->label('Admin accent color')
                            ->helperText('Tints buttons, links, and the active menu item in your admin panel. Pick any colour except black, white, or grey (those fall back to the default green). Leave empty for the default green.')
                            ->columnSpanFull(),
                    ]),
                    ]), // end Admin panel tab

                    ]), // end Tabs
            ]);
    }

    public function save(): void
    {
        $store = $this->getStore();
        $newDomain = $this->data['custom_domain'] ?? null;
        $domainChanged = $store->custom_domain !== $newDomain;

        $state = $this->form->getState();

        // Sanitize currency state.
        $state['currency'] = strtoupper($state['currency'] ?? 'EUR');

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

        // Fold the flat announcement_* inputs back into the announcement JSON column.
        $annSpeed = $state['announcement_speed'] ?? 'normal';
        if (! array_key_exists($annSpeed, \App\Models\Store::ANNOUNCEMENT_SPEEDS)) {
            $annSpeed = 'normal';
        }
        $state['announcement'] = [
            'enabled' => (bool) ($state['announcement_enabled'] ?? false),
            'text'    => trim((string) ($state['announcement_text'] ?? '')),
            'link'    => trim((string) ($state['announcement_link'] ?? '')) ?: null,
            'speed'   => $annSpeed,
        ];
        unset($state['announcement_enabled'], $state['announcement_text'], $state['announcement_link'], $state['announcement_speed']);

        // Fold number_animation into theme_settings, preserving other keys.
        $anim = $state['number_animation'] ?? 'count';
        if (! array_key_exists($anim, \App\Models\Store::NUMBER_ANIMATIONS)) {
            $anim = 'count';
        }
        $themeSettings = (array) ($store->theme_settings ?? []);
        $themeSettings['number_animation'] = $anim;
        $state['theme_settings'] = $themeSettings;
        unset($state['number_animation']);

        // Nav menu: drop rows missing label, coerce sort_order to int, sort
        // ascending so the stored order matches what'll render. URL is now
        // optional — a row with no URL but with `children` (or auto_source)
        // becomes a dropdown-only parent in the rendered header. Drop rows
        // with neither url nor children nor auto_source — they'd be empty.
        $state['nav_menu'] = collect($state['nav_menu'] ?? [])
            ->filter(fn ($r) => is_array($r) && trim((string) ($r['label'] ?? '')) !== '')
            ->map(function ($r) {
                $url = trim((string) ($r['url'] ?? ''));
                $autoSource = $r['auto_source'] ?? 'none';
                if (! in_array($autoSource, ['none', 'categories', 'collections'], true)) {
                    $autoSource = 'none';
                }
                // Manual children are stored only when auto_source is "none".
                // Auto rows always store an empty children array — the
                // navMenuItems() helper injects the live list at render time.
                $children = $autoSource === 'none'
                    ? collect((array) ($r['children'] ?? []))
                        ->filter(fn ($c) => is_array($c)
                            && trim((string) ($c['label'] ?? '')) !== ''
                            && trim((string) ($c['url'] ?? '')) !== '')
                        ->map(fn ($c) => [
                            'label'      => trim((string) $c['label']),
                            'url'        => trim((string) $c['url']),
                            'sort_order' => (int) ($c['sort_order'] ?? 0),
                        ])
                        ->sortBy('sort_order')
                        ->values()
                        ->all()
                    : [];
                return [
                    'label'       => trim((string) $r['label']),
                    'url'         => $url !== '' ? $url : null,
                    'sort_order'  => (int) ($r['sort_order'] ?? 0),
                    'auto_source' => $autoSource === 'none' ? null : $autoSource,
                    'children'    => $children,
                ];
            })
            // Keep auto-source rows even when they have no URL — they'll
            // materialize children at render-time. Manual rows without URL
            // need at least one child to survive.
            ->filter(fn ($r) => $r['url'] !== null
                || ! empty($r['children'])
                || $r['auto_source'] !== null)
            ->sortBy('sort_order')
            ->values()
            ->all();

        // Hero banner: same pattern as announcement.
        $state['hero_banner'] = [
            'enabled'    => (bool) ($state['hero_enabled'] ?? false),
            'title'      => trim((string) ($state['hero_title'] ?? '')),
            'subtitle'   => trim((string) ($state['hero_subtitle'] ?? '')),
            'image_path' => $state['hero_image_path'] ?: null,
            'cta_label'  => trim((string) ($state['hero_cta_label'] ?? '')),
            'cta_url'    => trim((string) ($state['hero_cta_url'] ?? '')),
        ];
        unset($state['hero_enabled'], $state['hero_title'], $state['hero_subtitle'],
              $state['hero_image_path'], $state['hero_cta_label'], $state['hero_cta_url']);

        // Collection strip appearance: fold the flat inputs into one JSON blob.
        // Validate the preset keyword and clamp the custom px on the way in so
        // a hand-tampered request can't store an out-of-range or unknown value.
        $bandKey = $state['collection_band_height'] ?? 'standard';
        if (! array_key_exists($bandKey, Store::COLLECTION_BAND_HEIGHTS) && $bandKey !== 'custom') {
            $bandKey = 'standard';
        }
        $titleKey = $state['collection_title_size'] ?? 'medium';
        if (! array_key_exists($titleKey, Store::COLLECTION_TITLE_SIZES) && $titleKey !== 'custom') {
            $titleKey = 'medium';
        }
        $state['collection_display'] = [
            'band_height'    => $bandKey,
            'band_height_px' => max(Store::COLLECTION_BAND_MIN, min(Store::COLLECTION_BAND_MAX, (int) ($state['collection_band_height_px'] ?? 210))),
            'title_size'     => $titleKey,
            'title_size_px'  => max(Store::COLLECTION_TITLE_MIN, min(Store::COLLECTION_TITLE_MAX, (int) ($state['collection_title_size_px'] ?? 44))),
        ];
        unset($state['collection_band_height'], $state['collection_band_height_px'],
              $state['collection_title_size'], $state['collection_title_size_px']);

        // Fold the per-field signup toggles back into the signup_fields JSON.
        // Walks Store::SIGNUP_FIELDS so adding a new field name in the model
        // is the only edit needed to support it here too.
        $signup = [];
        foreach (\App\Models\Store::SIGNUP_FIELDS as $field) {
            $enabledKey  = "signup_{$field}_enabled";
            $requiredKey = "signup_{$field}_required";
            $signup[$field] = [
                'enabled'  => (bool) ($state[$enabledKey] ?? false),
                'required' => (bool) ($state[$requiredKey] ?? false),
            ];
            unset($state[$enabledKey], $state[$requiredKey]);
        }
        $state['signup_fields'] = $signup;

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
