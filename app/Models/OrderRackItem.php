<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a configured rack's bill of materials, frozen onto the order.
 *
 * Everything here is snapshotted — label, SKU, sizes and price — for the same
 * reason order_items snapshots product_name: the packing list, the invoice and
 * the loading sheet must still read accurately after the part is renamed,
 * re-priced or retired.
 */
class OrderRackItem extends Model
{
    protected $fillable = [
        'order_item_id',
        'kind',
        'label',
        'sku',
        'height_cm',
        'depth_cm',
        'width_cm',
        'quantity',
        'unit_price_cents',
        'subtotal_cents',
        'sort_order',
    ];

    protected $casts = [
        'height_cm' => 'integer',
        'depth_cm' => 'integer',
        'width_cm' => 'integer',
        'quantity' => 'integer',
        'unit_price_cents' => 'integer',
        'subtotal_cents' => 'integer',
        'sort_order' => 'integer',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
