<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One choice on an axis — "100 cm" on Length. A value existing does NOT mean
 * it is buyable in every combination: that depends on whether a variant pairs
 * it with the shopper's other selections.
 */
class ProductOptionValue extends Model
{
    protected $fillable = ['product_option_id', 'value', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_option_values',
            'product_option_value_id',
            'product_variant_id'
        );
    }
}
