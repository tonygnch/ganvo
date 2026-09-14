<?php

namespace App\Themes;

use App\Models\Tenant;

class ThemeRegistry
{
    /**
     * Who may pick a theme. Visibility only governs CHOOSING one — onboarding,
     * Store Settings, the onboarding preview. It never stops a theme rendering:
     * a shop already on a hidden theme keeps working and keeps it selected.
     *
     *  - public:  offered to every shop.
     *  - hidden:  offered to nobody new; kept for the shops already on it.
     *  - private: a client's own design, offered only to the tenant slugs in
     *             'tenants'. Their brand is not a template for anyone else.
     */
    public const PUBLIC = 'public';

    public const HIDDEN = 'hidden';

    public const PRIVATE = 'private';

    /**
     * @return array<string, array{name: string, description: string, screenshot: string, visibility: string, tenants?: list<string>}>
     */
    public static function all(): array
    {
        return [
            'default' => [
                'name' => 'Atelier',
                'description' => 'Editorial luxury — warm paper tones, Cormorant Garamond serif headlines, magazine-grade layouts. Built for fashion and considered lifestyle brands.',
                'screenshot' => '/images/themes/default.svg',
                'visibility' => self::HIDDEN,
            ],
            'minimal' => [
                'name' => 'Lumine',
                'description' => 'Soft, premium beauty — blush palette, Marcellus serif, rounded cards and gentle gradients. Built for skincare, cosmetics, and wellness.',
                'screenshot' => '/images/themes/minimal.svg',
                'visibility' => self::HIDDEN,
            ],
            'gallery' => [
                'name' => 'Terra',
                'description' => 'Warm, tactile, lifestyle — stone and clay tones, Bricolage Grotesque, split editorial panels. Built for home goods, craft, and slow brands.',
                'screenshot' => '/images/themes/gallery.svg',
                'visibility' => self::HIDDEN,
            ],
            'menu' => [
                'name' => 'Menu',
                'description' => 'Restaurant-card layout with dotted leader lines from each item to its price. Built for food, drink, and tasting menus.',
                'screenshot' => '/images/themes/menu.svg',
                'visibility' => self::HIDDEN,
            ],
            'tech' => [
                'name' => 'Volt',
                'description' => 'Sharp dark mode — near-black canvas, neon accent, Space Grotesk + mono details. Built for electronics, gear, and digital products.',
                'screenshot' => '/images/themes/tech.svg',
                'visibility' => self::HIDDEN,
            ],
            'brick' => [
                'name' => 'Brick',
                'description' => 'Loud neo-brutalist — thick black borders, hard offset shadows, acid-lime accent, Lexend Mega display. Built for streetwear, sneakers, records, and bold DTC brands.',
                'screenshot' => '/images/themes/brick.svg',
                'visibility' => self::HIDDEN,
            ],
            'posy' => [
                'name' => 'Posy',
                'description' => 'Soft seasonal florist — sage and cream palette, DM Serif Display with Cormorant italics, polaroid cards and washi-tape details. Built for florists, plants, gifting, and gentle lifestyle brands.',
                'screenshot' => '/images/themes/posy.svg',
                'visibility' => self::PUBLIC,
            ],
            'ember' => [
                'name' => 'Ember',
                'description' => 'Warm specialty coffee — roasted terracotta on cream, Spectral serif with Space Mono detailing, tactile café layouts. Built for roasters, tea, bakeries, and warm artisan brands.',
                'screenshot' => '/images/themes/ember.svg',
                'visibility' => self::HIDDEN,
            ],
            'kiln' => [
                'name' => 'Kiln',
                'description' => 'Handmade ceramics — muted clay and stone tones, Schibsted Grotesk with Newsreader serif, soft stone-gradient cards. Built for pottery, homeware, craft, and slow-made goods.',
                'screenshot' => '/images/themes/kiln.svg',
                'visibility' => self::HIDDEN,
            ],
            'wick' => [
                'name' => 'Wick',
                'description' => 'Candlelit apothecary — near-black canvas warmed by amber, Fraunces serif with mono batch labels. Built for candles, home fragrance, apothecary, and moody artisan brands.',
                'screenshot' => '/images/themes/wick.svg',
                'visibility' => self::PUBLIC,
            ],
            'forma' => [
                'name' => 'Forma',
                'description' => 'Single-product showcase — cobalt accent on light grey, Sora geometric sans, configurator-style hero with spec rows. Built for one hero product: gadgets, bottles, design objects.',
                'screenshot' => '/images/themes/forma.svg',
                'visibility' => self::PUBLIC,
            ],
            'timber' => [
                'name' => 'Timber',
                'description' => 'Daylight lumber yard — sanded-pine paper, resin-amber accent, Barlow Condensed signage caps with mono grading stamps, plank-stack hero and a UC1–UC4 treatment guide. Built for treated wood, building materials, and trade supply.',
                'screenshot' => '/images/themes/timber.svg',
                'visibility' => self::PUBLIC,
            ],
            'sankevi' => [
                'name' => 'Sankevi',
                'description' => 'Forest atelier — warm off-black bark ground with moss green and birch cream, Alegreya serif over Commissioner, full-bleed photography with headlines walking across it and every plate cut at the corner. Built for sawmills, timber, stone, and heritage makers who sell the craft before the spec.',
                'screenshot' => '/images/themes/sankevi.svg',
                'visibility' => self::PRIVATE,
                'tenants' => ['sankevi'],
            ],
        ];
    }

