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
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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
