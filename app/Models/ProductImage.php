<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A gallery-extra image attached to a product. The product's primary
 * image stays on products.image_path; rows here are the additional
 * thumbnails shown on the product detail page.
 */
class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'path',
        'alt_text',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Public URL for the image (uses the public disk). */
    public function url(): string
    {
        return Storage::url($this->path);
    }
}
