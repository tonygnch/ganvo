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
        'announcement',
        'nav_menu',
        'hero_banner',
        'signup_fields',
        'is_live',
        'checkout_mode',
        'allow_registration',
    ];

    protected $casts = [
        'theme_settings' => 'array',
        'display_currencies' => 'array',
        'fx_rates' => 'array',
        'announcement' => 'array',
        'nav_menu' => 'array',
        'hero_banner' => 'array',
        'signup_fields' => 'array',
        'is_live' => 'boolean',
        'custom_domain_verified_at' => 'datetime',
        'allow_registration' => 'boolean',
    ];

    /**
     * The set of optional storefront-signup fields the merchant can enable.
     * Order here drives both the admin form and the rendered registration
     * page; treat this as the canonical schema.
     */
    public const SIGNUP_FIELDS = ['phone', 'birthday', 'shipping_address', 'marketing_optin'];

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

    /*
    |----------------------------------------------------------------------
    | Storefront chrome accessors
    |
    | Themes always go through these helpers (never the raw JSON columns)
    | so missing keys, null configs, and disabled toggles never cause a
    | view error. Each helper returns a complete shape with safe defaults.
    |----------------------------------------------------------------------
    */

    /**
     * The announcement bar at the top of every theme.
     *
     * @return array{enabled: bool, text: string, link: ?string}
     */
    public function announcementBar(): array
    {
        $a = (array) ($this->announcement ?? []);
        return [
            'enabled' => (bool) ($a['enabled'] ?? false),
            'text'    => trim((string) ($a['text'] ?? '')),
            'link'    => isset($a['link']) && trim((string) $a['link']) !== ''
                ? trim((string) $a['link'])
                : null,
        ];
    }

    /**
     * Navigation menu items for the header, sorted by sort_order ascending.
     * Each item: { label, url, sort_order }.
     *
     * @return array<int, array{label: string, url: string, sort_order: int}>
     */
    public function navMenuItems(): array
    {
        $items = collect((array) ($this->nav_menu ?? []))
            ->filter(fn ($r) => is_array($r) && ! empty($r['label']) && ! empty($r['url']))
            ->map(fn ($r) => [
                'label'      => trim((string) $r['label']),
                'url'        => trim((string) $r['url']),
                'sort_order' => (int) ($r['sort_order'] ?? 0),
            ])
            ->sortBy('sort_order')
            ->values()
            ->all();
        return $items;
    }

    /**
     * Hero banner shown above the product grid on the storefront index.
     *
     * @return array{enabled: bool, title: string, subtitle: string, image_path: ?string, cta_label: string, cta_url: string}
     */
    public function heroBanner(): array
    {
        $h = (array) ($this->hero_banner ?? []);
        return [
            'enabled'    => (bool) ($h['enabled'] ?? false),
            'title'      => trim((string) ($h['title'] ?? '')),
            'subtitle'   => trim((string) ($h['subtitle'] ?? '')),
            'image_path' => isset($h['image_path']) && $h['image_path'] !== '' ? $h['image_path'] : null,
            'cta_label'  => trim((string) ($h['cta_label'] ?? '')),
            'cta_url'    => trim((string) ($h['cta_url'] ?? '')),
        ];
    }

    /**
     * Normalized signup-field config for the storefront customer registration
     * form. Returns each known field with explicit enabled + required flags,
     * even when the stored JSON is missing or partial — so callers never
     * have to defend against null keys.
     *
     * @return array<string, array{enabled: bool, required: bool}>
     */
    public function signupFieldsConfig(): array
    {
        $stored = (array) ($this->signup_fields ?? []);
        $out = [];
        foreach (self::SIGNUP_FIELDS as $field) {
            $row = (array) ($stored[$field] ?? []);
            $enabled  = (bool) ($row['enabled']  ?? false);
            // `required` only matters when the field is enabled; storing
            // required=true on a disabled field is meaningless, so we
            // normalize it to false to avoid confusion downstream.
            $required = $enabled && (bool) ($row['required'] ?? false);
            $out[$field] = compact('enabled', 'required');
        }
        return $out;
    }
}
