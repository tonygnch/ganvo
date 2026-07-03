<?php

namespace App\Themes;

class ThemeRegistry
{
    /**
     * @return array<string, array{name: string, description: string, screenshot: string}>
     */
    public static function all(): array
    {
        return [
            'default' => [
                'name' => 'Atelier',
                'description' => 'Editorial luxury — warm paper tones, Cormorant Garamond serif headlines, magazine-grade layouts. Built for fashion and considered lifestyle brands.',
                'screenshot' => '/images/themes/default.svg',
            ],
            'minimal' => [
                'name' => 'Lumine',
                'description' => 'Soft, premium beauty — blush palette, Marcellus serif, rounded cards and gentle gradients. Built for skincare, cosmetics, and wellness.',
                'screenshot' => '/images/themes/minimal.svg',
            ],
            'gallery' => [
                'name' => 'Terra',
                'description' => 'Warm, tactile, lifestyle — stone and clay tones, Bricolage Grotesque, split editorial panels. Built for home goods, craft, and slow brands.',
                'screenshot' => '/images/themes/gallery.svg',
            ],
            'menu' => [
                'name' => 'Menu',
                'description' => 'Restaurant-card layout with dotted leader lines from each item to its price. Built for food, drink, and tasting menus.',
                'screenshot' => '/images/themes/menu.svg',
            ],
            'tech' => [
                'name' => 'Volt',
                'description' => 'Sharp dark mode — near-black canvas, neon accent, Space Grotesk + mono details. Built for electronics, gear, and digital products.',
                'screenshot' => '/images/themes/tech.svg',
            ],
            'brick' => [
                'name' => 'Brick',
                'description' => 'Loud neo-brutalist — thick black borders, hard offset shadows, acid-lime accent, Lexend Mega display. Built for streetwear, sneakers, records, and bold DTC brands.',
                'screenshot' => '/images/themes/brick.svg',
            ],
            'posy' => [
                'name' => 'Posy',
                'description' => 'Soft seasonal florist — sage and cream palette, DM Serif Display with Cormorant italics, polaroid cards and washi-tape details. Built for florists, plants, gifting, and gentle lifestyle brands.',
                'screenshot' => '/images/themes/posy.svg',
            ],
            'ember' => [
                'name' => 'Ember',
                'description' => 'Warm specialty coffee — roasted terracotta on cream, Spectral serif with Space Mono detailing, tactile café layouts. Built for roasters, tea, bakeries, and warm artisan brands.',
                'screenshot' => '/images/themes/ember.svg',
            ],
            'kiln' => [
                'name' => 'Kiln',
                'description' => 'Handmade ceramics — muted clay and stone tones, Schibsted Grotesk with Newsreader serif, soft stone-gradient cards. Built for pottery, homeware, craft, and slow-made goods.',
                'screenshot' => '/images/themes/kiln.svg',
            ],
            'wick' => [
                'name' => 'Wick',
                'description' => 'Candlelit apothecary — near-black canvas warmed by amber, Fraunces serif with mono batch labels. Built for candles, home fragrance, apothecary, and moody artisan brands.',
                'screenshot' => '/images/themes/wick.svg',
            ],
            'forma' => [
                'name' => 'Forma',
                'description' => 'Single-product showcase — cobalt accent on light grey, Sora geometric sans, configurator-style hero with spec rows. Built for one hero product: gadgets, bottles, design objects.',
                'screenshot' => '/images/themes/forma.svg',
            ],
        ];
    }

    public static function exists(string $id): bool
    {
        return array_key_exists($id, self::all());
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
     * @return array<string, string> [id => name]
     */
    public static function options(): array
    {
        return array_map(fn ($t) => $t['name'], self::all());
    }
}
