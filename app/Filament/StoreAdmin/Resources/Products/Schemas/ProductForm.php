<?php

namespace App\Filament\StoreAdmin\Resources\Products\Schemas;

use App\Models\ProductOptionValue;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
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
                Section::make('Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => $get('slug')
                                ? null
                                : $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText('URL fragment. Auto-filled from name.'),
                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Pricing & inventory')
                    ->description('Prices are in your store\'s base currency, set in Store Settings.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price_cents')
                            ->label('Price')
                            ->required()
                            ->numeric()
                            ->step('0.01')
                            ->prefix(fn () => \App\Services\Money::symbol(
                                auth()->user()?->tenant?->store?->currency ?? 'EUR'
                            ))
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, '.', '') : null)
                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                        TextInput::make('stock_quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ]),

                Section::make('Images')
                    ->description('The primary image shows on cards, in the cart, and as the main image on the product page. Gallery extras appear as thumbnails next to it; the customer can click a thumb to swap it into the main slot.')
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Primary image')
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
                            ->label('Gallery extras')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['alt_text'] ?? null)
                            ->addActionLabel('Add image')
                            ->reorderableWithDragAndDrop()
                            ->defaultItems(0)
                            ->schema([
                                FileUpload::make('path')
                                    ->label('Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products/gallery')
                                    ->maxSize(2048)
                                    ->imageEditor()
                                    ->required(),
                                TextInput::make('alt_text')
                                    ->label('Alt text')
                                    ->maxLength(160)
                                    ->helperText('For screen readers + SEO. Optional but recommended.'),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Options')
                    ->description('Only for products where the choices interact — a 100 см board sold in 10 and 20 см widths, a 200 см one in 20 and 30. Declare each axis here; which pairings you actually sell is decided below, under Variants. Leave empty for a plain list of variants, or for a single-SKU product.')
                    ->schema([
                        // Bound to the options() hasMany, with the values as a
                        // nested relationship repeater — Filament saves those
                        // once the parent option row has an id, so a new axis
                        // and its values land in the same submit.
                        Repeater::make('options')
                            ->label('')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->addActionLabel('Add option')
                            ->reorderableWithDragAndDrop()
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Option')
                                    ->required()
                                    ->maxLength(60)
                                    ->live(onBlur: true)
                                    ->placeholder('e.g. Дължина')
                                    ->helperText('The axis the customer chooses on. Order matters — it is the order the picker shows, and the order the variant label reads in.'),
                                Repeater::make('values')
                                    ->label('Values')
                                    ->relationship()
                                    ->orderColumn('sort_order')
                                    ->itemLabel(fn (array $state): ?string => $state['value'] ?? null)
                                    ->addActionLabel('Add value')
                                    ->reorderableWithDragAndDrop()
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->schema([
                                        TextInput::make('value')
                                            ->label('')
                                            ->required()
                                            ->maxLength(60)
                                            ->placeholder('e.g. 100 см')
                                            ->distinct(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make(fn ($record): string => static::hasSavedOptions($record) ? 'Combinations' : 'Variants')
                    ->description(fn ($record): string => static::hasSavedOptions($record)
                        ? 'One row per pairing you actually sell, each with its own SKU, price and stock.'
                        : 'Optional. Add purchasable variations (e.g. sizes, colors). When a product has variants, the customer must pick one — they buy the variant, not the bare product. Leave empty for single-SKU products.')
                    ->schema([
                        // The mechanism, spelled out: there is no rule anywhere
                        // that forbids 200 см × 10 см. It is unbuyable purely
                        // because no row below pairs them.
                        Callout::make('A pairing you do not add here cannot be bought')
                            ->description('There is no separate list of forbidden combinations — absence is the rule. If 200 см never comes in 10 см, simply never add that row, and the customer will find 10 см greyed out the moment they pick 200 см.')
                            ->info()
                            ->visible(fn ($record): bool => static::hasSavedOptions($record)),

                        // Combination rows point at option values by id, so the
                        // axes have to be stored before they can be paired.
                        Callout::make('Save the product to start pairing')
                            ->description('You have declared options above but not saved them yet. Save now — the rows here then turn into one dropdown per option. The same applies to values added since the last save: they become selectable after saving.')
                            ->warning()
                            ->visible(fn (Get $get, $record): bool => filled($get('options')) && ! static::hasSavedOptions($record)),

                        // Bound to the variants() hasMany. Each row is
                        // one ProductVariant; sort_order is auto-synced
                        // from the repeater drag order.
                        Repeater::make('variants')
                            ->label('')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel(fn ($record): string => static::hasSavedOptions($record) ? 'Add combination' : 'Add variant')
                            ->reorderableWithDragAndDrop()
                            ->defaultItems(0)
                            // Same relation, two shapes: a free-text label when
                            // the product has no axes, one Select per axis when
                            // it has.
                            ->schema(fn ($record): array => static::hasSavedOptions($record)
                                ? static::combinationFields(static::savedOptions($record))
                                : static::flatVariantFields())
                            ->mutateRelationshipDataBeforeFillUsing(fn (array $data): array => static::hydrateCombinationRow($data))
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
                                        $fail('Two rows describe the same combination. Each pairing may only appear once — delete the duplicate, or change one of its options.');

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

                Section::make('Categories')
                    ->description('Pick one or more categories this product belongs to. Customers browse by these on the storefront.')
                    ->schema([
                        Select::make('categories')
                            ->label('')
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

                Section::make('Visibility')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Visible in storefront')
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
    protected static function hasSavedOptions($record): bool
    {
        return (bool) $record?->exists && $record->options()->exists();
    }

    /** @return EloquentCollection<int, \App\Models\ProductOption> */
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
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    protected static function flatVariantFields(): array
    {
        return [
            TextInput::make('label')
                ->label('Label')
                ->required()
                ->maxLength(255)
                ->placeholder('e.g. Small / Red')
                ->helperText('Shown to the customer in the picker.'),
            ...static::variantStockFields(),
        ];
    }

    /**
     * One Select per axis. `label` becomes derived rather than typed: the cart,
     * the order lines and the confirmation e-mails all print it, so it has to
     * follow the selection instead of drifting away from it.
     *
     * @param  EloquentCollection<int, \App\Models\ProductOption>  $options
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    protected static function combinationFields(EloquentCollection $options): array
    {
        $valueTexts = [];
        $optionFields = [];

        foreach ($options as $option) {
            $optionFields[] = static::OPTION_FIELD_PREFIX . $option->id;

            foreach ($option->values as $value) {
                $valueTexts[(int) $value->id] = $value->value;
            }
        }

        $fields = [];

        foreach ($options as $option) {
            $fields[] = Select::make(static::OPTION_FIELD_PREFIX . $option->id)
                ->label($option->name)
                ->options($option->values->pluck('value', 'id')->all())
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

    /** @return array<int, \Filament\Schemas\Components\Component> */
    protected static function variantStockFields(): array
    {
        return [
            TextInput::make('sku')
                ->label('SKU')
                ->maxLength(120)
                ->placeholder('Optional, your inventory code.'),
            TextInput::make('price_cents')
                ->label('Price override')
                ->numeric()
                ->step('0.01')
                ->prefix(fn () => \App\Services\Money::symbol(
                    auth()->user()?->tenant?->store?->currency ?? 'EUR'
                ))
                ->helperText('Leave empty to use the product price.')
                // Same cent ↔ display dance as the
                // product-level price field above.
                ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, '.', '') : null)
                ->dehydrateStateUsing(fn ($state) => ($state === null || $state === '') ? null : (int) round(((float) $state) * 100)),
            TextInput::make('stock_quantity')
                ->label('Stock')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
            Toggle::make('is_active')
                ->label('Active')
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
    protected static function hydrateCombinationRow(array $data): array
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
            $data[static::OPTION_FIELD_PREFIX . $optionId] = (int) $valueId;
        }

        return $data;
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
