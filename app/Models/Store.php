<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Store extends Model
{
    public const CHECKOUT_GUEST = 'guest';
    public const CHECKOUT_ACCOUNT = 'account';
    public const CHECKOUT_BOTH = 'both';

    public const CHECKOUT_MODES = [
        self::CHECKOUT_GUEST => 'Guest checkout only',
        self::CHECKOUT_ACCOUNT => 'Account required',
        self::CHECKOUT_BOTH => 'Guest or account (recommended)',
    ];

    protected $fillable = [
        'tenant_id',
        'theme',
        'logo_path',
        'primary_color',
        'secondary_color',
        'font_family',
        'currency',
        'display_currencies',
        'fx_rates',
        'custom_domain',
        'custom_domain_verification_token',
        'custom_domain_verified_at',
        'theme_settings',
        'is_live',
        'checkout_mode',
        'allow_registration',
    ];

    protected $casts = [
        'theme_settings' => 'array',
        'display_currencies' => 'array',
        'fx_rates' => 'array',
        'is_live' => 'boolean',
        'custom_domain_verified_at' => 'datetime',
        'allow_registration' => 'boolean',
    ];

    protected $attributes = [
        'currency' => 'USD',
    ];

    /**
     * All currencies a customer can switch the storefront display to.
     * Base currency is always included, even if not in the JSON column.
     *
     * @return array<int, string>
     */
    public function supportedDisplayCurrencies(): array
    {
        $base = strtoupper($this->currency ?? 'USD');
        $extra = array_map('strtoupper', (array) ($this->display_currencies ?? []));
        return array_values(array_unique(array_merge([$base], $extra)));
    }

    /**
     * Conversion rate from base currency to the given target.
     *
     * Special case: BGN can always be derived from EUR via the fixed peg
     * (1 EUR = 1.95583 BGN), and likewise EUR can be derived from BGN. So if
     * the admin only configured one of the two, we synthesize the other rather
     * than falling back to 1.0 (which would silently misprice).
     *
     * Returns 1.0 only when the target IS the base, or when no derivation
     * path exists.
     */
    public function fxRateFor(string $code): float
    {
        $code = strtoupper($code);
        $base = strtoupper($this->currency ?? 'USD');
        if ($code === $base) {
            return 1.0;
        }
        $rates = $this->fx_rates ?? [];

        if (isset($rates[$code])) {
            return (float) $rates[$code];
        }

        // BGN from EUR via the fixed peg.
        if ($code === 'BGN') {
            $eurRate = $base === 'EUR' ? 1.0 : (float) ($rates['EUR'] ?? 0);
            if ($eurRate > 0) {
                return $eurRate * \App\Services\Money::EUR_BGN_RATE;
            }
        }
        // EUR from BGN via the fixed peg (inverse).
        if ($code === 'EUR') {
            $bgnRate = $base === 'BGN' ? 1.0 : (float) ($rates['BGN'] ?? 0);
            if ($bgnRate > 0) {
                return $bgnRate / \App\Services\Money::EUR_BGN_RATE;
            }
        }

        return 1.0;
    }

    /** Whether the storefront should show the currency switcher. */
    public function hasMultipleDisplayCurrencies(): bool
    {
        return count($this->supportedDisplayCurrencies()) > 1;
    }

    public function allowsGuestCheckout(): bool
    {
        return in_array($this->checkout_mode, [self::CHECKOUT_GUEST, self::CHECKOUT_BOTH], true);
    }

    public function requiresAccountCheckout(): bool
    {
        return $this->checkout_mode === self::CHECKOUT_ACCOUNT;
    }

    public function showsAccountUi(): bool
    {
        return in_array($this->checkout_mode, [self::CHECKOUT_ACCOUNT, self::CHECKOUT_BOTH], true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hasVerifiedCustomDomain(): bool
    {
        return filled($this->custom_domain) && $this->custom_domain_verified_at !== null;
    }

    public function ensureVerificationToken(): string
    {
        if (! $this->custom_domain_verification_token) {
            $this->custom_domain_verification_token = 'ganvo-verification=' . Str::random(24);
            $this->save();
        }
        return $this->custom_domain_verification_token;
    }
}
