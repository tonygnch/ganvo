<?php

namespace App\Filament\StoreAdmin\Pages;

use App\Models\RackPart;
use App\Models\Store;
use App\Services\Money;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The rack configurator's price book, as the merchant sees it.
 *
 * The client's requirement (S19, S21) is blunt: no price, size or limit may
 * live in code, and changing one must not need a developer. So this page is
 * the frame and shelf price tables laid out the way their own supplier list
 * is — heights down, depths across — plus the three fasteners and the handful
 * of settings that govern what the configurator will offer at all.
 *
 * A Page rather than a Resource because this is one fixed matrix, not a list
 * to browse: a Resource would paginate thirty-five near-identical rows and
 * make "put 3% on every frame" a thirty-five-click job. RackPartResource is
 * the companion for the rarer work — adding a size, retiring one, editing SKUs.
 */
class RackConfigurator extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.store-admin.pages.rack-configurator';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 30;

    public ?array $data = [];

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
        ];

        foreach ($limits['heights'] as $h) {
            foreach ($limits['depths'] as $d) {
                $part = $parts->get(RackPart::keyFor(RackPart::KIND_FRAME, $h, $d));
                $data["frame_{$h}_{$d}"] = $this->toDecimal($part?->price_cents);
                $data["frame_on_{$h}_{$d}"] = $part ? (bool) $part->is_active : true;
            }
        }

        foreach ($this->shelfWidths($limits) as $w) {
            foreach ($this->shelfDepths($limits) as $d) {
                $part = $parts->get(RackPart::keyFor(RackPart::KIND_SHELF, null, $d, $w));
                $data["shelf_{$w}_{$d}"] = $this->toDecimal($part?->price_cents);
                $data["shelf_on_{$w}_{$d}"] = $part ? (bool) $part->is_active : true;
            }
        }

        foreach (RackPart::FLAT_KINDS as $kind) {
            $part = $parts->get($kind);
            $data["part_{$kind}"] = $this->toDecimal($part?->price_cents);
            $data["part_sku_{$kind}"] = $part?->sku;
            $data["part_on_{$kind}"] = $part ? (bool) $part->is_active : true;
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        $store = $this->getStore();
        $limits = $store->rackConfigurator();

        return $schema
            ->statePath('data')
            ->components([
                Tabs::make(__('admin.configurator.nav.label'))
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('admin.configurator.nav.tab_frames'))
                            ->icon(Heroicon::OutlinedViewColumns)
                            ->schema($this->frameGrid($limits)),

                        Tab::make(__('admin.configurator.nav.tab_shelves'))
                            ->icon(Heroicon::OutlinedBars3BottomLeft)
                            ->schema($this->shelfGrid($limits)),

                        Tab::make(__('admin.configurator.nav.tab_parts'))
                            ->icon(Heroicon::OutlinedWrenchScrewdriver)
                            ->schema($this->partFields()),

                        Tab::make(__('admin.configurator.nav.tab_settings'))
                            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                            ->schema($this->settingsFields()),
                    ]),
            ]);
    }

    /** Heights down the page, depths across — the merchant's own list (S18). */
    private function frameGrid(array $limits): array
    {
        $out = [];
        foreach ($limits['heights'] as $h) {
            $cells = [];
            foreach ($limits['depths'] as $d) {
                $cells[] = $this->priceField("frame_{$h}_{$d}", __('admin.configurator.field.depth_cm', ['cm' => $d]))
                    ->columnSpan(2);
                $cells[] = Toggle::make("frame_on_{$h}_{$d}")
                    ->label(__('admin.shared.field.active'))
                    ->inline(false)
                    ->columnSpan(1);
            }

            /*
             | Three columns per depth, not two. A price and its on/off switch
             | are not the same size of thing: splitting the width evenly left
             | the box too narrow for "20,45" beside the currency symbol, and a
             | merchant opening their own price book read 20,4 with the 5 cut
             | off the end.
             */
            $out[] = Section::make(__('admin.configurator.section.frame_height', ['cm' => $h]))
                ->columns(count($limits['depths']) * 3)
                ->schema($cells)
                ->collapsible();
        }

        return $out;
    }

    /** Real board sizes, not nominal bays: 97 × 59, the thing in the stack (S17). */
    private function shelfGrid(array $limits): array
    {
        $out = [];
        foreach ($this->shelfWidths($limits) as $w) {
            $cells = [];
            foreach ($this->shelfDepths($limits) as $d) {
                $cells[] = $this->priceField("shelf_{$w}_{$d}", __('admin.configurator.field.depth_cm', ['cm' => $d]))
                    ->columnSpan(2);
                $cells[] = Toggle::make("shelf_on_{$w}_{$d}")
                    ->label(__('admin.shared.field.active'))
                    ->inline(false)
                    ->columnSpan(1);
            }

            $out[] = Section::make(__('admin.configurator.section.shelf_width', ['cm' => $w]))
                ->columns(count($this->shelfDepths($limits)) * 3)
                ->schema($cells)
                ->collapsible();
        }

        return $out;
    }

    private function partFields(): array
    {
        $rows = [];
        foreach (RackPart::FLAT_KINDS as $kind) {
            $rows[] = Section::make(__("admin.configurator.part.{$kind}"))
                ->columns(3)
                ->schema([
                    $this->priceField("part_{$kind}", __('admin.shared.field.price')),
                    TextInput::make("part_sku_{$kind}")
                        ->label(__('admin.configurator.field.sku'))
                        ->maxLength(60),
                    Toggle::make("part_on_{$kind}")
                        ->label(__('admin.shared.field.active'))
                        ->inline(false),
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

            Section::make(__('admin.configurator.section.sizes'))
                ->description(__('admin.configurator.section_help.sizes'))
                ->columns(3)
                ->schema([
                    TextInput::make('shelf_width_trim_cm')
                        ->label(__('admin.configurator.field.width_trim'))
                        ->helperText(__('admin.configurator.help.width_trim'))
                        ->numeric()->minValue(0)->maxValue(30)->required()->suffix('cm'),
                    TextInput::make('shelf_depth_trim_cm')
                        ->label(__('admin.configurator.field.depth_trim'))
                        ->numeric()->minValue(0)->maxValue(30)->required()->suffix('cm'),
                    TextInput::make('shelf_thickness_mm')
                        ->label(__('admin.configurator.field.thickness'))
                        ->helperText(__('admin.configurator.help.thickness'))
                        ->numeric()->minValue(1)->maxValue(100)->required()->suffix('mm'),
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
        $limits = $store->rackConfigurator();
        $state = $this->form->getState();

        // --- settings ------------------------------------------------
        $settings = (array) ($store->rack_configurator ?? []);
        $settings['enabled'] = true;
        $settings['vat_rate_bp'] = (int) round(((float) ($state['vat_rate'] ?? 0)) * 100);
        $settings['max_length_cm'] = (int) ($state['max_length_cm'] ?? 2500);
        $settings['shelf_width_trim_cm'] = (int) ($state['shelf_width_trim_cm'] ?? 3);
        $settings['shelf_depth_trim_cm'] = (int) ($state['shelf_depth_trim_cm'] ?? 1);
        $settings['shelf_thickness_mm'] = (int) ($state['shelf_thickness_mm'] ?? 18);
        $settings['over_limit_text'] = trim((string) ($state['over_limit_text'] ?? ''));
        $store->update(['rack_configurator' => $settings]);

        /*
         | A CHANGED TRIM MOVES EVERY SHELF ROW.
         |
         | Shelf rows are keyed by the REAL board size — 97 x 59, not the 100 x
         | 60 bay it fills — and that size is the bay minus the trim. Change the
         | trim from 3 to 4 because the supplier changed the cut, and every
         | stored row is suddenly keyed to a size nothing looks up: twelve
         | prices stranded, the grid reloading blank, and the storefront
         | throwing "no shelf price" on every quote.
         |
         | So the rows move with the setting. The price the merchant typed
         | belongs to that board, whatever it is now called.
         */
        $this->retrimShelves(
            $store,
            $limits['shelf_width_trim_cm'],
            $limits['shelf_depth_trim_cm'],
            $settings['shelf_width_trim_cm'],
            $settings['shelf_depth_trim_cm'],
        );

        // --- the price book ------------------------------------------
        foreach ($limits['heights'] as $h) {
            foreach ($limits['depths'] as $d) {
                $this->upsert($store, RackPart::KIND_FRAME, [
                    'height_cm' => $h,
                    'depth_cm' => $d,
                    'width_cm' => null,
                ], $state["frame_{$h}_{$d}"] ?? null, (bool) ($state["frame_on_{$h}_{$d}"] ?? true), sprintf('FRAME-%d-%d', $h, $d));
            }
        }

        foreach ($this->shelfWidths($limits) as $w) {
            foreach ($this->shelfDepths($limits) as $d) {
                $this->upsert($store, RackPart::KIND_SHELF, [
                    'height_cm' => null,
                    'depth_cm' => $d,
                    'width_cm' => $w,
                ], $state["shelf_{$w}_{$d}"] ?? null, (bool) ($state["shelf_on_{$w}_{$d}"] ?? true), sprintf('SHELF-%d-%d', $w, $d));
            }
        }

        foreach (RackPart::FLAT_KINDS as $kind) {
            $this->upsert($store, $kind, [
                'height_cm' => null,
                'depth_cm' => null,
                'width_cm' => null,
            ], $state["part_{$kind}"] ?? null, (bool) ($state["part_on_{$kind}"] ?? true), $state["part_sku_{$kind}"] ?? null);
        }

        Notification::make()->success()->title(__('admin.configurator.notify.saved'))->send();
    }

    /**
     * Re-key the tenant's shelf rows when a trim changes, so a price the
     * merchant typed for a board follows that board to its new size.
     */
    private function retrimShelves(Store $store, int $oldW, int $oldD, int $newW, int $newD): void
    {
        if ($oldW === $newW && $oldD === $newD) {
            return;
        }

        $limits = $store->rackConfigurator();
        $rows = RackPart::forTenant($store->tenant_id)->where('kind', RackPart::KIND_SHELF)->get();

        foreach ($rows as $row) {
            // Back to the bay this board came from, then forward again on the
            // new trim. A row that does not correspond to any offered bay is
            // left alone rather than guessed at.
            $bayW = (int) $row->width_cm + $oldW;
            $bayD = (int) $row->depth_cm + $oldD;

            if (! in_array($bayW, $limits['widths'], true) || ! in_array($bayD, $limits['depths'], true)) {
                continue;
            }

            $row->update([
                'width_cm' => max(1, $bayW - $newW),
                'depth_cm' => max(1, $bayD - $newD),
                'sku' => sprintf('SHELF-%d-%d', max(1, $bayW - $newW), max(1, $bayD - $newD)),
            ]);
        }
    }

    /**
     * A blank price means "we do not sell this size" — the row is deactivated
     * rather than saved at zero, because a rack priced at nothing is a bug
     * waiting to be ordered.
     */
    private function upsert(Store $store, string $kind, array $dims, $price, bool $active, ?string $sku): void
    {
        $part = RackPart::firstOrNew(array_merge(['tenant_id' => $store->tenant_id, 'kind' => $kind], $dims));

        $blank = $price === null || $price === '';
        $cents = $blank ? 0 : (int) round(((float) $price) * 100);

        if ($blank && ! $part->exists) {
            return; // never sold, never created
        }

        $part->price_cents = $cents;
        $part->is_active = $active && ! $blank;
        if ($sku !== null && $sku !== '' && blank($part->sku)) {
            $part->sku = $sku;
        }
        $part->save();
    }

    /** The real board widths that follow from the offered bays. */
    private function shelfWidths(array $limits): array
    {
        return array_values(array_unique(array_map(
            fn (int $w) => max(1, $w - $limits['shelf_width_trim_cm']),
            $limits['widths']
        )));
    }

    private function shelfDepths(array $limits): array
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
