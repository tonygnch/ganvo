<?php

namespace App\Models;

use App\Support\Dimension;
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
    /**
     * How much of the product's unit ONE of these is — the area of a single
     * board in m², the length of one length in metres.
     *
     * Read from the dimensions already written on the axes (see Dimension), so
     * a merchant does not restate them. Null when the product is sold by the
     * piece, or when any axis does not parse: a customer asking for 20 m² of
     * something whose size we cannot read must be told, not quietly given a
     * guessed quantity.
     */
    public function measure(): ?float
    {
        $product = $this->product;
        $needed = $product?->unitDimensions() ?? 0;

        if ($needed < 1) {
            return null;
        }

        // Axis order, so "width then length" is taken in the order the merchant
        // declared rather than whatever the pivot returns.
        $values = $this->optionValues()
            ->join('product_options', 'product_options.id', '=', 'product_option_values.product_option_id')
            ->orderBy('product_options.sort_order')
            ->pluck('product_option_values.value');

        $lengths = [];
        foreach ($values as $text) {
            $metres = Dimension::toMetres((string) $text);
            if ($metres === null) {
                return null;
            }
            $lengths[] = $metres;
        }

        if (count($lengths) < $needed) {
            return null;
        }

        return array_product(array_slice($lengths, 0, $needed));
    }

    public function effectivePriceCents(): int
    {
        return $this->price_cents !== null
            ? (int) $this->price_cents
            : (int) $this->product->price_cents;
    }
}
