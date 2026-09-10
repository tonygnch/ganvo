<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One priceable component of a modular rack. See the create migration for why
 * these are not products.
 *
 * Which dimensions a row carries depends on its kind — a frame is height ×
 * depth, a shelf is its REAL width × depth (97 × 59, not the nominal 100 × 60),
 * and the three fastener kinds have no dimensions at all.
 */
class RackPart extends Model
{
    public const KIND_FRAME = 'frame';

    public const KIND_SHELF = 'shelf';

    public const KIND_END_PIN = 'end_pin';

    public const KIND_EXTENSION_PIN = 'extension_pin';

    public const KIND_CROSS_BRACE = 'cross_brace';

    /** The kinds that are a single row rather than a grid. */
    public const FLAT_KINDS = [
        self::KIND_END_PIN,
        self::KIND_EXTENSION_PIN,
        self::KIND_CROSS_BRACE,
    ];

    public const KINDS = [
        self::KIND_FRAME,
        self::KIND_SHELF,
        self::KIND_END_PIN,
        self::KIND_EXTENSION_PIN,
        self::KIND_CROSS_BRACE,
    ];

    protected $fillable = [
        'tenant_id',
        'kind',
        'sku',
        'height_cm',
        'depth_cm',
        'width_cm',
        'price_cents',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'height_cm' => 'integer',
        'depth_cm' => 'integer',
        'width_cm' => 'integer',
        'price_cents' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The lookup key the price book indexes on: kind plus whichever dimensions
     * that kind actually uses. Dimensionless kinds key on the kind alone.
     */
    public function lookupKey(): string
    {
        return self::keyFor($this->kind, $this->height_cm, $this->depth_cm, $this->width_cm);
    }

    public static function keyFor(string $kind, ?int $height = null, ?int $depth = null, ?int $width = null): string
    {
        return match ($kind) {
            self::KIND_FRAME => sprintf('frame:%d:%d', $height, $depth),
            self::KIND_SHELF => sprintf('shelf:%d:%d', $width, $depth),
            default => $kind,
        };
    }
}
