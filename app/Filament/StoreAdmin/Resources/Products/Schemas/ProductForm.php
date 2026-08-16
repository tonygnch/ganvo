<?php

namespace App\Filament\StoreAdmin\Resources\Products\Schemas;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Services\Money;
use App\Support\Dimension;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductForm
{
    /**
     * A combination row carries one Select per axis, named after the option it
     * belongs to. Nothing on ProductVariant is called `option_*`, so the prefix
     * is how the save hooks tell a pinned axis apart from a real column.
     */
    public const OPTION_FIELD_PREFIX = 'option_';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.products.section.details'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.shared.field.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => $get('slug')
                                ? null
                                : $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->label(__('admin.shared.field.slug'))
                            ->required()
                            ->maxLength(255)
                            ->helperText(__('admin.products.help.slug')),
                        Textarea::make('description')
                            ->label(__('admin.shared.field.description'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('admin.products.section.pricing'))
                    ->description(__('admin.products.section_help.pricing'))
                    ->columns(2)
                    ->schema([
                        Select::make('price_unit')
                            ->label(__('admin.products.field.price_unit'))
                            ->options(Product::priceUnitOptions())
                            ->default(Product::UNIT_PIECE)
                            ->selectablePlaceholder(false)
                            ->native(false)
                            ->live()
                            ->helperText(__('admin.products.help.price_unit')),
                        TextInput::make('price_cents')
                            ->label(__('admin.shared.field.price'))
                            ->required()
                            ->numeric()
                            ->step('0.01')
                            ->prefix(fn () => Money::symbol(
                                auth()->user()?->tenant?->store?->currency ?? 'EUR'
                            ))
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, '.', '') : null)
                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                        TextInput::make('stock_quantity')
                            ->label(__('admin.products.field.stock_quantity'))
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ]),

                Section::make(__('admin.products.section.images'))
                    ->description(__('admin.products.section_help.images'))
                    ->schema([
                        FileUpload::make('image_path')
                            ->label(__('admin.products.field.primary_image'))
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->columnSpanFull(),

                        // Gallery extras as a Repeater bound to the
                        // gallery() hasMany relation. Each row is one
                        // ProductImage. orderColumn auto-syncs the
                        // repeater's drag order to the sort_order
                        // column so the order in the editor matches
                        // the storefront.
                        Repeater::make('gallery')
                            ->label(__('admin.products.field.gallery'))
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['alt_text'] ?? null)
                            ->addActionLabel(__('admin.products.action.add_image'))
                            ->reorderableWithDragAndDrop()
                            ->defaultItems(0)
                            ->schema([
                                FileUpload::make('path')
                                    ->label(__('admin.shared.field.image'))
                                    ->image()
                                    ->disk('public')
                                    ->directory('products/gallery')
                                    ->maxSize(2048)
                                    ->imageEditor()
                                    ->required(),
                                TextInput::make('alt_text')
                                    ->label(__('admin.products.field.alt_text'))
                                    ->maxLength(160)
                                    ->helperText(__('admin.products.help.alt_text')),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make(__('admin.products.section.options'))
                    ->key('options-section')
                    ->description(__('admin.products.section_help.options'))
                    /*
                     | PASTE THE AXES INSTEAD OF CLICKING THEM.
                     |
                     | Every value is a repeater row with one box in it, so a
                     | product with three lengths and four widths costs seven
                     | "add" clicks and seven fields before it says anything —
                     | and a merchant arriving with a catalogue has that list
                     | already written down. This takes the list.
                     |
                     | Merges rather than replaces: an axis whose name is already
                     | there gains only the values it is missing, and nothing
                     | typed by hand is thrown away. Pressing it twice does
                     | nothing the first press did not.
                     */
                    ->headerActions([
                        Action::make('bulkOptions')
                            ->label(__('admin.products.action.bulk_options'))
                            ->icon('heroicon-m-clipboard-document-list')
                            ->modalWidth('lg')
                            ->modalSubmitActionLabel(__('admin.products.action.bulk_options_submit'))
                            ->schema([
                                Textarea::make('bulk')
                                    ->label(__('admin.products.field.bulk_options'))
                                    ->helperText(__('admin.products.help.bulk_options'))
                                    ->placeholder(__('admin.products.ph.bulk_options'))
                                    ->rows(9)
                                    ->required(),
                            ])
                            ->action(function (array $data, Get $get, Set $set): void {
                                // Parsed first, so a malformed paste changes nothing.
                                $parsed = static::parseBulkOptions((string) ($data['bulk'] ?? ''));

                                if ($parsed === []) {
                                    Notification::make()
                                        ->warning()
                                        ->title(__('admin.products.notify.bulk_options_none'))
                                        ->send();

                                    return;
                                }

                                $rows = Arr::wrap($get('options'));

                                // Index what is already there by name, so a
                                // second paste tops an axis up instead of
                                // adding a second one with the same label.
                                $byName = [];
                                foreach ($rows as $key => $row) {
                                    $name = mb_strtolower(trim((string) ($row['name'] ?? '')));
                                    if ($name !== '') {
                                        $byName[$name] = $key;
                                    }
                                }

                                $axes = 0;
                                $values = 0;

                                foreach ($parsed as $name => $incoming) {
                                    $key = $byName[mb_strtolower($name)] ?? null;

                                    if ($key === null) {
                                        $key = (string) Str::uuid();
                                        $rows[$key] = ['name' => $name, 'values' => []];
                                        $byName[mb_strtolower($name)] = $key;
                                        $axes++;
                                    }

                                    $existing = [];
                                    foreach (Arr::wrap($rows[$key]['values'] ?? []) as $v) {
                                        $existing[] = mb_strtolower(trim((string) ($v['value'] ?? '')));
                                    }

                                    foreach ($incoming as $value) {
                                        if (in_array(mb_strtolower($value), $existing, true)) {
                                            continue;
                                        }
                                        $rows[$key]['values'][(string) Str::uuid()] = ['value' => $value];
                                        $existing[] = mb_strtolower($value);
                                        $values++;
                                    }
                                }

                                $set('options', $rows);

                                Notification::make()
                                    ->success()
                                    ->title(__('admin.products.notify.bulk_options_added', [
                                        'axes' => $axes,
                                        'values' => $values,
                                    ]))
                                    ->send();
                            }),
                    ])
                    ->schema([
                        // Bound to the options() hasMany, with the values as a
                        // nested relationship repeater — Filament saves those
                        // once the parent option row has an id, so a new axis
                        // and its values land in the same submit.
                        Repeater::make('options')
                            // hiddenLabel() hides it VISUALLY but the accessible name remains, and
                            // an unset label is derived from the attribute in English —
                            // so a screen reader still needs a translated one.
                            ->label(__('admin.products.section.options'))
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->addActionLabel(__('admin.products.action.add_option'))
                            ->reorderableWithDragAndDrop()
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('admin.products.field.option'))
                                    ->required()
                                    ->maxLength(60)
                                    ->live(onBlur: true)
                                    ->placeholder(__('admin.products.ph.option'))
                                    ->helperText(__('admin.products.help.option')),
                                Repeater::make('values')
                                    ->label(__('admin.products.field.values'))
                                    ->relationship()
                                    ->orderColumn('sort_order')
                                    ->itemLabel(fn (array $state): ?string => $state['value'] ?? null)
                                    ->addActionLabel(__('admin.products.action.add_value'))
                                    ->reorderableWithDragAndDrop()
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->schema([
                                        TextInput::make('value')
                                            ->label(__('admin.products.field.values'))
                                            ->hiddenLabel()
                                            ->required()
                                            ->maxLength(60)
                                            ->placeholder(__('admin.products.ph.value'))
                                            ->distinct(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make(fn ($record): string => static::hasSavedOptions($record)
                    ? __('admin.products.section.combinations')
                    : __('admin.products.section.variants'))
                    ->description(fn ($record): string => static::hasSavedOptions($record)
                        ? __('admin.products.section_help.combinations')
                        : __('admin.products.section_help.variants'))
                    /*
                     | FILL THE MATRIX IN ONE CLICK.
                     |
                     | Every combination the axes allow is already implied by
                     | the axes; typing them out again is transcription, not a
                     | decision. Four widths and seven lengths is 28 rows, each
                     | needing two dropdowns before it says anything — and a
                     | real catalogue wants most of them. So the merchant gets
                     | the whole grid and deletes what they do not sell, which
                     | is a handful of clicks instead of a hundred.
                     |
                     | Rows already present are kept untouched: this ADDS the
                     | missing combinations, so pressing it twice is safe and
                     | pressing it after adding an axis fills in only the new
                     | pairings.
                     |
                     | Prices are left unset on purpose. A variant with no
                     | price override sells at the product's price
                     | (ProductVariant::effectivePriceCents), so an unedited row
                     | is never wrong — where a prefilled copy of the base price
                     | would look deliberate and quietly ship at the wrong
                     | number for every size that differs.
                     */
                    ->headerActions([
                        /*
                         | REPRICE WHAT IS ALREADY THERE.
                         |
                         | Switching a product to per-m² does not touch rows
                         | that already carry a typed price — an override wins
                         | over the product price, by design. Without this, a
                         | page quoting 8,20 €/м² sells a 20 m² order for
                         | whatever the old per-piece numbers happened to add up
                         | to, and the arithmetic visibly does not close.
                         |
                         | Separate from Generate on purpose: one adds rows, this
                         | one overwrites prices the merchant may have set by
                         | hand, and those should not happen behind a single
                         | click. Rows whose dimensions do not parse keep what
                         | they have and are reported.
                         */
                        Action::make('recalculatePrices')
                            ->label(__('admin.products.action.recalculate_prices'))
                            ->icon('heroicon-o-calculator')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->modalDescription(__('admin.products.help.recalculate_prices'))
                            ->visible(fn (Get $get, $record): bool => static::hasSavedOptions($record)
                                && (Product::UNIT_DIMENSIONS[$get('price_unit') ?? Product::UNIT_PIECE] ?? 0) > 0)
                            ->action(function (Get $get, Set $set, $record): void {
                                $options = static::savedOptions($record);
                                $valueTexts = [];
                                foreach ($options as $option) {
                                    foreach ($option->values as $value) {
                                        $valueTexts[(int) $value->id] = $value->value;
                                    }
                                }

                                $dimensions = Product::UNIT_DIMENSIONS[$get('price_unit') ?? Product::UNIT_PIECE] ?? 0;
                                $unitPrice = (int) round(((float) $get('price_cents')) * 100);

                                $rows = Arr::wrap($get('variants'));
                                $done = 0;
                                $skipped = 0;

                                foreach ($rows as $key => $row) {
                                    $selection = static::selectedValueIds(Arr::wrap($row));
                                    $measure = static::measureFor($selection, $valueTexts, $dimensions);

                                    if ($measure === null || $unitPrice <= 0) {
                                        $skipped++;

                                        continue;
                                    }

                                    $rows[$key]['price_cents'] = number_format(
                                        ((int) round($unitPrice * $measure)) / 100, 2, '.', ''
                                    );
                                    $done++;
                                }

                                $set('variants', $rows);

                                Notification::make()
                                    ->success()
                                    ->title(__('admin.products.notify.prices_recalculated', ['count' => $done]))
                                    ->body($skipped > 0
                                        ? __('admin.products.notify.prices_skipped', ['count' => $skipped])
                                        : null)
                                    ->send();
                            }),
                        Action::make('generateCombinations')
                            ->label(__('admin.products.action.generate_combinations'))
                            ->icon('heroicon-o-squares-2x2')
                            ->color('gray')
                            ->visible(fn ($record): bool => static::hasSavedOptions($record))
                            ->action(function (Get $get, Set $set, $record): void {
                                $options = static::savedOptions($record);

                                $axes = [];
                                $valueTexts = [];
                                foreach ($options as $option) {
                                    $ids = $option->values->pluck('id')->map(fn ($id): int => (int) $id)->all();
                                    if ($ids === []) {
                                        continue;
                                    }
                                    $axes[(int) $option->id] = $ids;
                                    foreach ($option->values as $value) {
                                        $valueTexts[(int) $value->id] = $value->value;
                                    }
                                }

                                if ($axes === []) {
                                    return;
                                }

                                // Every pairing the axes allow.
                                $combinations = [[]];
                                foreach ($axes as $optionId => $valueIds) {
                                    $next = [];
                                    foreach ($combinations as $partial) {
                                        foreach ($valueIds as $valueId) {
                                            $next[] = $partial + [$optionId => $valueId];
                                        }
                                    }
                                    $combinations = $next;
                                }

                                $rows = Arr::wrap($get('variants'));

                                // What the merchant already has, so a second
                                // press adds nothing and never duplicates.
                                $existing = [];
                                foreach ($rows as $row) {
                                    $selection = static::selectedValueIds(Arr::wrap($row));
                                    if ($selection !== []) {
                                        $existing[static::combinationSignature($selection)] = true;
                                    }
                                }

                                // Read the unit off the LIVE form, not the
                                // saved record: a merchant who has just switched
                                // to m² expects this press to use it.
                                $dimensions = Product::UNIT_DIMENSIONS[$get('price_unit') ?? Product::UNIT_PIECE] ?? 0;
                                $unitPrice = (int) round(((float) $get('price_cents')) * 100);

                                $added = 0;
                                $unpriced = 0;
                                foreach ($combinations as $selection) {
                                    if (isset($existing[static::combinationSignature($selection)])) {
                                        continue;
                                    }

                                    /*
                                     | A size priced by the metre already knows
                                     | what it costs — 8,20 per m² on 0,29 ×
                                     | 0,77 m is 1,83 — so the row arrives
                                     | priced. Unreadable dimensions leave the
                                     | price null (the product price stands) and
                                     | are counted for the notification.
                                     */
                                    $price = null;
                                    if ($dimensions > 0 && $unitPrice > 0) {
                                        $measure = static::measureFor($selection, $valueTexts, $dimensions);
                                        if ($measure === null) {
                                            $unpriced++;
                                        } else {
                                            /*
                                             | A DECIMAL, not cents. This row
                                             | goes into the repeater's form
                                             | state, and that field holds what
                                             | the merchant would type — its
                                             | dehydrate multiplies by 100 on
                                             | the way to the column. Handing it
                                             | cents priced 0,421 × 0,77 m at
                                             | 266,00 instead of 2,66.
                                             */
                                            $price = number_format(
                                                ((int) round($unitPrice * $measure)) / 100, 2, '.', ''
                                            );
                                        }
                                    }

                                    $row = [
                                        'label' => implode(' / ', array_map(
                                            fn (int $valueId): string => (string) ($valueTexts[$valueId] ?? ''),
                                            array_values($selection),
                                        )),
                                        'sku' => null,
                                        'price_cents' => $price,
                                        'stock_quantity' => $get('stock_quantity') ?? 0,
                                        'is_active' => true,
                                    ];
                                    foreach ($selection as $optionId => $valueId) {
                                        $row[static::OPTION_FIELD_PREFIX.$optionId] = $valueId;
                                    }

                                    $rows[(string) Str::uuid()] = $row;
                                    $added++;
                                }

                                $set('variants', $rows);

                                Notification::make()
                                    ->success()
                                    ->title($added > 0
                                        ? __('admin.products.notify.combinations_added', ['count' => $added])
                                        : __('admin.products.notify.combinations_none'))
                                    ->body($unpriced > 0
                                        ? __('admin.products.notify.combinations_unpriced', ['count' => $unpriced])
                                        : null)
                                    ->send();
                            }),
                    ])
                    ->schema([
                        // The mechanism, spelled out: there is no rule anywhere
                        // that forbids 200 см × 10 см. It is unbuyable purely
                        // because no row below pairs them.
                        Callout::make(__('admin.products.section.callout_absence'))
                            ->description(__('admin.products.section_help.callout_absence'))
                            ->info()
                            ->visible(fn ($record): bool => static::hasSavedOptions($record)),

                        // Combination rows point at option values by id, so the
                        // axes have to be stored before they can be paired.
                        Callout::make(__('admin.products.section.callout_save_first'))
                            ->description(__('admin.products.section_help.callout_save_first'))
                            ->warning()
                            ->visible(fn (Get $get, $record): bool => filled($get('options')) && ! static::hasSavedOptions($record)),

                        // An axis added after these rows were built covers
                        // none of them. Rather than opening every row with an
                        // empty required Select and no explanation — which used
                        // to make the product unsaveable — each row is given
                        // that axis's first value and told so here.
                        Callout::make(fn ($record): string => __('admin.products.section.callout_new_axis', [
                            'axes' => implode(' / ', static::axesMissingFromRows($record)),
                        ]))
                            ->description(__('admin.products.section_help.callout_new_axis'))
                            ->warning()
                            ->visible(fn ($record): bool => static::axesMissingFromRows($record) !== []),

                        // Bound to the variants() hasMany. Each row is
                        // one ProductVariant; sort_order is auto-synced
                        // from the repeater drag order.
                        Repeater::make('variants')
                            ->label(__('admin.products.section.variants'))
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel(fn ($record): string => static::hasSavedOptions($record)
                                ? __('admin.products.action.add_combination')
                                : __('admin.products.action.add_variant'))
                            ->reorderableWithDragAndDrop()
                            ->defaultItems(0)
                            // Same relation, two shapes: a free-text label when
                            // the product has no axes, one Select per axis when
                            // it has.
                            ->schema(fn ($record): array => static::hasSavedOptions($record)
                                ? static::combinationFields(static::savedOptions($record))
                                : static::flatVariantFields())
                            ->mutateRelationshipDataBeforeFillUsing(fn (array $data, $record): array => static::hydrateCombinationRow($data, static::savedOptions($record)))
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => static::normaliseCombinationRow($data))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => static::normaliseCombinationRow($data))
                            // Two rows pinned to the same values would compete
                            // for the same cell of the matrix and one of them
                            // could never be reached — a mistake worth saying
                            // out loud rather than silently persisting.
                            ->rule(static fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
                                $seen = [];

                                foreach (Arr::wrap($value) as $row) {
                                    $selection = self::selectedValueIds(Arr::wrap($row));

                                    if (! $selection) {
                                        continue;
                                    }

                                    $signature = implode('|', array_map(
                                        fn (int $optionId, int $valueId): string => "{$optionId}:{$valueId}",
                                        array_keys($selection),
                                        $selection,
                                    ));

                                    if (isset($seen[$signature])) {
                                        $fail(__('admin.products.notify.duplicate_combination'));

                                        return;
                                    }

                                    $seen[$signature] = true;
                                }
                            })
                            // Hidden while options are declared but unsaved, so
                            // the merchant cannot create variants that miss the
                            // new axis and would be dropped from the picker.
                            ->visible(fn (Get $get, $record): bool => static::hasSavedOptions($record) || blank($get('options')))
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                /*
                 | THE ROWS UNDER THE PRICE.
                 |
                 | These were the platform's own promises — free delivery,
                 | thirty-day returns, checkout "under a minute" — printed from
                 | lang strings on every product of every store. The merchant
                 | could switch a row off but never say anything else, so a yard
                 | delivering in three days and a florist delivering in three
                 | hours advertised the same sentence, and neither had written
                 | it.
                 |
                 | Collapsed by default: most products will never need this, and
                 | an expanded empty repeater on every product page reads as a
                 | field someone forgot to fill in.
                 */
                Section::make(__('admin.products.section.spec_rows'))
                    ->description(__('admin.products.section_help.spec_rows'))
                    ->collapsed()
                    ->schema([
                        Repeater::make('spec_rows')
                            ->label(__('admin.products.section.spec_rows'))
                            ->hiddenLabel()
                            // No default row. One would turn "I opened this
                            // product" into a saved override on every product
                            // the merchant so much as looks at.
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->schema([
                                TextInput::make('label')
                                    ->label(__('admin.products.field.spec_label'))
                                    ->placeholder(__('admin.products.placeholder.spec_label'))
                                    ->maxLength(60),
                                TextInput::make('value')
                                    ->label(__('admin.products.field.spec_value'))
                                    ->placeholder(__('admin.products.placeholder.spec_value'))
                                    ->maxLength(120),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('admin.products.section.categories'))
                    ->description(__('admin.products.section_help.categories'))
                    ->schema([
                        Select::make('categories')
                            ->label(__('admin.products.section.categories'))
                            ->hiddenLabel()
                            ->multiple()
                            ->relationship(
                                name: 'categories',
                                titleAttribute: 'name',
                                // Scope to the merchant's own categories.
                                modifyQueryUsing: fn ($query) => $query->where('tenant_id', auth()->user()?->tenant_id),
                            )
                            ->preload()
                            ->searchable(),
                    ]),

                Section::make(__('admin.shared.section.visibility'))
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('admin.products.field.is_active'))
                            ->default(true),
                        /* SHOWN and ORDERABLE are different questions, and
                           merchants were answering the second with the first —
                           hiding a product outright to stop people buying it,
                           which threw away the listing and the photograph to
                           switch off a button. */
                        Toggle::make('is_orderable')
                            ->label(__('admin.products.field.is_orderable'))
                            ->helperText(__('admin.products.help.is_orderable'))
                            ->default(true),
                    ]),
            ]);
    }

    /**
     * The chosen value per axis on a combination row, keyed by option id and
     * sorted so two rows describing the same pairing produce the same array.
     * Empty for a flat variant, which is what every caller branches on.
     *
     * @param  array<string, mixed>  $row
     * @return array<int, int>
     */
    public static function selectedValueIds(array $row): array
    {
        $selection = [];

        foreach ($row as $key => $value) {
            if (! str_starts_with((string) $key, static::OPTION_FIELD_PREFIX) || blank($value)) {
                continue;
            }

            $selection[(int) substr((string) $key, strlen(static::OPTION_FIELD_PREFIX))] = (int) $value;
        }

        ksort($selection);

        return $selection;
    }

    /** Axes the product has actually stored — only those have ids to pair on. */
    /**
     * The measure one combination represents, in the product's own unit — m²
     * for two dimensions, m³ for three, metres for one.
     *
     * Returns null when ANY axis fails to parse, rather than a partial figure:
     * half a measurement is not a smaller measurement, it is a wrong one, and
     * the caller leaves that price alone and says which value it could not
     * read.
     *
     * @param  array<int, int>  $selection  option id => value id
     * @param  array<int, string>  $valueTexts  value id => the text as typed
     */
    protected static function measureFor(array $selection, array $valueTexts, int $dimensions): ?float
    {
        if ($dimensions < 1 || count($selection) < $dimensions) {
            return null;
        }

        $lengths = [];
        foreach ($selection as $valueId) {
            $metres = Dimension::toMetres((string) ($valueTexts[$valueId] ?? ''));
            if ($metres === null) {
                return null;
            }
            $lengths[] = $metres;
        }

        // More axes than the unit needs (a colour beside width and length) is
        // normal — take the dimensions in axis order and ignore the rest.
        $lengths = array_slice($lengths, 0, $dimensions);

        if (count($lengths) < $dimensions) {
            return null;
        }

        return array_product($lengths);
    }

    /**
     * A stable fingerprint for one cell of the matrix, so "do we already have
     * this pairing?" is a string comparison rather than a nested loop. Sorted
     * by axis id, because two rows describing the same cell must produce the
     * same signature whatever order their Selects came back in.
     *
     * @param  array<int, int>  $selection  option id => value id
     */
    protected static function combinationSignature(array $selection): string
    {
        ksort($selection);

        $parts = [];
        foreach ($selection as $optionId => $valueId) {
            $parts[] = "{$optionId}:{$valueId}";
        }

        return implode('|', $parts);
    }

    /**
     * Read pasted axes into ['Axis name' => ['value', 'value', …]].
     *
     * ONE AXIS PER BLOCK, blocks separated by a blank line. The first line of a
     * block names the axis; a colon after the name is optional and anything
     * following it counts as values. Every other line in the block is a value,
     * as is anything separated from its neighbour by a semicolon.
     *
     * SEMICOLON AND NOT COMMA is why this is hand-rolled rather than an
     * explode(','). The sizes in this catalogue are Bulgarian decimals —
     * „0,29 м" — and splitting on commas would quietly turn every one of them
     * into two values, „0" and „29 м", on every axis.
     *
     * Order is preserved, blanks are dropped, and duplicates inside a block are
     * collapsed: this is a list someone typed, not a validated import.
     *
     * @return array<string, list<string>>
     */
    protected static function parseBulkOptions(string $input): array
    {
        $out = [];

        foreach (preg_split('/\R\s*\R/u', trim($input)) ?: [] as $block) {
            $lines = array_values(array_filter(
                array_map('trim', preg_split('/\R/u', trim($block)) ?: []),
                fn (string $l): bool => $l !== ''
            ));

            if ($lines === []) {
                continue;
            }

            $head = array_shift($lines);
            $name = $head;
            $inline = '';

            if (str_contains($head, ':')) {
                [$name, $inline] = array_map('trim', explode(':', $head, 2));
            }

            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $values = [];
            foreach (array_merge($inline === '' ? [] : [$inline], $lines) as $line) {
                foreach (explode(';', $line) as $piece) {
                    $piece = trim($piece);
                    if ($piece !== '' && ! in_array($piece, $values, true)) {
                        $values[] = $piece;
                    }
                }
            }

            if ($values !== []) {
                $out[$name] = array_merge($out[$name] ?? [], $values);
            }
        }

        return $out;
    }

    protected static function hasSavedOptions($record): bool
    {
        return (bool) $record?->exists && $record->options()->exists();
    }

    /** @return EloquentCollection<int, ProductOption> */
    protected static function savedOptions($record): EloquentCollection
    {
        return $record?->exists
            ? $record->options()->with('values')->get()
            : new EloquentCollection;
    }

    /**
     * The variant fields for a product with no axes: the merchant names the
     * variant himself. Unchanged behaviour for single-axis catalogues.
     *
     * @return array<int, Component>
     */
    protected static function flatVariantFields(): array
    {
        return [
            TextInput::make('label')
                ->label(__('admin.products.field.variant_label'))
                ->required()
                ->maxLength(255)
                ->placeholder(__('admin.products.ph.variant_label'))
                ->helperText(__('admin.products.help.variant_label')),
            ...static::variantStockFields(),
        ];
    }

    /**
     * One Select per axis. `label` becomes derived rather than typed: the cart,
     * the order lines and the confirmation e-mails all print it, so it has to
     * follow the selection instead of drifting away from it.
     *
     * @param  EloquentCollection<int, ProductOption>  $options
     * @return array<int, Component>
     */
    protected static function combinationFields(EloquentCollection $options): array
    {
        $valueTexts = [];
        $optionFields = [];

        foreach ($options as $option) {
            $optionFields[] = static::OPTION_FIELD_PREFIX.$option->id;

            foreach ($option->values as $value) {
                $valueTexts[(int) $value->id] = $value->value;
            }
        }

        $fields = [];

        foreach ($options as $option) {
            $optionId = (int) $option->id;

            $fields[] = Select::make(static::OPTION_FIELD_PREFIX.$optionId)
                ->label($option->name)
                ->options($option->values->pluck('value', 'id')->all())
                /*
                 | THE AXIS THE MERCHANT HAS JUST DELETED MUST STOP ASKING.
                 |
                 | These Selects are built from the axes in the DATABASE, and a
                 | row deleted from the Options repeater is not gone from the
                 | database until the save — the very save this field was
                 | blocking. Required, unanswerable, and unremovable: adding an
                 | axis to a product that already had combinations locked the
                 | product permanently, because the only way out was a save that
                 | could never pass.
                 |
                 | So the field also consults the LIVE state: an axis no longer
                 | declared above disappears from every row at once, and a
                 | hidden field is neither validated nor dehydrated, so the
                 | deletion finally reaches the database.
                 */
                ->visible(fn (Get $get): bool => static::axisStillDeclared($get, $optionId))
                ->required()
                ->live()
                // Keeps the collapsed row header readable while editing. The
                // save hook recomputes it server-side anyway — this is only so
                // the merchant sees "100 см / 20 см" before hitting Save.
                ->afterStateUpdated(function (Get $get, Set $set) use ($optionFields, $valueTexts): void {
                    $set('label', implode(' / ', array_filter(array_map(
                        fn (string $field): ?string => $valueTexts[(int) $get($field)] ?? null,
                        $optionFields,
                    ))));
                });
        }

        $fields[] = Hidden::make('label');

        return [...$fields, ...static::variantStockFields()];
    }

    /** @return array<int, Component> */
    protected static function variantStockFields(): array
    {
        return [
            TextInput::make('sku')
                ->label(__('admin.products.field.sku'))
                ->maxLength(120)
                ->placeholder(__('admin.products.ph.sku')),
            TextInput::make('price_cents')
                ->label(__('admin.products.field.price_override'))
                ->numeric()
                ->step('0.01')
                ->prefix(fn () => Money::symbol(
                    auth()->user()?->tenant?->store?->currency ?? 'EUR'
                ))
                ->helperText(__('admin.products.help.price_override'))
                // Same cent ↔ display dance as the
                // product-level price field above.
                ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, '.', '') : null)
                ->dehydrateStateUsing(fn ($state) => ($state === null || $state === '') ? null : (int) round(((float) $state) * 100)),
            TextInput::make('stock_quantity')
                ->label(__('admin.products.field.stock'))
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
            Toggle::make('is_active')
                ->label(__('admin.shared.field.active'))
                ->default(true),
        ];
    }

    /**
     * Replay a saved variant's pivot rows into the per-axis Selects — the
     * repeater only fills columns, and the pinned values are not columns.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function hydrateCombinationRow(array $data, ?EloquentCollection $options = null): array
    {
        if (blank($data['id'] ?? null)) {
            return $data;
        }

        // Straight at the pivot: the row wants option id => value id and
        // nothing else, and going through the relation would cost a second
        // query per variant.
        $pinned = DB::table('product_variant_option_values')
            ->where('product_variant_id', $data['id'])
            ->pluck('product_option_value_id', 'product_option_id');

        foreach ($pinned as $optionId => $valueId) {
            $data[static::OPTION_FIELD_PREFIX.$optionId] = (int) $valueId;
        }

        /*
         | An axis declared AFTER these rows were made has no pivot row for any
         | of them, so every one of them opened with an empty required Select
         | and the product could not be saved at all until all of them were
         | filled by hand — with nothing on screen saying so.
         |
         | The first value of the new axis is proposed instead. It is a
         | proposal, not a fact: it lands in the form only, the row header
         | immediately reads "2000 мм / 96 мм / 45 мм" so it is visible rather
         | than silent, and nothing reaches the database until the merchant
         | saves. A Callout above the rows says it has happened.
         */
        foreach ($options ?? [] as $option) {
            $field = static::OPTION_FIELD_PREFIX.$option->id;

            if (filled($data[$field] ?? null)) {
                continue;
            }

            if ($first = $option->values->first()) {
                $data[$field] = (int) $first->id;
            }
        }

        return $data;
    }

    /**
     * True unless the merchant has deleted this axis from the Options repeater
     * in the current, unsaved form state.
     *
     * Existing rows are keyed `record-<id>` and new ones by a browser uuid, so
     * both the key and an `id` in the row are accepted. If the state cannot be
     * read at all the answer is YES — never hide an axis on a guess, because
     * hiding one drops its selection from the save.
     */
    protected static function axisStillDeclared(Get $get, int $optionId): bool
    {
        $rows = $get('../../options');

        if (! is_array($rows)) {
            return true;
        }

        foreach ($rows as $key => $row) {
            if (is_string($key) && str_starts_with($key, 'record-')
                && (int) substr($key, 7) === $optionId) {
                return true;
            }

            if (is_array($row) && (int) ($row['id'] ?? 0) === $optionId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Axes that exist but which some saved combination has no value for — the
     * signature of an axis added after the rows were built.
     *
     * @return array<int, string>
     */
    protected static function axesMissingFromRows($record): array
    {
        if (! $record?->exists) {
            return [];
        }

        $variantIds = $record->variants()->pluck('id');

        if ($variantIds->isEmpty()) {
            return [];
        }

        $pinnedPerOption = DB::table('product_variant_option_values')
            ->whereIn('product_variant_id', $variantIds)
            ->selectRaw('product_option_id, COUNT(DISTINCT product_variant_id) AS covered')
            ->groupBy('product_option_id')
            ->pluck('covered', 'product_option_id');

        return static::savedOptions($record)
            ->filter(fn ($option): bool => (int) ($pinnedPerOption[$option->id] ?? 0) < $variantIds->count())
            ->pluck('name')
            ->all();
    }

    /**
     * Derive `label` from the pinned values and drop the Selects, which are not
     * ProductVariant columns. The selection itself is written to the pivot by
     * the page's afterCreate/afterSave hook, which reads the raw form state.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function normaliseCombinationRow(array $data): array
    {
        $selection = static::selectedValueIds($data);

        if (! $selection) {
            return $data;
        }

        // Regenerated rather than trusted: a value renamed since this row was
        // last opened would otherwise keep printing its old text on receipts.
        $data['label'] = static::labelForValues($selection) ?: ($data['label'] ?? '');

        return Arr::except($data, array_filter(
            array_keys($data),
            fn ($key): bool => str_starts_with((string) $key, static::OPTION_FIELD_PREFIX),
        ));
    }

    /**
     * "100 см / 20 см" — the values joined in axis order, which is the order
     * the picker presents them in.
     *
     * @param  array<int, int>  $selection
     */
    protected static function labelForValues(array $selection): string
    {
        return ProductOptionValue::query()
            ->join('product_options', 'product_options.id', '=', 'product_option_values.product_option_id')
            ->whereIn('product_option_values.id', array_values($selection))
            ->orderBy('product_options.sort_order')
            ->orderBy('product_options.id')
            ->pluck('product_option_values.value')
            ->implode(' / ');
    }
}
