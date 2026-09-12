<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'rack_configuration_id',
        'product_name',
        'variant_label',
        'unit_price_cents',
        'quantity',
        'subtotal_cents',
        'measure_quantity',
        'measure_unit', ];

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
     * The saved configuration this line was built from, when it is a rack.
     * Nullable and nullOnDelete: the authoritative record of what was ordered
     * is {@see rackItems()}, frozen below, not this live link.
     */
    public function rackConfiguration(): BelongsTo
    {
        return $this->belongsTo(RackConfiguration::class);
    }

    /** The frozen bill of materials, when this line is a configured rack. */
    public function rackItems(): HasMany
    {
        return $this->hasMany(OrderRackItem::class)->orderBy('sort_order');
    }

    /**
     * Does this line have a frozen parts list to show?
     *
     * NOT `rack_configuration_id !== null`. That column is nullOnDelete, so a
     * merchant tidying up the saved-configurations screen used to blank the
     * cutting list on every order built from that drawing — the order_rack_items
     * rows survived the delete exactly as designed, and then nothing could
     * reach them, because every render gated on the link rather than on the
     * parts. The authoritative record is the frozen rows; ask them.
     */
    public function isRack(): bool
    {
        return $this->relationLoaded('rackItems')
            ? $this->rackItems->isNotEmpty()
            : $this->rackItems()->exists();
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
