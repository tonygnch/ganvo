<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'price_cents',
        'currency',
        'stock_quantity',
        'image_path',
        'is_active',
        'price_unit',
        'spec_rows',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'spec_rows' => 'array',
    ];

    /**
     * The label/sub-line rows printed under this product's price, or NULL when
     * the merchant has written none and the theme's own rows should stand.
     *
     * EMPTY MEANS "NO OPINION", NOT "SHOW NOTHING". The two are tempting to
     * distinguish — null for untouched, [] for deliberately cleared — but the
     * form cannot express the difference: an empty repeater is the only thing
     * a merchant can leave behind, and Filament saves it as [], not null. So a
     * merchant who opened a product and pressed Save would have silently
     * blanked its rows. Hiding the block entirely is already a job the theme
     * switches do (Customize theme → the four spec rows), and it belongs there:
     * it is a store-wide decision, not a per-product one.
     *
     * Rows are trimmed and the entirely-blank ones dropped, so a repeater row
     * the merchant opened and abandoned does not print a ruled empty line.
     */
    public function specRows(): ?array
    {
        if (! is_array($this->spec_rows)) {
            return null;
        }

        $rows = [];
        foreach ($this->spec_rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($label === '' && $value === '') {
                continue;
            }
            $rows[] = ['label' => $label, 'value' => $value];
        }

        return $rows ?: null;
    }

    /**
     * The cheapest and dearest a shopper can ACTUALLY pay, as [min, max] cents,
     * or null when there is only one price to quote.
     *
     * The headline used to be products.price_cents, and on this catalogue that
     * is frequently a price nobody can buy at: twelve of the fifteen products
     * with variants carry a base BELOW their cheapest variant, so a board
     * advertised at 11.50 started at 18.00 once you picked a size. A range is
     * the honest version of one number.
     *
     * A variant with a null price inherits the product's, which is why the base
     * still feeds the calculation rather than being ignored. Null comes back
     * when there is nothing to range over — one variant, or several that all
     * cost the same — because "3.50 – 3.50" is worse than "3.50".
     */
    public function priceRange(): ?array
    {
        // Prefer the loaded relation: these are rendered once per card on a
        // listing page, and going back to the database for each would turn one
        // query into fifty.
        $variants = $this->relationLoaded('variants')
            ? $this->variants->where('is_active', true)
            : $this->activeVariants()->get();

        $prices = $variants
            ->map(fn ($v): int => (int) ($v->price_cents ?? $this->price_cents))
            ->filter(fn (int $cents): bool => $cents > 0);

        if ($prices->count() < 2) {
            return null;
        }

        $min = (int) $prices->min();
        $max = (int) $prices->max();

        return $min === $max ? null : [$min, $max];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * How this product's price is quoted.
     *
     * PIECE is the default and the behaviour every product had before units
     * existed: the price is the price. The other three say the price is per
     * unit of MEASURE, and a given size's price follows from its dimensions —
     * 8,20 per m² on a 0,29 × 0,77 m shelf is 1,83.
     *
     * DIMENSIONS says how many lengths a unit needs, which is also how many of
     * the product's axes have to parse as dimensions before a price can be
     * worked out.
     */
    public const UNIT_PIECE = 'piece';

    public const UNIT_SQM = 'sqm';

    public const UNIT_LM = 'lm';

    public const UNIT_CBM = 'cbm';

    public const UNIT_DIMENSIONS = [
        self::UNIT_PIECE => 0,
        self::UNIT_LM => 1,
        self::UNIT_SQM => 2,
        self::UNIT_CBM => 3,
    ];

    /** @return array<string, string> */
    public static function priceUnitOptions(): array
    {
        return [
            self::UNIT_PIECE => __('admin.products.unit.piece'),
            self::UNIT_SQM => __('admin.products.unit.sqm'),
            self::UNIT_LM => __('admin.products.unit.lm'),
            self::UNIT_CBM => __('admin.products.unit.cbm'),
        ];
    }

    /** The short suffix a price carries on the storefront: „/ м²". */
    public function priceUnitSuffix(): string
    {
        return $this->price_unit === self::UNIT_PIECE
            ? ''
            : (string) __('site.units.'.$this->price_unit);
    }

    /** „м²" on its own — the suffix carries a leading slash for prices. */
    public function priceUnitShort(): string
    {
        return trim(str_replace('/', '', $this->priceUnitSuffix()));
    }

    public function isPricedByMeasure(): bool
    {
        return ($this->price_unit ?? self::UNIT_PIECE) !== self::UNIT_PIECE;
    }

    /** How many dimensions this product's unit needs. */
    public function unitDimensions(): int
    {
        return self::UNIT_DIMENSIONS[$this->price_unit ?? self::UNIT_PIECE] ?? 0;
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /** Curated merchandising groupings the operator has placed this product into. */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /** Gallery extras (primary image stays on $this->image_path). */
    public function gallery(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /** All variant rows (incl. inactive — use for admin). */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    /**
     * Active variants only — what the storefront should show. Wrapped
     * as a method rather than a scope so callers can eager-load via
     * `with('variants')` and still filter cheaply in PHP if needed.
     */
    public function activeVariants()
    {
        return $this->variants()->where('is_active', true);
    }

    public function hasVariants(): bool
    {
        return $this->activeVariants()->exists();
    }

    /** Choice axes for this product, in merchant-defined order. */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Everything the storefront picker needs to let a shopper choose across
     * several interacting axes, in one payload:
     *
     *   options[]           the axes, each with its ordered values
     *   combos[]            variant_id => [option_id => value_id], one entry
     *                       per PURCHASABLE combination
     *   variants[]          variant_id => label/price/stock for live updates
     *
     * The picker never needs a rule engine: to decide whether "10 cm" is
     * still selectable it asks whether any combo matches the shopper's other
     * selections. A 200 cm board with no 10 cm variant therefore greys 10 cm
     * out automatically — nothing has to be configured to forbid it.
     *
     * Returns an empty options list for products that never defined any, so
     * callers can fall back to the flat variant picker.
     */
    public function optionMatrix(): array
    {
        $options = $this->relationLoaded('options')
            ? $this->options
            : $this->options()->with('values')->get();

        if ($options->isEmpty()) {
            return ['options' => [], 'combos' => [], 'variants' => []];
        }

        $variants = $this->activeVariants()->with('optionValues')->get();

        $combos = [];
        $meta = [];
        foreach ($variants as $variant) {
            $pairs = [];
            foreach ($variant->optionValues as $value) {
                $pairs[(int) $value->pivot->product_option_id] = (int) $value->id;
            }

            // A variant that is not pinned on every axis cannot be resolved
            // unambiguously from the picker, so it is not offered at all.
            if (count($pairs) !== $options->count()) {
                continue;
            }

            $combos[(int) $variant->id] = $pairs;
            $meta[(int) $variant->id] = [
                'label' => $variant->label,
                'price_cents' => $variant->effectivePriceCents(),
                'stock' => (int) $variant->stock_quantity,
            ];
        }

        return [
            'options' => $options->map(fn (ProductOption $o) => [
                'id' => (int) $o->id,
                'name' => $o->name,
                'values' => $o->values->map(fn (ProductOptionValue $v) => [
                    'id' => (int) $v->id,
                    'value' => $v->value,
                ])->values()->all(),
            ])->values()->all(),
            'combos' => $combos,
            'variants' => $meta,
        ];
    }

    /** True when this product chooses via interacting axes rather than a flat list. */
    public function hasOptionMatrix(): bool
    {
        return $this->options()->exists();
    }

    /**
     * Every image for the product as a unified collection: primary
     * first (when set), then gallery rows in sort order. Each item is
     * an associative array with `url` + `alt` so views don't have to
     * branch between primary-vs-extra. Empty collection when the
     * product has no images at all.
     *
     * @return Collection<int, array{url: string, alt: string}>
     */
    public function allImages(): Collection
    {
        $out = collect();
        if ($this->image_path) {
            $out->push([
                'url' => Storage::url($this->image_path),
                'alt' => $this->name,
            ]);
        }
        foreach ($this->gallery as $img) {
            $out->push([
                'url' => $img->url(),
                'alt' => $img->alt_text ?: $this->name,
            ]);
        }

        return $out;
    }
}
