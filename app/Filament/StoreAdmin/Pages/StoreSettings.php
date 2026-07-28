<?php

namespace App\Filament\StoreAdmin\Pages;

use App\Models\Store;
use App\Services\Money;
use App\Themes\ThemeRegistry;
use BackedEnum;
use Closure;
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
use Illuminate\Contracts\Support\Htmlable;

class StoreSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.store-admin.pages.store-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public ?array $data = [];

    // Navigation label and page title are resolved at request time, not as
    // static property defaults, so the active locale decides the wording.
    /*
     | Grouped rather than one flat list of eleven. getNavigationGroup() and not
     | the static $navigationGroup the SuperAdmin panel uses: a static property
     | initialiser cannot call __(), so that one is stuck in English forever.
     */
    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.group.shop');
    }

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.nav.label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.settings.nav.title');
    }

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

        // Contact page. Defaults come from the helper, but email, phone and the
        // map embed are hydrated from the RAW column: contactPage() substitutes
        // the tenant's contact details for a blank email/phone, and re-saving
        // that would bake the fallback into the store's own JSON — the merchant
        // could never get back to "inherit from my account". Same for the map:
        // the helper nulls a snippet it rejects, so showing the raw value is the
        // only way a merchant can see and fix what they actually pasted.
        $contact = $store->contactPage();
        $contactRaw = (array) ($store->contact ?? []);
        $data['contact_enabled']   = $contact['enabled'];
        $data['contact_show_form'] = $contact['show_form'];
        $data['contact_heading']   = $contact['heading'];
        $data['contact_intro']     = $contact['intro'];
        $data['contact_address']   = $contact['address'];
        $data['contact_phone']     = trim((string) ($contactRaw['phone'] ?? ''));
        $data['contact_email']     = trim((string) ($contactRaw['email'] ?? ''));
        $data['contact_hours']     = $contact['hours'];
        $data['contact_map_embed'] = trim((string) ($contactRaw['map_embed'] ?? ''));

        // History page. Straight out of the helper — nothing here inherits
        // from the account, so the normalised values ARE what's stored.
        // Images come back as a compacted list; spread it over the fixed
        // upload slots the form shows.
        $about = $store->aboutPage();
        $data['about_enabled']      = $about['enabled'];
        $data['about_heading']      = $about['heading'];
        $data['about_intro']        = $about['intro'];
        $data['about_story']        = $about['story'];
        $data['about_founded_year'] = $about['founded_year'];
        $data['about_milestones']   = $about['milestones'];
        $data['about_stats']        = $about['stats'];
        for ($i = 0; $i < Store::ABOUT_IMAGE_SLOTS; $i++) {
            $data['about_image_' . ($i + 1)] = $about['images'][$i] ?? null;
        }

        // Storefront effects.
        $data['number_animation'] = $store->numberAnimation();

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make(__('admin.settings.nav.title'))
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([

                // ───────────────────────── DESIGN ─────────────────────────
                Tab::make(__('admin.settings.nav.tab_design'))
                    ->icon(Heroicon::OutlinedSwatch)
                    ->schema([
                Section::make(__('admin.settings.section.theme'))
                    ->description(__('admin.settings.section_help.theme'))
                    ->headerActions([
                        Action::make('customizeTheme')
                            ->label(__('admin.settings.action.customize_theme'))
                            ->url(fn () => CustomizeTheme::getUrl()),
                    ])
                    ->schema([
                        Radio::make('theme')
                            ->label(__('admin.settings.field.theme'))
                            ->options(ThemeRegistry::options())
                            ->descriptions(ThemeRegistry::descriptions())
                            ->required(),
                    ]),
                Section::make(__('admin.settings.section.branding'))
                    ->columns(2)
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label(__('admin.settings.field.primary_color'))
                            ->required()
                            ->helperText(__('admin.settings.help.primary_color')),
                        ColorPicker::make('secondary_color')
                            ->label(__('admin.settings.field.secondary_color'))
                            ->required()
                            ->helperText(__('admin.settings.help.secondary_color')),
                        Select::make('font_family')
                            ->label(__('admin.settings.field.font_family'))
                            ->options([
                                'Inter' => 'Inter',
                                'Roboto' => 'Roboto',
                                'Lato' => 'Lato',
                                'Merriweather' => __('admin.settings.opt.font_merriweather'),
                                'Playfair Display' => __('admin.settings.opt.font_playfair'),
                            ])
                            ->required()
                            ->columnSpanFull(),
                        FileUpload::make('logo_path')
                            ->label(__('admin.settings.field.logo'))
                            ->image()
                            ->disk('public')
                            ->directory('logos')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('admin.settings.section.storefront_effects'))
                    ->description(__('admin.settings.section_help.storefront_effects'))
                    ->schema([
                        Select::make('number_animation')
                            ->label(__('admin.settings.field.number_animation'))
                            ->options(\App\Models\Store::numberAnimationOptions())
                            ->default('count')
                            ->required()
                            ->native(false)
                            ->helperText(__('admin.settings.help.number_animation')),
                    ]),
                    ]), // end Design tab

                // ─────────────────────── STOREFRONT ───────────────────────
                Tab::make(__('admin.settings.nav.tab_storefront'))
                    ->icon(Heroicon::OutlinedSparkles)
                    ->schema([
                Section::make(__('admin.settings.section.announcement'))
                    ->description(__('admin.settings.section_help.announcement'))
                    ->collapsible()
                    ->schema([
                        Toggle::make('announcement_enabled')
                            ->label(__('admin.settings.field.announcement_enabled')),
                        TextInput::make('announcement_text')
                            ->label(__('admin.settings.field.announcement_text'))
                            ->placeholder(__('admin.settings.ph.announcement_text'))
                            ->maxLength(180),
                        TextInput::make('announcement_link')
                            ->label(__('admin.settings.field.announcement_link'))
                            ->placeholder('https://example.com/shipping')
                            ->url()
                            ->maxLength(500),
                        Select::make('announcement_speed')
                            ->label(__('admin.settings.field.announcement_speed'))
                            ->options(\App\Models\Store::announcementSpeedOptions())
                            ->default('normal')
                            ->native(false)
                            ->helperText(__('admin.settings.help.announcement_speed')),
                    ]),

                Section::make(__('admin.settings.section.header_menu'))
                    ->description(__('admin.settings.section_help.header_menu'))
                    ->collapsible()
                    ->schema([
                        Repeater::make('nav_menu')
                            ->label(__('admin.settings.section.header_menu'))
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('label')
                                    ->label(__('admin.settings.field.nav_label'))
                                    ->required()
                                    ->maxLength(60)
                                    ->placeholder(__('admin.settings.ph.nav_label'))
                                    ->columnSpan(1),
                                TextInput::make('url')
                                    ->label(__('admin.settings.field.nav_url'))
                                    ->maxLength(500)
                                    ->placeholder('/')
                                    ->helperText(__('admin.settings.help.nav_url'))
                                    ->columnSpan(1),
                                TextInput::make('sort_order')
                                    ->label(__('admin.shared.field.sort_order'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText(__('admin.shared.help.sort_order'))
                                    ->columnSpan(1),
                                Select::make('auto_source')
                                    ->label(__('admin.settings.field.nav_auto_source'))
                                    ->options([
                                        'none'        => __('admin.settings.opt.nav_auto_none'),
                                        'categories' => __('admin.settings.opt.nav_auto_categories'),
                                        'collections'=> __('admin.settings.opt.nav_auto_collections'),
                                    ])
                                    ->default('none')
                                    ->live()
                                    ->helperText(__('admin.settings.help.nav_auto_source'))
                                    ->columnSpanFull(),
                                Repeater::make('children')
                                    ->label(__('admin.settings.field.nav_children'))
                                    ->visible(fn (Get $get): bool => ($get('auto_source') ?? 'none') === 'none')
                                    ->schema([
                                        TextInput::make('label')
                                            ->label(__('admin.settings.field.nav_label'))
                                            ->required()
                                            ->maxLength(60)
                                            ->placeholder(__('admin.settings.ph.nav_child_label')),
                                        TextInput::make('url')
                                            ->label(__('admin.settings.field.nav_url'))
                                            ->required()
                                            ->maxLength(500)
                                            ->placeholder('/categories/apparel'),
                                        TextInput::make('sort_order')
                                            ->label(__('admin.shared.field.sort_order'))
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
                                    ->addActionLabel(__('admin.settings.action.add_sub_link'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->reorderable()
                            ->reorderableWithDragAndDrop()
                            ->collapsible()
                            ->cloneable()
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel(__('admin.settings.action.add_link')),
                    ]),

                Section::make(__('admin.settings.section.hero'))
                    ->description(__('admin.settings.section_help.hero'))
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Toggle::make('hero_enabled')
                            ->label(__('admin.settings.field.hero_enabled'))
                            ->columnSpanFull(),
                        TextInput::make('hero_title')
                            ->label(__('admin.settings.field.hero_title'))
                            ->placeholder(__('admin.settings.ph.hero_title'))
                            ->maxLength(120),
                        TextInput::make('hero_subtitle')
                            ->label(__('admin.settings.field.hero_subtitle'))
                            ->placeholder(__('admin.settings.ph.hero_subtitle'))
                            ->maxLength(200),
                        TextInput::make('hero_cta_label')
                            ->label(__('admin.settings.field.hero_cta_label'))
                            ->placeholder(__('admin.settings.ph.hero_cta_label'))
                            ->maxLength(40),
                        TextInput::make('hero_cta_url')
                            ->label(__('admin.settings.field.hero_cta_url'))
                            ->placeholder('/')
                            ->maxLength(500),
                        FileUpload::make('hero_image_path')
                            ->label(__('admin.settings.field.hero_image'))
                            ->image()
                            ->disk('public')
                            ->directory('hero-banners')
                            ->maxSize(4096)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.settings.section.collection_strips'))
                    ->description(__('admin.settings.section_help.collection_strips'))
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Select::make('collection_band_height')
                            ->label(__('admin.settings.field.collection_band_height'))
                            ->options([
                                'compact'  => __('admin.settings.opt.band_compact'),
                                'standard' => __('admin.settings.opt.band_standard'),
                                'tall'     => __('admin.settings.opt.band_tall'),
                                'custom'   => __('admin.settings.opt.custom_px'),
                            ])
                            ->default('standard')
                            ->selectablePlaceholder(false)
                            ->native(false)
                            ->live()
                            ->helperText(__('admin.settings.help.collection_band_height')),
                        TextInput::make('collection_band_height_px')
                            ->label(__('admin.settings.field.collection_band_height_px'))
                            ->numeric()
                            ->suffix('px')
                            ->minValue(Store::COLLECTION_BAND_MIN)
                            ->maxValue(Store::COLLECTION_BAND_MAX)
                            ->visible(fn (Get $get): bool => ($get('collection_band_height') ?? 'standard') === 'custom')
                            ->required(fn (Get $get): bool => ($get('collection_band_height') ?? 'standard') === 'custom')
                            // Keep the value in state even while hidden so toggling to a
                            // preset and back doesn't wipe the merchant's custom number.
                            ->dehydratedWhenHidden()
                            ->helperText(__('admin.settings.help.px_range', [
                                'min' => Store::COLLECTION_BAND_MIN,
                                'max' => Store::COLLECTION_BAND_MAX,
                            ])),
                        Select::make('collection_title_size')
                            ->label(__('admin.settings.field.collection_title_size'))
                            ->options([
                                'small'  => __('admin.settings.opt.size_small'),
                                'medium' => __('admin.settings.opt.size_medium'),
                                'large'  => __('admin.settings.opt.size_large'),
                                'custom' => __('admin.settings.opt.custom_px'),
                            ])
                            ->default('medium')
                            ->selectablePlaceholder(false)
                            ->native(false)
                            ->live()
                            ->helperText(__('admin.settings.help.collection_title_size')),
                        TextInput::make('collection_title_size_px')
                            ->label(__('admin.settings.field.collection_title_size_px'))
                            ->numeric()
                            ->suffix('px')
                            ->minValue(Store::COLLECTION_TITLE_MIN)
                            ->maxValue(Store::COLLECTION_TITLE_MAX)
                            ->visible(fn (Get $get): bool => ($get('collection_title_size') ?? 'medium') === 'custom')
                            ->required(fn (Get $get): bool => ($get('collection_title_size') ?? 'medium') === 'custom')
                            ->dehydratedWhenHidden()
                            ->helperText(__('admin.settings.help.px_range', [
                                'min' => Store::COLLECTION_TITLE_MIN,
                                'max' => Store::COLLECTION_TITLE_MAX,
                            ])),
                    ]),

                Section::make(__('admin.settings.section.contact'))
                    ->description(__('admin.settings.section_help.contact'))
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Toggle::make('contact_enabled')
                            ->label(__('admin.settings.field.contact_enabled'))
                            ->default(true)
                            ->helperText(__('admin.settings.help.contact_enabled'))
                            ->columnSpanFull(),
                        Toggle::make('contact_show_form')
                            ->label(__('admin.settings.field.contact_show_form'))
                            ->default(true)
                            ->helperText(__('admin.settings.help.contact_show_form'))
                            ->columnSpanFull(),
                        TextInput::make('contact_heading')
                            ->label(__('admin.settings.field.contact_heading'))
                            ->placeholder(__('admin.settings.ph.contact_heading'))
                            ->maxLength(120)
                            ->helperText(__('admin.settings.help.default_wording'))
                            ->columnSpanFull(),
                        Textarea::make('contact_intro')
                            ->label(__('admin.settings.field.contact_intro'))
                            ->rows(3)
                            ->maxLength(600)
                            ->placeholder(__('admin.settings.ph.contact_intro'))
                            ->helperText(__('admin.settings.help.default_wording'))
                            ->columnSpanFull(),
                        Textarea::make('contact_address')
                            ->label(__('admin.settings.field.contact_address'))
                            ->rows(3)
                            ->maxLength(300)
                            ->placeholder(__('admin.settings.ph.contact_address'))
                            ->columnSpanFull(),
                        TextInput::make('contact_phone')
                            ->label(__('admin.settings.field.contact_phone'))
                            ->tel()
                            ->maxLength(40)
                            ->placeholder('+359 2 123 4567'),
                        TextInput::make('contact_email')
                            ->label(__('admin.settings.field.contact_email'))
                            ->email()
                            ->maxLength(255)
                            ->placeholder('hello@yourbrand.com')
                            ->helperText(__('admin.settings.help.contact_email')),
                        Textarea::make('contact_hours')
                            ->label(__('admin.settings.field.contact_hours'))
                            ->rows(3)
                            ->maxLength(300)
                            ->placeholder(__('admin.settings.ph.contact_hours'))
                            ->columnSpanFull(),
                        Textarea::make('contact_map_embed')
                            ->label(__('admin.settings.field.contact_map_embed'))
                            ->rows(4)
                            ->maxLength(4000)
                            ->placeholder('<iframe src="https://www.google.com/maps/embed?pb=…" …></iframe>')
                            ->helperText(__('admin.settings.help.contact_map_embed'))
                            // Mirrors the guard in Store::contactPage(), which silently
                            // drops a snippet it doesn't recognize. Without this rule the
                            // save would look successful and the map would just never
                            // appear, with nothing to explain why.
                            ->rule(static fn (): Closure => static function (string $attribute, $value, Closure $fail): void {
                                $snippet = trim((string) $value);
                                if ($snippet === '' || Store::sanitizeMapEmbed($snippet) !== null) {
                                    return;
                                }
                                $fail(__('admin.settings.help.contact_map_embed_invalid'));
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make(__('admin.settings.section.about'))
                    ->description(__('admin.settings.section_help.about'))
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Toggle::make('about_enabled')
                            ->label(__('admin.settings.field.about_enabled'))
                            ->helperText(__('admin.settings.help.about_enabled'))
                            ->columnSpanFull(),
                        TextInput::make('about_heading')
                            ->label(__('admin.settings.field.about_heading'))
                            ->placeholder(__('admin.settings.ph.about_heading'))
                            ->maxLength(120)
                            ->helperText(__('admin.settings.help.default_wording')),
                        TextInput::make('about_founded_year')
                            ->label(__('admin.settings.field.about_founded_year'))
                            ->numeric()
                            ->minValue(1800)
                            ->maxValue((int) date('Y'))
                            ->placeholder((string) (date('Y') - 20))
                            ->helperText(__('admin.settings.help.about_founded_year')),
                        Textarea::make('about_intro')
                            ->label(__('admin.settings.field.about_intro'))
                            ->rows(3)
                            ->maxLength(600)
                            ->placeholder(__('admin.settings.ph.about_intro'))
                            ->helperText(__('admin.settings.help.about_intro'))
                            ->columnSpanFull(),
                        Textarea::make('about_story')
                            ->label(__('admin.settings.field.about_story'))
                            ->rows(10)
                            ->maxLength(6000)
                            ->placeholder(__('admin.settings.ph.about_story'))
                            ->helperText(__('admin.settings.help.about_story'))
                            ->columnSpanFull(),
                        Repeater::make('about_milestones')
                            ->label(__('admin.settings.field.about_milestones'))
                            ->helperText(__('admin.settings.help.about_milestones'))
                            ->schema([
                                TextInput::make('year')
                                    ->label(__('admin.settings.field.milestone_year'))
                                    ->maxLength(20)
                                    ->placeholder('1998')
                                    ->helperText(__('admin.settings.help.milestone_year'))
                                    ->columnSpan(1),
                                TextInput::make('title')
                                    ->label(__('admin.settings.field.milestone_title'))
                                    ->maxLength(120)
                                    ->placeholder(__('admin.settings.ph.milestone_title'))
                                    ->columnSpan(2),
                                Textarea::make('text')
                                    ->label(__('admin.settings.field.milestone_text'))
                                    ->rows(2)
                                    ->maxLength(600)
                                    ->placeholder(__('admin.settings.ph.milestone_text'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->reorderable()
                            ->reorderableWithDragAndDrop()
                            ->collapsible()
                            ->cloneable()
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => trim(($state['year'] ?? '') . ' · ' . ($state['title'] ?? ''), ' ·') ?: null)
                            ->addActionLabel(__('admin.settings.action.add_milestone'))
                            ->columnSpanFull(),
                        Repeater::make('about_stats')
                            ->label(__('admin.settings.field.about_stats'))
                            ->helperText(__('admin.settings.help.about_stats'))
                            ->schema([
                                TextInput::make('value')
                                    ->label(__('admin.settings.field.stat_value'))
                                    ->maxLength(20)
                                    ->placeholder('25+')
                                    ->helperText(__('admin.settings.help.stat_value'))
                                    ->columnSpan(1),
                                TextInput::make('label')
                                    ->label(__('admin.settings.field.stat_label'))
                                    ->maxLength(80)
                                    ->placeholder(__('admin.settings.ph.stat_label'))
                                    ->columnSpan(2),
                            ])
                            ->columns(3)
                            ->reorderable()
                            ->reorderableWithDragAndDrop()
                            ->collapsible()
                            ->cloneable()
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => trim(($state['value'] ?? '') . ' ' . ($state['label'] ?? '')) ?: null)
                            ->addActionLabel(__('admin.settings.action.add_number'))
                            ->columnSpanFull(),
                        FileUpload::make('about_image_1')
                            ->label(__('admin.settings.field.about_image', ['number' => 1]))
                            ->image()
                            ->disk('public')
                            ->directory('about')
                            ->maxSize(4096)
                            ->helperText(__('admin.settings.help.about_images'))
                            ->columnSpanFull(),
                        FileUpload::make('about_image_2')
                            ->label(__('admin.settings.field.about_image', ['number' => 2]))
                            ->image()
                            ->disk('public')
                            ->directory('about')
                            ->maxSize(4096)
                            ->columnSpanFull(),
                        FileUpload::make('about_image_3')
                            ->label(__('admin.settings.field.about_image', ['number' => 3]))
                            ->image()
                            ->disk('public')
                            ->directory('about')
                            ->maxSize(4096)
                            ->columnSpanFull(),
                    ]),
                    ]), // end Storefront tab

                // ───────────────────── CURRENCY & DOMAIN ─────────────────────
                Tab::make(__('admin.settings.nav.tab_currency_domain'))
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->schema([
                Section::make(__('admin.settings.section.currency'))
                    ->description(__('admin.settings.section_help.currency'))
                    ->schema([
                        Select::make('currency')
                            ->label(__('admin.settings.field.currency'))
                            ->helperText(__('admin.settings.help.currency'))
                            ->options(Money::options())
                            ->required()
                            ->default('EUR')
                            ->live(),
                        CheckboxList::make('display_currencies')
                            ->label(__('admin.settings.field.display_currencies'))
                            ->helperText(__('admin.settings.help.display_currencies'))
                            ->options(Money::options())
                            ->columns(2)
                            ->bulkToggleable(),
                        KeyValue::make('fx_rates')
                            ->label(__('admin.settings.field.fx_rates'))
                            ->helperText(__('admin.settings.help.fx_rates'))
                            ->keyLabel(__('admin.settings.field.fx_key'))
                            ->valueLabel(__('admin.settings.field.fx_value'))
                            ->keyPlaceholder('USD')
                            ->valuePlaceholder('1.09')
                            ->reorderable(false),
                    ]),

                Section::make(__('admin.settings.section.custom_domain'))
                    ->description(__('admin.settings.section_help.custom_domain'))
                    ->schema([
                        TextInput::make('custom_domain')
                            ->label(__('admin.settings.field.custom_domain'))
                            ->placeholder('shop.acmecorp.com')
                            ->helperText(__('admin.settings.help.custom_domain'))
                            ->rule('regex:/^[a-z0-9][a-z0-9.\-]+[a-z0-9]$/')
                            ->maxLength(255)
                            ->unique(table: 'stores', column: 'custom_domain', ignorable: fn () => $this->getStore())
                            ->nullable(),
                    ]),
                    ]), // end Currency & domain tab

                // ─────────────────── CHECKOUT & SHIPPING ───────────────────
                Tab::make(__('admin.settings.nav.tab_checkout_shipping'))
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->schema([
                Section::make(__('admin.settings.section.customer_accounts'))
                    ->description(__('admin.settings.section_help.customer_accounts'))
                    ->schema([
                        Radio::make('checkout_mode')
                            ->label(__('admin.settings.field.checkout_mode'))
                            ->options(\App\Models\Store::checkoutModeOptions())
                            ->default(\App\Models\Store::CHECKOUT_BOTH)
                            ->required(),
                        Toggle::make('allow_registration')
                            ->label(__('admin.settings.field.allow_registration'))
                            ->helperText(__('admin.settings.help.allow_registration'))
                            ->default(true),
                    ]),

                Section::make(__('admin.settings.section.signup_fields'))
                    ->description(__('admin.settings.section_help.signup_fields'))
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        // Phone
                        Toggle::make('signup_phone_enabled')->label(__('admin.settings.field.signup_phone_enabled'))->columnSpan(1),
                        Toggle::make('signup_phone_required')->label(__('admin.settings.field.signup_required'))->helperText(__('admin.settings.help.signup_required'))->columnSpan(1),
                        // Birthday
                        Toggle::make('signup_birthday_enabled')->label(__('admin.settings.field.signup_birthday_enabled'))->columnSpan(1),
                        Toggle::make('signup_birthday_required')->label(__('admin.settings.field.signup_required'))->columnSpan(1),
                        // Shipping address (4 sub-inputs rendered together at signup)
                        Toggle::make('signup_shipping_address_enabled')->label(__('admin.settings.field.signup_shipping_address_enabled'))->columnSpan(1),
                        Toggle::make('signup_shipping_address_required')->label(__('admin.settings.field.signup_required'))->columnSpan(1),
                        // Marketing opt-in
                        Toggle::make('signup_marketing_optin_enabled')->label(__('admin.settings.field.signup_marketing_optin_enabled'))->columnSpan(1),
                        Toggle::make('signup_marketing_optin_required')->label(__('admin.settings.field.signup_marketing_required'))->helperText(__('admin.settings.help.signup_marketing_required'))->columnSpan(1),
                    ]),

                Section::make(__('admin.settings.section.shipping_methods'))
                    ->description(__('admin.settings.section_help.shipping_methods'))
                    ->collapsible()
                    ->schema([
                        Repeater::make('shipping_methods')
                            ->label(__('admin.settings.section.shipping_methods'))
                            ->hiddenLabel()
                            ->addActionLabel(__('admin.settings.action.add_shipping_method'))
                            ->reorderableWithDragAndDrop()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->defaultItems(0)
                            ->columns(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('label')
                                    ->label(__('admin.settings.field.shipping_label'))
                                    ->required()
                                    ->maxLength(120)
                                    ->placeholder(__('admin.settings.ph.shipping_label'))
                                    ->columnSpan(2),
                                \Filament\Forms\Components\TextInput::make('description')
                                    ->label(__('admin.settings.field.shipping_description'))
                                    ->maxLength(160)
                                    ->placeholder(__('admin.settings.ph.shipping_description'))
                                    ->columnSpan(2),
                                \Filament\Forms\Components\TextInput::make('price_cents')
                                    ->label(__('admin.shared.field.price'))
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
                                    ->label(__('admin.settings.field.shipping_free_threshold'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->nullable()
                                    ->prefix(fn () => \App\Services\Money::symbol(
                                        auth()->user()?->tenant?->store?->currency ?? 'EUR'
                                    ))
                                    ->formatStateUsing(fn ($state) => ($state === null || $state === '') ? null : number_format((int) $state / 100, 2, '.', ''))
                                    ->dehydrateStateUsing(fn ($state) => ($state === null || $state === '') ? null : (int) round(((float) $state) * 100))
                                    ->helperText(__('admin.settings.help.shipping_free_threshold')),
                            ]),
                    ]),

                Section::make(__('admin.shared.section.visibility'))
                    ->schema([
                        Toggle::make('is_live')
                            ->label(__('admin.settings.field.is_live'))
                            ->helperText(__('admin.settings.help.is_live')),
                    ]),
                    ]), // end Checkout & shipping tab

                // ───────────────────────── ADMIN PANEL ─────────────────────────
                Tab::make(__('admin.settings.nav.tab_admin_panel'))
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->schema([
                Section::make(__('admin.settings.section.admin_appearance'))
                    ->description(__('admin.settings.section_help.admin_appearance'))
                    ->columns(2)
                    ->schema([
                        FileUpload::make('admin_logo_path')
                            ->label(__('admin.settings.field.admin_logo'))
                            ->image()
                            ->disk('public')
                            ->directory('admin-logos')
                            ->maxSize(2048)
                            ->helperText(__('admin.settings.help.admin_logo'))
                            ->columnSpanFull(),
                        ColorPicker::make('admin_accent_color')
                            ->label(__('admin.settings.field.admin_accent_color'))
                            ->helperText(__('admin.settings.help.admin_accent_color'))
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

        // Contact page: same fold-into-JSON pattern. Every value stays optional —
        // a blank heading/intro falls back to the platform copy, and a blank
        // email/phone falls back to the account contact details, both resolved
        // by Store::contactPage() at render time.
        $state['contact'] = [
            'enabled'   => (bool) ($state['contact_enabled'] ?? false),
            'show_form' => (bool) ($state['contact_show_form'] ?? false),
            'heading'   => trim((string) ($state['contact_heading'] ?? '')),
            'intro'     => trim((string) ($state['contact_intro'] ?? '')),
            'address'   => trim((string) ($state['contact_address'] ?? '')),
            'phone'     => trim((string) ($state['contact_phone'] ?? '')),
            'email'     => trim((string) ($state['contact_email'] ?? '')),
            'hours'     => trim((string) ($state['contact_hours'] ?? '')),
            'map_embed' => trim((string) ($state['contact_map_embed'] ?? '')) ?: null,
        ];
        unset($state['contact_enabled'], $state['contact_show_form'], $state['contact_heading'],
              $state['contact_intro'], $state['contact_address'], $state['contact_phone'],
              $state['contact_email'], $state['contact_hours'], $state['contact_map_embed']);

        // History page: same fold-into-JSON pattern. Rows and images are stored
        // raw-ish (trimmed only) — Store::aboutPage() is what drops the empties
        // and clamps the year, and keeping that in ONE place means a row saved
        // by an older form version still renders correctly.
        $aboutImages = [];
        for ($i = 1; $i <= Store::ABOUT_IMAGE_SLOTS; $i++) {
            $key = "about_image_{$i}";
            $path = trim((string) ($state[$key] ?? ''));
            if ($path !== '') {
                $aboutImages[] = $path;
            }
            unset($state[$key]);
        }
        $state['about'] = [
            'enabled'      => (bool) ($state['about_enabled'] ?? false),
            'heading'      => trim((string) ($state['about_heading'] ?? '')),
            'intro'        => trim((string) ($state['about_intro'] ?? '')),
            'story'        => trim((string) ($state['about_story'] ?? '')),
            'founded_year' => (int) ($state['about_founded_year'] ?? 0) ?: null,
            'milestones'   => collect($state['about_milestones'] ?? [])
                ->filter(fn ($m) => is_array($m))
                ->map(fn ($m) => [
                    'year'  => trim((string) ($m['year'] ?? '')),
                    'title' => trim((string) ($m['title'] ?? '')),
                    'text'  => trim((string) ($m['text'] ?? '')),
                ])
                ->filter(fn ($m) => $m['year'] !== '' || $m['title'] !== '' || $m['text'] !== '')
                ->values()
                ->all(),
            'stats'        => collect($state['about_stats'] ?? [])
                ->filter(fn ($s) => is_array($s))
                ->map(fn ($s) => [
                    'value' => trim((string) ($s['value'] ?? '')),
                    'label' => trim((string) ($s['label'] ?? '')),
                ])
                ->filter(fn ($s) => $s['value'] !== '' && $s['label'] !== '')
                ->values()
                ->all(),
            'images'       => $aboutImages,
        ];
        unset($state['about_enabled'], $state['about_heading'], $state['about_intro'],
              $state['about_story'], $state['about_founded_year'], $state['about_milestones'],
              $state['about_stats']);

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
            ->title(__('admin.settings.notify.saved'))
            ->send();
    }

    protected function getHeaderActions(): array
    {
        $store = $this->getStore();

        return [
            Action::make('verify')
                ->label(__('admin.settings.action.verify_domain'))
                ->icon(Heroicon::OutlinedShieldCheck)
                ->color('info')
                ->visible(fn () => filled($store->custom_domain) && ! $store->hasVerifiedCustomDomain())
                ->action(function () use ($store) {
                    $token = $store->ensureVerificationToken();
                    $records = @dns_get_record($store->custom_domain, DNS_TXT);
                    $values = collect($records ?: [])->pluck('txt')->all();

                    if (in_array($token, $values, true)) {
                        $store->update(['custom_domain_verified_at' => now()]);
                        Notification::make()->success()->title(__('admin.settings.notify.domain_verified'))->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title(__('admin.settings.notify.txt_not_found'))
                            ->body(__('admin.settings.notify.txt_not_found_body', ['domain' => $store->custom_domain]))
                            ->send();
                    }
                }),

            Action::make('forceVerify')
                ->label(__('admin.settings.action.force_verify'))
                ->icon(Heroicon::OutlinedBeaker)
                ->color('warning')
                ->visible(fn () => app()->environment('local') && filled($store->custom_domain) && ! $store->hasVerifiedCustomDomain())
                ->requiresConfirmation()
                ->modalDescription(__('admin.settings.help.force_verify'))
                ->action(function () use ($store) {
                    $store->ensureVerificationToken();
                    $store->update(['custom_domain_verified_at' => now()]);
                    Notification::make()->success()->title(__('admin.settings.notify.domain_force_verified'))->send();
                }),

            Action::make('unverify')
                ->label(__('admin.settings.action.unverify'))
                ->icon(Heroicon::OutlinedXMark)
                ->color('danger')
                ->visible(fn () => $store->hasVerifiedCustomDomain())
                ->requiresConfirmation()
                ->action(function () use ($store) {
                    $store->update([
                        'custom_domain_verified_at' => null,
                        'custom_domain_verification_token' => null,
                    ]);
                    Notification::make()->warning()->title(__('admin.settings.notify.domain_unverified'))->send();
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
