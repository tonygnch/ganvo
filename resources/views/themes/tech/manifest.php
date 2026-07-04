<?php

/*
 | Volt (tech) — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles (with editable label text), and content fields
 | whose defaults fall back to the platform copy. Read by ThemeCustomizer;
 | edited via the Store Admin "Customize theme" page.
 |
 | Volt ships as a gadget shop, but nothing here is electronics-bound: a
 | supplement brand rewrites the spec ticker as "VEGAN / LAB-TESTED", a
 | bookshop switches the badge to "STAFF PICK" and mutes the hero chip.
 */

return [
    'sections' => [
        'spec_strip' => [
            'label' => 'Spec ticker (// FAST-CHARGE // USB-C…)',
            'default' => true,
        ],
        'promo_banner' => [
            'label' => 'Split promo banner (text + image)',
            'default' => true,
        ],
        'category_tiles' => [
            'label' => 'Category tile grid',
            'default' => true,
        ],
        'news_band' => [
            'label' => 'Newsletter panel',
            'default' => true,
        ],
    ],

    'motifs' => [
        'hero_chip' => [
            'label' => 'Mono price chip on the hero product',
            'default' => true,
        ],
        'featured_badge' => [
            'label' => 'Accent badge on the lead featured card',
            'default' => true,
            'text_label' => 'Badge text',
            'text_default_lang' => 'site.storefront.featured.badge',
        ],
    ],

    'content' => [
        'spec_items' => [
            'label' => 'Spec ticker — one spec per line',
            'type' => 'textarea',
            'default' => "FAST-CHARGE\nUSB-C\nBLUETOOTH 5.4\nIPX5\n2Y WARRANTY",
        ],
        'promo_body' => [
            'label' => 'Promo banner — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.promo.p',
        ],
        'news_body' => [
            'label' => 'Newsletter panel — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.footer.tagline',
        ],
    ],

    'palettes' => [
        'volt' => [
            'name' => 'Volt black (default)',
            'vars' => [],
        ],
        'graphite' => [
            'name' => 'Graphite — colder, neutral gray',
            'vars' => [
                '--bg' => '#0b0c0d', '--surface' => '#151618', '--surface2' => '#1d1f22',
                '--line' => '#282a2e', '--muted' => '#9ba0a8', '--faint' => '#6b7078',
            ],
        ],
        'night' => [
            'name' => 'Night — deep blue',
            'vars' => [
                '--bg' => '#070b16', '--surface' => '#0e1526', '--surface2' => '#151f36',
                '--line' => '#202c48', '--muted' => '#96a2bd', '--faint' => '#697697',
            ],
        ],
    ],

    'fonts' => [
        'grotesk' => [
            'name' => 'Space Grotesk (default)',
            'vars' => [],
            'link' => null,
        ],
        'terminal' => [
            'name' => 'Space Mono — full terminal',
            // Already in Volt's base font stylesheet — zero extra requests.
            'vars' => [
                '--display' => '"Space Mono", monospace',
                '--archivo' => '"Space Mono", monospace',
            ],
            'link' => null,
        ],
    ],
];
