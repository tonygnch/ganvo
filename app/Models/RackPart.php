<?php

namespace App\Models;

use App\Services\Rack\RackConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One priceable component of a modular rack. See the create migration for why
 * these are not products.
 *
 * Which dimensions a row carries depends on its kind — a frame is height ×
 * depth, a board (shelf, wine tray) is its REAL width × depth (97 × 59, not
 * the nominal 100 × 60), and the three fastener kinds have no dimensions at all.
 *
 * Frames and boards belong to a rack type: the plain, office and wine racks
 * are priced separately. Fasteners are shared and carry no type.
 */
class RackPart extends Model
{
    public const KIND_FRAME = 'frame';

    public const KIND_SHELF = 'shelf';

    /** The wine rack's board: a shelf with a raised rim, in place of every shelf. */
    public const KIND_WINE_TRAY = 'wine_tray';

    /**
     * The office rack's desk plate. NOT a row of its own: the desk is a deeper
     * shelf, priced from the office rack's shelf table at the depth the
     * customer chose. The kind exists so the bill of materials can say „Плот за бюро".
     */
    public const KIND_DESK_TOP = 'desk_top';

    public const KIND_END_PIN = 'end_pin';

    public const KIND_EXTENSION_PIN = 'extension_pin';

    public const KIND_CROSS_BRACE = 'cross_brace';

    /** The kinds that are a single row rather than a grid — shared by every rack type. */
    public const FLAT_KINDS = [
        self::KIND_END_PIN,
        self::KIND_EXTENSION_PIN,
        self::KIND_CROSS_BRACE,
    ];

    /** Board rows in the price book, all keyed by real width × depth. */
    public const BOARD_KINDS = [
        self::KIND_SHELF,
        self::KIND_WINE_TRAY,
    ];

    /** Board rows only one rack model uses — see RackConfig::modelBoardKind(). */
    public const MODEL_KINDS = [
        self::KIND_WINE_TRAY,
    ];

    /** The kinds priced per rack type. */
    public const TYPED_KINDS = [
        self::KIND_FRAME,
        self::KIND_SHELF,
        self::KIND_WINE_TRAY,
    ];

    public const KINDS = [
        self::KIND_FRAME,
        self::KIND_SHELF,
        self::KIND_WINE_TRAY,
        self::KIND_END_PIN,
        self::KIND_EXTENSION_PIN,
        self::KIND_CROSS_BRACE,
    ];

    protected $fillable = [
        'tenant_id',
        'kind',
        'rack_type',
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

    protected static function booted(): void
    {
        // A frame or board always belongs to a rack type; one written without
        // one is the type that kind naturally belongs to.
        static::saving(function (self $part) {
            if (in_array($part->kind, self::TYPED_KINDS, true) && blank($part->rack_type)) {
                $part->rack_type = self::defaultTypeFor($part->kind);
            }
        });
    }

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

    /** The rack type a kind belongs to when none is given; null for the shared fasteners. */
    public static function defaultTypeFor(string $kind): ?string
    {
        return match ($kind) {
            self::KIND_WINE_TRAY => RackConfig::TYPE_WINE,
            self::KIND_FRAME, self::KIND_SHELF => RackConfig::TYPE_SINGLE,
            default => null,
        };
    }

    /**
     * The lookup key the price book indexes on: kind, rack type and whichever
     * dimensions that kind actually uses. The fasteners key on the kind alone.
     */
    public function lookupKey(): string
    {
        return self::keyFor($this->kind, $this->height_cm, $this->depth_cm, $this->width_cm, $this->rack_type);
    }

    public static function keyFor(string $kind, ?int $height = null, ?int $depth = null, ?int $width = null, ?string $type = null): string
    {
        $type = $type ?: self::defaultTypeFor($kind);

        return match ($kind) {
            self::KIND_FRAME => sprintf('frame:%s:%d:%d', $type, $height, $depth),
            self::KIND_SHELF, self::KIND_WINE_TRAY => sprintf('%s:%s:%d:%d', $kind, $type, $width, $depth),
            default => $kind,
        };
    }

    /**
     * The catalogue number a new row is given: FRAME-210-60, SHELF-97-59,
     * WINE-TRAY-97-59 — with the rack type in it when the row is not the
     * kind's natural type (FRAME-OFFICE-210-60), so the three price lists can
     * be told apart on a cutting list.
     */
    public static function skuFor(string $kind, ?string $type, int $a, int $b): string
    {
        $type = $type ?: self::defaultTypeFor($kind);
        $typePart = $type === self::defaultTypeFor($kind) ? '' : '-'.strtoupper($type);

        return sprintf('%s%s-%d-%d', strtoupper(str_replace('_', '-', $kind)), $typePart, $a, $b);
    }
}
