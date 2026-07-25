<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'label',
        'sku',
        'price_cents',
        'stock_quantity',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price_cents' => 'integer',
        'stock_quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The option values this variant is pinned to — one per axis. The pivot
     * carries product_option_id so callers can group by axis without a second
     * query back through the values.
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_option_values',
            'product_variant_id',
            'product_option_value_id'
        )->withPivot('product_option_id');
    }

    /**
     * The effective price for this variant: its override when set,
     * otherwise the parent product's price. Returned in cents.
     */
    public function effectivePriceCents(): int
    {
        return $this->price_cents !== null
            ? (int) $this->price_cents
            : (int) $this->product->price_cents;
    }
}
