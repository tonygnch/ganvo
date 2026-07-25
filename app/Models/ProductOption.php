<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One axis of choice on a product — "Length", "Width", "Treatment".
 * Its values are the options a shopper picks between; which COMBINATIONS
 * are purchasable is decided by the variants, not here.
 */
class ProductOption extends Model
{
    protected $fillable = ['product_id', 'name', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class)->orderBy('sort_order')->orderBy('id');
    }
}
