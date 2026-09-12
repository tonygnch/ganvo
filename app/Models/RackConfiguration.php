<?php

namespace App\Models;

use App\Services\Rack\RackConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Session;
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
        'owner_token',
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

    /**
     * Who may reuse this row on a later save.
     *
     * The session, hashed — see the add_owner migration. Anonymous customers
     * are all this platform has on a storefront, and a session is as close to
     * "the same person, still here" as it gets. If there is no session at all
     * (console, a queued job) the answer is null, which matches nothing, so a
     * save from there creates its own row rather than adopting somebody's.
     */
    public static function ownerToken(): ?string
    {
        $id = (string) Session::getId();

        return $id === '' ? null : hash('sha256', $id);
    }

    /**
     * The row this save should reuse instead of writing another, or null.
     *
     * Three conditions, all of them load-bearing. Same tenant, obviously. Same
     * OWNER, because a configuration is one person's drawing and a stranger
     * rebuilding the same shape must never be handed it. And the same price,
     * because reusing a row whose quote has moved would mean either editing
     * what somebody was already shown or handing back a code that no longer
     * describes their money.
     *
     * Height, depth and levels are matched in SQL; the segment list is
     * compared in PHP, because it is a JSON column and asking two different
     * database engines to agree on JSON equality is a worse bet than reading
     * back the handful of rows that share the other three.
     */
    public static function reusableFor(int $tenantId, ?string $owner, RackConfig $config, array $snapshot): ?self
    {
        if ($owner === null) {
            return null;
        }

        return static::query()
            ->where('tenant_id', $tenantId)
            ->where('owner_token', $owner)
            ->where('height_cm', $config->heightCm)
            ->where('depth_cm', $config->depthCm)
            ->where('levels', $config->levels)
            ->latest('id')
            ->limit(50)
            ->get()
            ->first(fn (self $c) => $c->segmentWidths() === $config->segments
                && $c->matchesSnapshot($snapshot));
    }

    /**
     * Is this row already exactly what we just priced?
     *
     * Only the money is compared. The bill of materials and the totals are both
     * derived from the geometry and the price book, so two rows of the same
     * rack that cost the same are the same row — and if the merchant has moved
     * a price since, this returns false and the caller writes a NEW row rather
     * than editing what somebody was already shown.
     */
    public function matchesSnapshot(array $snapshot): bool
    {
        foreach (['subtotal_cents', 'vat_cents', 'total_cents', 'vat_rate_bp'] as $column) {
            if ((int) $this->{$column} !== (int) ($snapshot[$column] ?? -1)) {
                return false;
            }
        }

        return (string) $this->currency === (string) ($snapshot['currency'] ?? '');
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
