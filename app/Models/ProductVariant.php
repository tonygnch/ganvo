<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
