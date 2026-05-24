<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use SoftDeletes;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';
    public const TYPE_FREE_SHIPPING = 'free_shipping';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'value',
        'min_subtotal_cents',
        'starts_at',
        'ends_at',
        'usage_limit',
        'per_customer_limit',
        'times_used',
        'is_auto',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_auto' => 'boolean',
        'is_active' => 'boolean',
        'value' => 'integer',
        'min_subtotal_cents' => 'integer',
        'usage_limit' => 'integer',
        'per_customer_limit' => 'integer',
        'times_used' => 'integer',
    ];

    protected static function booted(): void
    {
        // Normalize codes to uppercase so customer-typed lowercase
        // matches operator-typed mixed-case. Stored in canonical form;
        // lookups also uppercase before comparing.
        static::saving(function (self $d) {
            if ($d->code) {
                $d->code = strtoupper(trim($d->code));
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Whether the discount is currently usable independent of cart
     * conditions (active flag + within validity window + under usage
     * cap). Per-customer + per-subtotal checks happen separately at
     * apply time since they need cart/customer context.
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $now = now();
        if ($this->starts_at && $this->starts_at->isAfter($now)) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->isBefore($now)) {
            return false;
        }
        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) {
            return false;
        }
        return true;
    }

    public function meetsMinimum(int $subtotalCents): bool
    {
        return $this->min_subtotal_cents === null
            || $subtotalCents >= $this->min_subtotal_cents;
    }

    /**
     * Compute the amount (in cents) this discount takes off given a
     * cart's subtotal + shipping. Returns 0 when the discount doesn't
     * apply or yields nothing. Capped so we never refund more than the
     * thing being discounted.
     */
    public function amountOff(int $subtotalCents, int $shippingCents): int
    {
        if (! $this->isValid() || ! $this->meetsMinimum($subtotalCents)) {
            return 0;
        }
        return match ($this->type) {
            self::TYPE_PERCENTAGE   => min($subtotalCents, (int) round($subtotalCents * ($this->value / 100))),
            self::TYPE_FIXED        => min($subtotalCents, (int) $this->value),
            self::TYPE_FREE_SHIPPING => $shippingCents,
            default                 => 0,
        };
    }
}
