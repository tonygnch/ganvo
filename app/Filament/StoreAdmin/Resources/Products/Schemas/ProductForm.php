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
                        TextInput::make('price_cents')
                            ->label(__('admin.shared.field.price'))
                            ->required()
                            ->numeric()
                            ->step('0.01')
                            ->prefix(fn () => \App\Services\Money::symbol(
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
                    ->description(__('admin.products.section_help.options'))
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
                                            ->label('')
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
                            ->label('')
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

                Section::make(__('admin.products.section.categories'))
                    ->description(__('admin.products.section_help.categories'))
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

                Section::make(__('admin.shared.section.visibility'))
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('admin.products.field.is_active'))
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
            $optionId = (int) $option->id;

            $fields[] = Select::make(static::OPTION_FIELD_PREFIX . $optionId)
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

    /** @return array<int, \Filament\Schemas\Components\Component> */
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
                ->prefix(fn () => \App\Services\Money::symbol(
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
            $data[static::OPTION_FIELD_PREFIX . $optionId] = (int) $valueId;
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
            $field = static::OPTION_FIELD_PREFIX . $option->id;

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
