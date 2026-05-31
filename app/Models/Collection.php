<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Curated grouping of products. Unlike Categories (taxonomic), a
 * Collection is purely a merchandising tool — operator picks the
 * exact products and the order they appear in. A product can sit in
 * any number of collections.
 */
class Collection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'description',
        'banner_path',
        'sort_order',
        'is_featured',
        'is_active',
        'show_in_menu',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'show_in_menu' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // Auto-slug from title on save when blank. Operator can still
        // override to anything URL-safe — kept in sync per tenant by
        // the (tenant_id, slug) unique index.
        static::saving(function (self $col) {
            if (! $col->slug && $col->title) {
                $col->slug = Str::slug($col->title);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('collection_product.sort_order')
            ->orderBy('products.name');
    }

    /** Resolved public URL for the banner, or null when unset. */
    public function bannerUrl(): ?string
    {
        return $this->banner_path ? Storage::url($this->banner_path) : null;
    }
}
