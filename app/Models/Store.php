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
        'admin_logo_path',
        'admin_accent_color',
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
        'collection_display',
        'signup_fields',
        'shipping_methods',
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
        'collection_display' => 'array',
        'signup_fields' => 'array',
        'shipping_methods' => 'array',
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
        'currency' => 'EUR',
    ];

    /**
     * All currencies a customer can switch the storefront display to.
     * Base currency is always included, even if not in the JSON column.
     *
     * @return array<int, string>
     */
    public function supportedDisplayCurrencies(): array
    {
        $base = strtoupper($this->currency ?? 'EUR');
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
        $base = strtoupper($this->currency ?? 'EUR');
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

    /**
     * Public URL for the merchant's admin-panel logo, or null to fall back
     * to the default Ganvo mark. Separate from the storefront logo.
     */
    public function adminLogoUrl(): ?string
    {
        return $this->admin_logo_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->admin_logo_path)
            : null;
    }

    /**
     * The merchant's chosen admin accent hex, normalized to #rrggbb, or
     * null when unset / invalid / unusable (panel then uses its default
     * Emerald). "Unusable" = near-black, near-white, or greyscale: those
     * can't drive a legible Filament primary palette (selected/toggle/focus
     * states would lose contrast), so we ignore them rather than break the UI.
     */
    public function adminAccentColor(): ?string
    {
        $hex = $this->admin_accent_color;

        return \App\Support\AccentPalette::isUsable($hex)
            ? '#' . ltrim(trim((string) $hex), '#')
            : null;
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
     * Valid storefront number-change animation styles. Drives the rolling /
     * odometer / fade / flip effect when cart totals update asynchronously.
     * Keys are the stored slug; values are the admin-facing label.
     */
    public const NUMBER_ANIMATIONS = [
        'count'    => 'Count up (rolling)',
        'odometer' => 'Odometer (digit reels)',
        'flip'     => 'Flip (vertical)',
        'fade'     => 'Fade swap',
        'none'     => 'None (instant)',
    ];

    /**
     * Resolved number-change animation style for this store's storefront.
     * Stored in theme_settings.number_animation; defaults to 'count'.
     * Falls back to 'count' for any unknown / legacy value.
     */
    public function numberAnimation(): string
    {
        $v = (string) (($this->theme_settings ?? [])['number_animation'] ?? 'count');
        return array_key_exists($v, self::NUMBER_ANIMATIONS) ? $v : 'count';
    }

    /**
     * Announcement-bar scroll speeds, for themes that render the bar as a
     * moving marquee (e.g. Brick). Keys are the stored slug; values pair an
     * admin label with a target scroll rate in CSS pixels per second.
     *
     * Themes that show a STATIC announcement bar simply ignore the speed.
     * Driving it as px/sec (rather than a fixed animation duration) keeps the
     * perceived speed consistent no matter how long the announcement text is.
     */
    public const ANNOUNCEMENT_SPEEDS = [
        'slow'   => ['label' => 'Slow',   'pxPerSec' => 30],
        'normal' => ['label' => 'Normal', 'pxPerSec' => 55],
        'fast'   => ['label' => 'Fast',   'pxPerSec' => 90],
        'static' => ['label' => 'Static (no scroll)', 'pxPerSec' => 0],
    ];

    /** @return array<string, string> [slug => label] for the admin select. */
    public static function announcementSpeedOptions(): array
    {
        return array_map(fn ($s) => $s['label'], self::ANNOUNCEMENT_SPEEDS);
    }

    /**
     * The announcement bar at the top of every theme.
     *
     * `speed` is one of self::ANNOUNCEMENT_SPEEDS (default 'normal'); only
     * marquee-style themes use it. `speed_px` resolves it to a px/sec rate so
     * a theme can render the marquee at a length-independent speed.
     *
     * @return array{enabled: bool, text: string, link: ?string, speed: string, speed_px: int}
     */
    public function announcementBar(): array
    {
        $a = (array) ($this->announcement ?? []);
        $speed = (string) ($a['speed'] ?? 'normal');
        if (! array_key_exists($speed, self::ANNOUNCEMENT_SPEEDS)) {
            $speed = 'normal';
        }
        return [
            'enabled' => (bool) ($a['enabled'] ?? false),
            'text'    => trim((string) ($a['text'] ?? '')),
            'link'    => isset($a['link']) && trim((string) $a['link']) !== ''
                ? trim((string) $a['link'])
                : null,
            'speed'    => $speed,
            'speed_px' => (int) self::ANNOUNCEMENT_SPEEDS[$speed]['pxPerSec'],
        ];
    }

    /**
     * Navigation menu items for the header, sorted by sort_order ascending.
     *
     * Each item: { label, url, sort_order, auto_source, children }.
     *
     * `url` is nullable: an item with NO url and a non-empty `children` array
     * renders as a dropdown-only parent (label triggers a flyout, no own
     * page). An item with a url AND children renders as a clickable parent
     * whose menu still flyouts on hover/click. An item with neither url nor
     * children is dropped (it would be invisible).
     *
     * `auto_source` (optional): when set to "categories" or "collections",
     * the item's children are replaced by an auto-fetched list of the
     * corresponding model rows where `show_in_menu = true`. This is the
     * preferred way to wire category/collection dropdowns — single source
     * of truth, no double-bookkeeping when the merchant adds a new category.
     * When `auto_source` is null or "none", `children` is read from the
     * stored manual list (back-compat with old configs).
     *
     * `children` is always present (possibly empty) so view code doesn't
     * have to defend against missing keys.
     *
     * @return array<int, array{label: string, url: ?string, sort_order: int, auto_source: ?string, children: array<int, array{label: string, url: string, sort_order: int}>}>
     */
    public function navMenuItems(): array
    {
        $items = collect((array) ($this->nav_menu ?? []))
            ->filter(fn ($r) => is_array($r) && ! empty($r['label']))
            ->map(function ($r) {
                $autoSource = $r['auto_source'] ?? null;
                $autoSource = in_array($autoSource, ['categories', 'collections'], true)
                    ? $autoSource
                    : null;

                if ($autoSource === 'categories') {
                    $children = $this->autoChildrenForCategories();
                } elseif ($autoSource === 'collections') {
                    $children = $this->autoChildrenForCollections();
                } else {
                    // Manual children — same shape as auto, validated +
                    // sort-ordered. Old configs (no auto_source) flow here.
                    $children = collect((array) ($r['children'] ?? []))
                        ->filter(fn ($c) => is_array($c)
                            && ! empty($c['label'])
                            && ! empty($c['url']))
                        ->map(fn ($c) => [
                            'label'      => trim((string) $c['label']),
                            'url'        => trim((string) $c['url']),
                            'sort_order' => (int) ($c['sort_order'] ?? 0),
                        ])
                        ->sortBy('sort_order')
                        ->values()
                        ->all();
                }

                $url = trim((string) ($r['url'] ?? ''));
                return [
                    'label'       => trim((string) $r['label']),
                    'url'         => $url !== '' ? $url : null,
                    'sort_order'  => (int) ($r['sort_order'] ?? 0),
                    'auto_source' => $autoSource,
                    'children'    => $children,
                ];
            })
            // Drop items that have neither a URL nor any children — there's
            // nothing for the user to click. (An auto_source row with zero
            // visible categories/collections gets dropped here too — keeps
            // the storefront from showing an empty dropdown.)
            ->filter(fn ($i) => $i['url'] !== null || ! empty($i['children']))
            ->sortBy('sort_order')
            ->values()
            ->all();
        return $items;
    }

    /**
     * Live list of categories tagged `show_in_menu`. Scoped to this store's
     * tenant. Includes nested children: walks the category tree DFS so each
     * parent is followed immediately by its children (sorted by sort_order
     * within each level). Every child carries a `depth` field — 0 for
     * roots, 1 for direct children, etc. — which the layout uses to
     * visually indent the dropdown items so the hierarchy reads at a
     * glance without needing nested submenus.
     *
     * Edge case: when a child's parent is hidden (show_in_menu=false or
     * is_active=false), the child is orphaned and rendered at depth 0
     * as if it were a root. Better than silently disappearing — the
     * merchant intentionally exposed the child via show_in_menu, so
     * we honor that.
     *
     * @return array<int, array{label: string, url: string, sort_order: int, depth: int}>
     */
    private function autoChildrenForCategories(): array
    {
        $all = Category::query()
            ->where('tenant_id', $this->tenant_id)
            ->where('is_active', true)
            ->where('show_in_menu', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'parent_id', 'sort_order']);

        $byParent = $all->groupBy(fn ($c) => $c->parent_id);
        $emitted = [];
        $flat = [];

        $walk = function ($parentKey, int $depth) use (&$walk, $byParent, &$emitted, &$flat) {
            foreach (($byParent->get($parentKey) ?? collect()) as $c) {
                if (isset($emitted[$c->id])) {
                    continue;
                }
                $emitted[$c->id] = true;
                $flat[] = [
                    'label'      => $c->name,
                    'url'        => '/categories/' . $c->slug,
                    'sort_order' => (int) $c->sort_order,
                    'depth'      => $depth,
                ];
                $walk($c->id, $depth + 1);
            }
        };

        // Roots use the literal null key — groupBy on a nullable column
        // produces an empty-string key, not a null key, so coalesce both.
        $walk(null, 0);
        $walk('', 0);

        // Orphans: categories whose parent isn't in the visible set
        // (parent hidden or out of tenant). Render at depth 0 so the
        // merchant's show_in_menu toggle still wins.
        foreach ($all as $c) {
            if (! isset($emitted[$c->id])) {
                $flat[] = [
                    'label'      => $c->name,
                    'url'        => '/categories/' . $c->slug,
                    'sort_order' => (int) $c->sort_order,
                    'depth'      => 0,
                ];
            }
        }

        return $flat;
    }

    /**
     * Live list of collections tagged `show_in_menu`. Scoped to this store's
     * tenant.
     *
     * @return array<int, array{label: string, url: string, sort_order: int}>
     */
    private function autoChildrenForCollections(): array
    {
        return Collection::query()
            ->where('tenant_id', $this->tenant_id)
            ->where('is_active', true)
            ->where('show_in_menu', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn ($c) => [
                'label'      => $c->title,
                'url'        => '/collections/' . $c->slug,
                'sort_order' => (int) $c->sort_order,
            ])
            ->all();
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
     * Featured-collection strip appearance. Two merchant-controlled knobs —
     * the banner band height and the collection-title size — each a named
     * preset OR a custom pixel value. Presets resolve to the px maps below;
     * "custom" uses the stored px, clamped to a sane band.
     *
     * Defaults (standard / medium) equal the values Brick previously hard-
     * coded, so a store that never touches these settings looks unchanged.
     * Consumed by themes that render a collection banner band (Brick today)
     * as CSS custom properties; other themes simply ignore it.
     */
    public const COLLECTION_BAND_HEIGHTS = ['compact' => 170, 'standard' => 210, 'tall' => 260];

    public const COLLECTION_TITLE_SIZES = ['small' => 34, 'medium' => 44, 'large' => 56];

    /** Bounds for the "custom" px inputs, enforced on both read and save. */
    public const COLLECTION_BAND_MIN = 120;
    public const COLLECTION_BAND_MAX = 360;
    public const COLLECTION_TITLE_MIN = 24;
    public const COLLECTION_TITLE_MAX = 72;

    /**
     * @return array{band_height: string, band_height_px: int, title_size: string, title_size_px: int}
     */
    public function collectionDisplay(): array
    {
        $c = (array) ($this->collection_display ?? []);

        $bandKey = (string) ($c['band_height'] ?? 'standard');
        if (! array_key_exists($bandKey, self::COLLECTION_BAND_HEIGHTS) && $bandKey !== 'custom') {
            $bandKey = 'standard';
        }
        $bandPx = $bandKey === 'custom'
            ? max(self::COLLECTION_BAND_MIN, min(self::COLLECTION_BAND_MAX, (int) ($c['band_height_px'] ?? 210)))
            : self::COLLECTION_BAND_HEIGHTS[$bandKey];

        $titleKey = (string) ($c['title_size'] ?? 'medium');
        if (! array_key_exists($titleKey, self::COLLECTION_TITLE_SIZES) && $titleKey !== 'custom') {
            $titleKey = 'medium';
        }
        $titlePx = $titleKey === 'custom'
            ? max(self::COLLECTION_TITLE_MIN, min(self::COLLECTION_TITLE_MAX, (int) ($c['title_size_px'] ?? 44)))
            : self::COLLECTION_TITLE_SIZES[$titleKey];

        return [
            'band_height'    => $bandKey,
            'band_height_px' => $bandPx,
            'title_size'     => $titleKey,
            'title_size_px'  => $titlePx,
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

    /**
     * Normalized list of shipping methods the customer picks from at
     * checkout. Falls back to a built-in Standard + Express pair when
     * the operator hasn't customized — keeps new stores functional
     * out of the box without forcing an admin tour first.
     *
     * Each entry: id (slug), label, description, price_cents,
     * free_threshold_cents (nullable). `id` is what the checkout form
     * submits; the controller validates against this list and snapshots
     * the label + computed cost onto the Order.
     *
     * @return array<int, array{id: string, label: string, description: string, price_cents: int, free_threshold_cents: ?int}>
     */
    public function shippingMethods(): array
    {
        $stored = (array) ($this->shipping_methods ?? []);
        if (empty($stored)) {
            // Sensible defaults — Standard is free over €50 (matches the
            // old hard-coded threshold) and Express is a flat €15.
            return [
                [
                    'id' => 'standard',
                    'label' => 'Standard shipping',
                    'description' => '3–5 business days',
                    'price_cents' => 500,
                    'free_threshold_cents' => 5000,
                ],
                [
                    'id' => 'express',
                    'label' => 'Express shipping',
                    'description' => '1–2 business days',
                    'price_cents' => 1500,
                    'free_threshold_cents' => null,
                ],
            ];
        }
        $out = [];
        foreach ($stored as $i => $row) {
            $row = (array) $row;
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue; // skip half-saved rows
            }
            $idRaw = trim((string) ($row['id'] ?? $label));
            $out[] = [
                'id' => Str::slug($idRaw) ?: 'method-' . $i,
                'label' => $label,
                'description' => trim((string) ($row['description'] ?? '')),
                'price_cents' => max(0, (int) ($row['price_cents'] ?? 0)),
                'free_threshold_cents' => isset($row['free_threshold_cents']) && $row['free_threshold_cents'] !== ''
                    ? (int) $row['free_threshold_cents']
                    : null,
            ];
        }
        // Defensive: if every row was empty, fall back to defaults
        // rather than render a checkout with no shipping options.
        return $out ?: $this->forceDefaultShippingMethods();
    }

    /** @internal used by shippingMethods() when stored config is empty. */
    private function forceDefaultShippingMethods(): array
    {
        $original = $this->shipping_methods;
        $this->shipping_methods = null;
        $defaults = $this->shippingMethods();
        $this->shipping_methods = $original;
        return $defaults;
    }

    /**
     * Resolve a shipping method by id against the current subtotal.
     * Returns null when the id doesn't match any configured method.
     *
     * @return ?array{id: string, label: string, description: string, price_cents: int, cost_cents: int}
     */
    public function resolveShippingMethod(string $id, int $subtotalCents): ?array
    {
        foreach ($this->shippingMethods() as $m) {
            if ($m['id'] !== $id) {
                continue;
            }
            // Apply free-over-threshold rule when set.
            $cost = $m['price_cents'];
            if ($m['free_threshold_cents'] !== null && $subtotalCents >= $m['free_threshold_cents']) {
                $cost = 0;
            }
            return [
                'id' => $m['id'],
                'label' => $m['label'],
                'description' => $m['description'],
                'price_cents' => $m['price_cents'],
                'cost_cents' => $cost,
            ];
        }
        return null;
    }
}
