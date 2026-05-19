<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;

    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_YEARLY = 'yearly';

    public const PERIODS = [
        self::PERIOD_MONTHLY,
        self::PERIOD_YEARLY,
    ];

    protected $fillable = [
        'slug',
        'name',
        'tagline',
        'features',
        'currency',
        'price_monthly_cents',
        'price_yearly_cents',
        'is_popular',
        'is_active',
        'sort_order',
        'discount_percent',
        'discount_label',
        'discount_starts_at',
        'discount_ends_at',
    ];

    protected $casts = [
        'features' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'discount_starts_at' => 'datetime',
        'discount_ends_at' => 'datetime',
    ];

    /**
     * Whether a promo discount is currently in effect — non-zero percent AND
     * the current moment falls within the optional start/end window.
     */
    public function hasActiveDiscount(?CarbonInterface $at = null): bool
    {
        if (! $this->discount_percent || $this->discount_percent <= 0) {
            return false;
        }
        $at = $at ?: now();
        if ($this->discount_starts_at && $this->discount_starts_at->isAfter($at)) {
            return false;
        }
        if ($this->discount_ends_at && $this->discount_ends_at->isBefore($at)) {
            return false;
        }
        return true;
    }

    /** Pre-discount price for the given billing period. */
    public function priceCentsFor(string $period): int
    {
        return $period === self::PERIOD_YEARLY
            ? (int) $this->price_yearly_cents
            : (int) $this->price_monthly_cents;
    }

    /**
     * Effective price the merchant will be charged — applies the active
     * discount if one is in effect, else returns the listed price.
     */
    public function effectivePriceCentsFor(string $period): int
    {
        $base = $this->priceCentsFor($period);
        if (! $this->hasActiveDiscount()) {
            return $base;
        }
        return (int) round($base * (100 - (int) $this->discount_percent) / 100);
    }

    /** Yearly price normalized to a per-month figure (for "$X/mo billed yearly"). */
    public function yearlyAsMonthlyCents(): int
    {
        return (int) round($this->price_yearly_cents / 12);
    }

    /**
     * How many cents the merchant saves by paying yearly vs 12 monthly
     * charges. Negative or zero when yearly isn't actually a discount.
     */
    public function yearlySavingsCents(): int
    {
        return max(0, ($this->price_monthly_cents * 12) - $this->price_yearly_cents);
    }

    public function yearlySavingsPercent(): int
    {
        $yearlyAtMonthly = $this->price_monthly_cents * 12;
        if ($yearlyAtMonthly <= 0) {
            return 0;
        }
        return (int) round((($yearlyAtMonthly - $this->price_yearly_cents) / $yearlyAtMonthly) * 100);
    }

    /** Free plan = price 0 in both periods. Marketing label switches in the wizard. */
    public function isFree(): bool
    {
        return $this->price_monthly_cents === 0 && $this->price_yearly_cents === 0;
    }
}
