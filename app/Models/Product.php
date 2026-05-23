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