    public static function exists(string $id): bool
    {
        return array_key_exists($id, self::all());
    }

    /**
     * The themes this tenant may choose, in registry order: every public theme,
     * any private theme that names the tenant, and $current — the theme the
     * shop is already on — even when it is hidden, so saving Store Settings
     * never forces a shop off the design it has.
     *
     * Onboarding passes no $current: a new shop is created on 'default' as a
     * placeholder, and that placeholder is not a choice anyone made.
     *
     * @return array<string, array>
     */
    public static function selectable(?Tenant $tenant, ?string $current = null): array
    {
        return array_filter(
            self::all(),
            fn (array $theme, string $id) => $id === $current
                || $theme['visibility'] === self::PUBLIC
                || ($theme['visibility'] === self::PRIVATE
                    && $tenant !== null
                    && in_array($tenant->slug, $theme['tenants'] ?? [], true)),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @return list<string>
     */
    public static function selectableIds(?Tenant $tenant, ?string $current = null): array
    {
        return array_keys(self::selectable($tenant, $current));
    }

    /**
     * A theme's customization manifest (content fields, section/motif toggles,
     * palette presets, font pairings) from its manifest.php, or [] when the
     * theme doesn't declare one. Cached per request.
     *
     * @var array<string, array>
     */
    private static array $manifests = [];

    public static function manifest(string $id): array
    {
        if (! array_key_exists($id, self::$manifests)) {
            $path = resource_path("views/themes/{$id}/manifest.php");
            self::$manifests[$id] = is_file($path) ? (array) require $path : [];
        }

        return self::$manifests[$id];
    }

    public static function get(string $id): array
    {
        return self::all()[$id] ?? self::all()['default'];
    }

    public static function ids(): array
    {
        return array_keys(self::all());
    }

    /**
     * @param  list<string>|null  $only  limit to these ids (e.g. selectableIds())
     * @return array<string, string> [id => name]
     */
    public static function options(?array $only = null): array
    {
        // Theme NAMES are brand nouns — Atelier, Volt, Brick. They are the same
        // word in every language and are deliberately not translated.
        return array_map(fn ($t) => $t['name'], self::only($only));
    }

    /**
     * The picker blurb for each theme, translated. Kept apart from all() so the
     * English text there stays the single source for the key list and for any
     * caller that wants the untranslated original.
     *
     * @param  list<string>|null  $only  limit to these ids (e.g. selectableIds())
     * @return array<string, string>
     */
    public static function descriptions(?array $only = null): array
    {
        $out = [];

        foreach (self::only($only) as $key => $theme) {
            // A theme added without a translation falls back to its English
            // blurb rather than rendering the raw key.
            $line = __("admin.themes.{$key}");
            $out[$key] = $line === "admin.themes.{$key}" ? $theme['description'] : $line;
        }

        return $out;
    }

    /**
     * @param  list<string>|null  $ids
     */
    private static function only(?array $ids): array
    {
        return $ids === null ? self::all() : array_intersect_key(self::all(), array_flip($ids));
    }

    /**
     * A label that came out of a theme's manifest.php, translated.
     *
     * Manifest text is authored in English next to the theme it describes, and
     * that English stays the single source for the key list; the translation
     * lives under admin.manifest.{theme}.{kind}.{id} — e.g.
     * admin.manifest.sankevi.palette.ash. Same fallback rule as descriptions():
     * an entry with no key yet renders its English manifest text rather than
     * the raw key, so a theme can ship a palette or a pairing before anyone
     * has translated it.
     */
    public static function manifestText(string $theme, string $kind, string $id, string $english): string
    {
        $key = "admin.manifest.{$theme}.{$kind}.{$id}";
        $line = __($key);

        return is_string($line) && $line !== $key ? $line : $english;
    }
}
