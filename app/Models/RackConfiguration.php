<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A rack somebody built and saved. Addressed publicly by {@see $code} at
 * /configurator/{code}, which is also the share link and the cart line key.
 *
 * The price columns are provenance, not truth — see the create migration.
 */
class RackConfiguration extends Model
{
    /**
     * No I, O, 0 or 1: these codes get read off a screen and typed back in,
     * or dictated over the phone to the yard.
     */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const CODE_LENGTH = 8;

    protected $fillable = [
        'tenant_id',
        'code',
        'height_cm',
        'depth_cm',
        'levels',
        'segments',
        'bom',
        'subtotal_cents',
        'vat_cents',
        'total_cents',
        'vat_rate_bp',
        'currency',
    ];

    protected $casts = [
        'segments' => 'array',
        'bom' => 'array',
        'height_cm' => 'integer',
        'depth_cm' => 'integer',
        'levels' => 'integer',
        'subtotal_cents' => 'integer',
        'vat_cents' => 'integer',
        'total_cents' => 'integer',
        'vat_rate_bp' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $config) {
            if (blank($config->code)) {
                $config->code = self::freshCode();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Public URL, resolved within the current storefront host. */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function segmentWidths(): array
    {
        return array_map('intval', (array) ($this->segments ?? []));
    }

    public function sectionCount(): int
    {
        return count($this->segmentWidths());
    }

    public function totalLengthCm(): int
    {
        return array_sum($this->segmentWidths());
    }

    /**
     * A code that is not already taken. Collisions are vanishingly unlikely at
     * 32^8, but "unlikely" is not "never" and the column is unique — so we
     * check rather than let a save blow up in a customer's face.
     */
    public static function freshCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /** Normalise a user-supplied code before looking it up. */
    public static function normaliseCode(string $raw): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $raw) ?? '');
    }
}
