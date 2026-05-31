<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A tenant-scoped product category. Self-references for nesting via
 * parent_id; one level is rendered in the storefront nav today but
 * the schema supports deeper trees.
 *
 * Slug is auto-derived from name on save if not explicitly set.
 */
class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'parent_id',
        'image_path',
        'sort_order',
        'is_active',
        'show_in_menu',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_menu' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // Auto-slug from name if the operator left slug blank. Doesn't
        // overwrite an explicit slug — that's the operator's call.
        static::saving(function (self $cat) {
            if (empty($cat->slug) && ! empty($cat->name)) {
                $cat->slug = Str::slug($cat->name);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }
}
