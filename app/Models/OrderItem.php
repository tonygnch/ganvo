<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_label',
        'unit_price_cents',
        'quantity',
        'subtotal_cents',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Display name shown on receipts + admin order views: the product
     * name with the variant label appended when set. Reads from the
     * snapshotted columns so it survives variant deletion.
     */
    public function displayName(): string
    {
        return $this->variant_label
            ? sprintf('%s — %s', $this->product_name, $this->variant_label)
            : (string) $this->product_name;
    }
}
