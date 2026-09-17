<?php

namespace App\Filament\StoreAdmin\Pages;

use App\Models\Category;
use App\Models\RackPart;
use App\Models\Store;
use App\Services\Money;
use App\Services\Rack\RackConfig;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The rack configurator's price book, as the merchant sees it.
 *
 * The client's requirement (S19, S21) is blunt: no price, size or limit may
 * live in code, and changing one must not need a developer. So this page is
 * where the sizes on offer are chosen and where every part is priced, in the
 * order the job is done:
 *
 *   Размери       the heights, depths, bay widths and level counts on offer
 *   one tab per rack type — the plain, office and wine racks are priced
 *                 separately: a frame table (heights down, depths across, the
 *                 merchant's own supplier list, S18) and a board table
 *   Обков         the three fasteners, shared by every type
 *   Настройки     VAT, the length limit, how the models are built
 *
 * Every price is one plain box in a table. A blank box is a size that is not
 * sold for that type — there is no separate on/off switch to keep in step.
 */
class RackConfigurator extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.store-admin.pages.rack-configurator';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 30;

    public ?array $data = [];

    /** What a size may be: cm for the three dimensions, a count for the levels. */
    private const SIZE_RANGES = [
        'heights' => [50, 400],
        'depths' => [10, 150],
        'widths' => [30, 300],
        // an office rack gives one level to the desk and needs another for a shelf
        'levels' => [2, 20],
    ];

    /*
     | getNavigationGroup() and not the static property: a static initialiser
     | cannot call __(), which would pin this to English for good.
     */
    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.group.shop');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.configurator.nav.label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.configurator.nav.label');
    }

    /**
     * Sankevi's feature, and only theirs. There is no feature-flag mechanism
     * in this panel, so the same opt-in key that gates the storefront route
     * gates the screen — one switch, one truth.
     */
    public static function canAccess(): bool
    {
        $store = auth()->user()?->tenant?->store;

        return (bool) ($store?->rackConfigurator()['enabled'] ?? false);
    }

    public function mount(): void
    {
        $store = $this->getStore();
        $limits = $store->rackConfigurator();
        $parts = RackPart::forTenant($store->tenant_id)->get()->keyBy(fn (RackPart $p) => $p->lookupKey());

        $data = [
            'vat_rate' => $limits['vat_rate_bp'] / 100,
            'max_length_cm' => $limits['max_length_cm'],
            'shelf_width_trim_cm' => $limits['shelf_width_trim_cm'],
            'shelf_depth_trim_cm' => $limits['shelf_depth_trim_cm'],
            'shelf_thickness_mm' => $limits['shelf_thickness_mm'],
            'over_limit_text' => $limits['over_limit_text'],
            'desk_height_cm' => $limits['desk_height_cm'],
            'tray_rim_cm' => $limits['tray_rim_cm'],
            'tray_tilt_deg' => $limits['tray_tilt_deg'],
            'model_depth_cm' => $limits['model_depth_cm'],
            'tab_category_ids' => $limits['tab_category_ids'],
        ];

        foreach (array_keys(self::SIZE_RANGES) as $name) {
            $data[$name] = array_map('strval', $limits[$name]);
        }

        foreach (RackPart::FLAT_KINDS as $kind) {
            $part = $parts->get($kind);
            $data["part_{$kind}"] = $part?->is_active ? $this->toDecimal($part->price_cents) : null;
            $data["part_sku_{$kind}"] = $part?->sku;
        }

        $this->form->fill($data);

        /*
         | The price tables live beside the form rather than in it: they are
         | plain inputs bound to data.prices.{kind}.{type}.{row}x{col}. A cell
         | shows a price only while that part is on sale, so what the merchant
         | sees blank is exactly what the storefront does not sell.
         */
        $prices = [];
        foreach (RackConfig::TYPES as $type) {
            foreach ($limits['heights'] as $h) {
                foreach ($limits['depths'] as $d) {
                    $part = $parts->get(RackPart::keyFor(RackPart::KIND_FRAME, $h, $d, null, $type));
                    $prices[RackPart::KIND_FRAME][$type]["{$h}x{$d}"] = $part?->is_active ? $this->toDecimal($part->price_cents) : null;
                }
            }

            $board = $this->boardKind($type);
            foreach ($this->boardWidths($limits) as $w) {
                foreach ($this->boardDepths($limits) as $d) {
                    $part = $parts->get(RackPart::keyFor($board, null, $d, $w, $type));
                    $prices[$board][$type]["{$w}x{$d}"] = $part?->is_active ? $this->toDecimal($part->price_cents) : null;
                }
            }
        }
        $this->data['prices'] = $prices;
    }

    public function form(Schema $schema): Schema
    {
        $limits = $this->getStore()->rackConfigurator();

        $typeTabs = [];
        foreach (RackConfig::TYPES as $type) {
            $typeTabs[] = Tab::make(__("admin.configurator.nav.tab_{$type}"))
                ->icon(match ($type) {
                    RackConfig::TYPE_OFFICE => Heroicon::OutlinedComputerDesktop,
                    RackConfig::TYPE_WINE => Heroicon::OutlinedInboxStack,
                    default => Heroicon::OutlinedViewColumns,
                })
                ->schema($this->priceTables($limits, $type));
        }

        return $schema
            ->statePath('data')
            ->components([
                Tabs::make(__('admin.configurator.nav.label'))
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('admin.configurator.nav.tab_sizes'))
                            ->icon(Heroicon::OutlinedArrowsPointingOut)
                            ->schema($this->sizeFields()),

                        ...$typeTabs,

                        Tab::make(__('admin.configurator.nav.tab_parts'))
                            ->icon(Heroicon::OutlinedWrenchScrewdriver)
                            ->schema($this->partFields()),

                        Tab::make(__('admin.configurator.nav.tab_settings'))
                            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                            ->schema($this->settingsFields()),
                    ]),
            ]);
    }

    /** The sizes on offer — shared by every rack type; each type's tables decide which it sells. */
    private function sizeFields(): array
    {
        $size = function (string $name): TagsInput {
            [$min, $max] = self::SIZE_RANGES[$name];

            return TagsInput::make($name)
                ->label(__("admin.configurator.field.{$name}"))
                ->helperText(__("admin.configurator.help.{$name}", ['min' => $min, 'max' => $max]))
                ->placeholder(__('admin.configurator.field.add_size'))
                ->splitKeys(['Tab', ',', ' '])
                ->nestedRecursiveRules(['integer', "min:{$min}", "max:{$max}"])
                ->required();
        };

        return [
            Section::make(__('admin.configurator.section.offered'))
                ->description(__('admin.configurator.section_help.offered'))
                ->columns(2)
                ->schema([
                    $size('heights'),
                    $size('depths'),
                    $size('widths'),
                    $size('levels'),
                ]),

            Section::make(__('admin.configurator.section.boards'))
                ->description(__('admin.configurator.section_help.boards'))
                ->columns(3)
                ->schema([
                    TextInput::make('shelf_width_trim_cm')
                        ->label(__('admin.configurator.field.width_trim'))
                        ->helperText(__('admin.configurator.help.width_trim'))
                        ->numeric()->minValue(0)->maxValue(30)->required()->suffix('cm'),
                    TextInput::make('shelf_depth_trim_cm')
                        ->label(__('admin.configurator.field.depth_trim'))
                        ->helperText(__('admin.configurator.help.depth_trim'))
                        ->numeric()->minValue(0)->maxValue(30)->required()->suffix('cm'),
                    TextInput::make('shelf_thickness_mm')
                        ->label(__('admin.configurator.field.thickness'))
                        ->helperText(__('admin.configurator.help.thickness'))
                        ->numeric()->minValue(1)->maxValue(100)->required()->suffix('mm'),
                ]),
        ];
    }

    /** One rack type's frame table and board table. */
    private function priceTables(array $limits, string $type): array
    {
        $symbol = Money::symbol($this->getStore()->currency ?? 'EUR');
        $board = $this->boardKind($type);
        $widthTrim = $limits['shelf_width_trim_cm'];
        $depthTrim = $limits['shelf_depth_trim_cm'];

        $frameTable = View::make('filament.store-admin.components.rack-price-table')->viewData([
            'corner' => __('admin.configurator.table.frame_corner'),
            'rows' => array_map(fn (int $h) => ['key' => $h, 'label' => $h.' cm', 'sub' => null], $limits['heights']),
            'cols' => array_map(fn (int $d) => ['key' => $d, 'label' => $d.' cm', 'sub' => null], $limits['depths']),
            'path' => "data.prices.frame.{$type}",
            'symbol' => $symbol,
        ]);

        // Rows and columns read in the sizes the customer picks; the cell is
        // the real board, which is what the price book is keyed on.
        $boardTable = View::make('filament.store-admin.components.rack-price-table')->viewData([
            'corner' => __('admin.configurator.table.board_corner'),
            'rows' => array_map(fn (int $w) => [
                'key' => max(1, $w - $widthTrim),
                'label' => $w.' cm',
                'sub' => __('admin.configurator.table.board_size', ['cm' => max(1, $w - $widthTrim)]),
            ], $limits['widths']),
            'cols' => array_map(fn (int $d) => [
                'key' => max(1, $d - $depthTrim),
                'label' => $d.' cm',
                'sub' => __('admin.configurator.table.board_size', ['cm' => max(1, $d - $depthTrim)]),
            ], $limits['depths']),
            'path' => "data.prices.{$board}.{$type}",
            'symbol' => $symbol,
        ]);

        $boardHelp = match ($type) {
            RackConfig::TYPE_OFFICE => 'office_shelves',
            RackConfig::TYPE_WINE => 'trays',
            default => 'shelves',
        };

        return [
            Section::make(__('admin.configurator.section.frames'))
                ->description(__('admin.configurator.section_help.frames'))
                ->schema([$frameTable]),

            Section::make(__('admin.configurator.section.'.($board === RackPart::KIND_WINE_TRAY ? 'trays' : 'shelves')))
                ->description(__("admin.configurator.section_help.{$boardHelp}"))
                ->schema([$boardTable]),
        ];
    }

    private function partFields(): array
    {
        $rows = [
            Section::make(__('admin.configurator.nav.tab_parts'))
                ->description(__('admin.configurator.section_help.parts'))
                ->schema([]),
        ];

        foreach (RackPart::FLAT_KINDS as $kind) {
            $rows[] = Section::make(__("admin.configurator.part.{$kind}"))
                ->columns(2)
                ->schema([
                    $this->priceField("part_{$kind}", __('admin.shared.field.price')),
                    TextInput::make("part_sku_{$kind}")
                        ->label(__('admin.configurator.field.sku'))
                        ->maxLength(60),
                ]);
        }

        return $rows;
    }

    private function settingsFields(): array
    {
        return [
            Section::make(__('admin.configurator.section.trade'))
                ->description(__('admin.configurator.section_help.trade'))
                ->columns(2)
                ->schema([
                    TextInput::make('vat_rate')
                        ->label(__('admin.configurator.field.vat_rate'))
                        ->helperText(__('admin.configurator.help.vat_rate'))
                        ->numeric()->step('0.01')->minValue(0)->maxValue(100)->required()
                        ->suffix('%'),
                    TextInput::make('max_length_cm')
                        ->label(__('admin.configurator.field.max_length'))
                        ->helperText(__('admin.configurator.help.max_length'))
                        ->numeric()->step(10)->minValue(80)->maxValue(10000)->required()
                        ->suffix('cm'),
                    TextInput::make('over_limit_text')
                        ->label(__('admin.configurator.field.over_limit_text'))
                        ->helperText(__('admin.configurator.help.over_limit_text'))
                        ->maxLength(300)
                        ->columnSpanFull(),
                ]),

            // Which category pages carry the button into the configurator.
            Section::make(__('admin.configurator.section.tab'))
                ->description(__('admin.configurator.section_help.tab'))
                ->schema([
                    Select::make('tab_category_ids')
                        ->label(__('admin.configurator.field.tab_categories'))
                        ->multiple()
                        ->searchable()
                        ->options(fn () => Category::query()
                            ->where('tenant_id', $this->getStore()->tenant_id)
                            ->with('parent')
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => ($c->parent ? $c->parent->name.' › ' : '').$c->name])
                            ->all()),
                ]),

            // How the office and wine models are built. Starting values were
            // read off the product photos; they are the merchant's to correct.
            Section::make(__('admin.configurator.section.models'))
                ->description(__('admin.configurator.section_help.models'))
                ->columns(2)
                ->schema([
                    Select::make('model_depth_cm')
                        ->label(__('admin.configurator.field.model_depth'))
                        ->helperText(__('admin.configurator.help.model_depth'))
                        ->options(fn () => collect($this->getStore()->rackConfigurator()['depths'])->mapWithKeys(fn (int $d) => [$d => $d.' cm'])->all())
                        ->required()
                        ->native(false)
                        ->selectablePlaceholder(false),
                    TextInput::make('desk_height_cm')
                        ->label(__('admin.configurator.field.desk_height'))
                        ->helperText(__('admin.configurator.help.desk_height'))
                        ->numeric()->minValue(40)->maxValue(120)->required()->suffix('cm'),
                    TextInput::make('tray_rim_cm')
                        ->label(__('admin.configurator.field.tray_rim'))
                        ->helperText(__('admin.configurator.help.tray_rim'))
                        ->numeric()->minValue(1)->maxValue(20)->required()->suffix('cm'),
                    TextInput::make('tray_tilt_deg')
                        ->label(__('admin.configurator.field.tray_tilt'))
                        ->helperText(__('admin.configurator.help.tray_tilt'))
                        ->numeric()->minValue(0)->maxValue(45)->required()->suffix('°'),
                ]),
        ];
    }

    /**
     * The money idiom used everywhere in this panel: stored in minor units,
     * edited as a decimal, prefixed with the store's own currency symbol.
     */
    private function priceField(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->step('0.01')
            ->minValue(0)
            ->prefix(fn () => Money::symbol(auth()->user()?->tenant?->store?->currency ?? 'EUR'));
    }

    public function save(): void
    {
        $store = $this->getStore();
        $before = $store->rackConfigurator();

        // Read the tables before the form: getState() only knows its own fields.
        $prices = (array) ($this->data['prices'] ?? []);
        $state = $this->form->getState();

        $invalid = 0;
        array_walk_recursive($prices, function ($value) use (&$invalid) {
            if ($value !== null && $value !== '' && (! is_numeric($value) || (float) $value < 0)) {
                $invalid++;
            }
        });
        if ($invalid > 0) {
            Notification::make()->danger()->title(__('admin.configurator.notify.invalid', ['count' => $invalid]))->send();

            return;
        }

        // --- the price book, at the sizes the tables were drawn with ----
        // Before the settings change, because a changed trim then moves these
        // rows along with every other board (retrimShelves, below).
        foreach (RackConfig::TYPES as $type) {
            foreach ($before['heights'] as $h) {
                foreach ($before['depths'] as $d) {
                    $this->upsert($store, RackPart::KIND_FRAME, $type, [
                        'height_cm' => $h,
                        'depth_cm' => $d,
                        'width_cm' => null,
                    ], $prices[RackPart::KIND_FRAME][$type]["{$h}x{$d}"] ?? null, RackPart::skuFor(RackPart::KIND_FRAME, $type, $h, $d));
                }
            }

            $board = $this->boardKind($type);
            foreach ($this->boardWidths($before) as $w) {
                foreach ($this->boardDepths($before) as $d) {
                    $this->upsert($store, $board, $type, [
                        'height_cm' => null,
                        'depth_cm' => $d,
                        'width_cm' => $w,
                    ], $prices[$board][$type]["{$w}x{$d}"] ?? null, RackPart::skuFor($board, $type, $w, $d));
                }
            }
        }

        foreach (RackPart::FLAT_KINDS as $kind) {
            $this->upsert($store, $kind, null, [
                'height_cm' => null,
                'depth_cm' => null,
                'width_cm' => null,
            ], $state["part_{$kind}"] ?? null, $state["part_sku_{$kind}"] ?? null);
        }

        // --- settings ------------------------------------------------
        $settings = (array) ($store->rack_configurator ?? []);
        $settings['enabled'] = true;
        foreach (array_keys(self::SIZE_RANGES) as $name) {
            $settings[$name] = $this->sizeList($state[$name] ?? [], $before[$name]);
        }
        $settings['vat_rate_bp'] = (int) round(((float) ($state['vat_rate'] ?? 0)) * 100);
        $settings['max_length_cm'] = (int) ($state['max_length_cm'] ?? 2500);
        $settings['shelf_width_trim_cm'] = (int) ($state['shelf_width_trim_cm'] ?? 3);
        $settings['shelf_depth_trim_cm'] = (int) ($state['shelf_depth_trim_cm'] ?? 1);
        $settings['shelf_thickness_mm'] = (int) ($state['shelf_thickness_mm'] ?? 18);
        $settings['over_limit_text'] = trim((string) ($state['over_limit_text'] ?? ''));
        $settings['desk_height_cm'] = (int) ($state['desk_height_cm'] ?? 75);
        unset($settings['desk_overhang_cm']); // no longer a setting: the customer's plate decides
        $settings['tray_rim_cm'] = (int) ($state['tray_rim_cm'] ?? 5);
        $settings['tray_tilt_deg'] = (int) ($state['tray_tilt_deg'] ?? 15);
        $settings['model_depth_cm'] = (int) ($state['model_depth_cm'] ?? 40);
        $settings['tab_category_ids'] = array_values(array_unique(array_map('intval', (array) ($state['tab_category_ids'] ?? []))));
        $store->update(['rack_configurator' => $settings]);

        /*
         | A CHANGED TRIM MOVES EVERY BOARD ROW.
         |
         | Board rows are keyed by the REAL board size — 97 x 59, not the 100 x
         | 60 bay it fills — and that size is the bay minus the trim. Change the
         | trim from 3 to 4 because the supplier changed the cut, and every
         | stored row is suddenly keyed to a size nothing looks up: prices
         | stranded, the tables reloading blank, and the storefront throwing
         | "no shelf price" on every quote.
         |
         | So the rows move with the setting. The price the merchant typed
         | belongs to that board, whatever it is now called.
         */
        $this->retrimShelves(
            $store,
            $before,
            $settings['shelf_width_trim_cm'],
            $settings['shelf_depth_trim_cm'],
        );

        Notification::make()->success()->title(__('admin.configurator.notify.saved'))->send();

        // Redraw: a size added or removed adds or removes a row in every table.
        $this->mount();
    }

    /** A size list as typed: whole numbers, each once, in order. Nothing valid keeps what was there. */
    private function sizeList(array $typed, array $fallback): array
    {
        $out = [];
        foreach ($typed as $value) {
            if (is_numeric($value) && (int) $value > 0) {
                $out[(int) $value] = (int) $value;
            }
        }
        $out = array_values($out);
        sort($out);

        return $out ?: $fallback;
    }

    /**
     * Re-key the tenant's board rows when a trim changes, so a price the
     * merchant typed for a board follows that board to its new size.
     */
    private function retrimShelves(Store $store, array $before, int $newW, int $newD): void
    {
        $oldW = $before['shelf_width_trim_cm'];
        $oldD = $before['shelf_depth_trim_cm'];
        if ($oldW === $newW && $oldD === $newD) {
            return;
        }

        // Every board kind of every rack type is keyed by the trimmed size.
        $rows = RackPart::forTenant($store->tenant_id)->whereIn('kind', RackPart::BOARD_KINDS)->get();

        foreach ($rows as $row) {
            // Back to the bay this board came from, then forward again on the
            // new trim. A row that does not correspond to any offered bay is
            // left alone rather than guessed at.
            $bayW = (int) $row->width_cm + $oldW;
            $bayD = (int) $row->depth_cm + $oldD;

            if (! in_array($bayW, $before['widths'], true) || ! in_array($bayD, $before['depths'], true)) {
                continue;
            }

            $w = max(1, $bayW - $newW);
            $d = max(1, $bayD - $newD);
            $row->update([
                'width_cm' => $w,
                'depth_cm' => $d,
                'sku' => RackPart::skuFor($row->kind, $row->rack_type, $w, $d),
            ]);
        }
    }

    /**
     * A blank price means "we do not sell this size" — the row is deactivated
     * rather than saved at zero, because a rack priced at nothing is a bug
     * waiting to be ordered.
     */
    private function upsert(Store $store, string $kind, ?string $type, array $dims, $price, ?string $sku): void
    {
        $part = RackPart::firstOrNew(array_merge(['tenant_id' => $store->tenant_id, 'kind' => $kind, 'rack_type' => $type], $dims));

        $blank = $price === null || $price === '';
        $cents = $blank ? 0 : (int) round(((float) $price) * 100);

        if ($blank && ! $part->exists) {
            return; // never sold, never created
        }

        $part->price_cents = $cents;
        $part->is_active = ! $blank;
        if ($sku !== null && $sku !== '' && blank($part->sku)) {
            $part->sku = $sku;
        }
        $part->save();
    }

    /** The board a rack type's levels are made of: the wine rack's trays, everyone else's shelves. */
    private function boardKind(string $type): string
    {
        return RackConfig::modelBoardKind($type) ?? RackPart::KIND_SHELF;
    }

    /** The real board widths that follow from the offered bays. */
    private function boardWidths(array $limits): array
    {
        return array_values(array_unique(array_map(
            fn (int $w) => max(1, $w - $limits['shelf_width_trim_cm']),
            $limits['widths']
        )));
    }

    private function boardDepths(array $limits): array
    {
        return array_values(array_unique(array_map(
            fn (int $d) => max(1, $d - $limits['shelf_depth_trim_cm']),
            $limits['depths']
        )));
    }

    private function toDecimal(?int $cents): ?string
    {
        return $cents === null ? null : number_format($cents / 100, 2, '.', '');
    }

    protected function getStore(): Store
    {
        $tenant = auth()->user()->tenant;

        return $tenant->store ?? $tenant->store()->create([]);
    }
}
