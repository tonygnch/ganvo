<?php

/*
 | Lumine — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles, and content fields whose defaults fall back to
 | the platform copy. Read by ThemeCustomizer; edited via the Store Admin
 | "Customize theme" page.
 |
 | Lumine ships as a soft premium beauty storefront, but nothing here is
 | beauty-bound: a stationery shop keeps the ritual band as its story,
 | a minimalist label turns the blobs off for a flatter hero.
 */

return [
    'sections' => [
        'trust_strip' => [
            'label' => 'Trust strip (value props under the hero)',
            'default' => true,
        ],
        'ritual_band' => [
            'label' => 'Ritual story band (blush two-up)',
            'default' => true,
        ],
        'category_tiles' => [
            'label' => 'Category tiles (three-up)',
            'default' => true,
        ],
        'newsletter_card' => [
            'label' => 'Newsletter card (above the footer)',
            'default' => true,
        ],
    ],

    'motifs' => [
        'hero_blobs' => [
            'label' => 'Soft gradient blobs in the hero',
            'default' => true,
        ],
        'hero_cameo' => [
            'label' => 'Product photo cameo in the hero',
            'default' => true,
        ],
    ],

    'content' => [
        'hero_sub' => [
            'label' => 'Hero — intro text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.hero.sub',
        ],
        'ritual_body' => [
            'label' => 'Ritual band — story text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.promo.p',
        ],
        'news_body' => [
            'label' => 'Newsletter card — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.footer.tagline',
        ],
    ],

    'palettes' => [
        'blush' => [
            'name' => 'Blush (default)',
            'vars' => [],
        ],
        'porcelain' => [
            'name' => 'Porcelain — quiet neutral',
            'vars' => [
                '--bg' => '#f8f6f2', '--ink' => '#3d3630', '--soft' => '#eee8df',
                '--blush' => '#f1ebe2', '--card' => '#ffffff', '--line' => '#e5dcce',
                '--muted' => '#95897a', '--text-muted' => '#6e6255',
            ],
        ],
        'lavender' => [
            'name' => 'Lavender — cool dusk',
            'vars' => [
                '--bg' => '#f9f4f9', '--ink' => '#463950', '--soft' => '#eedff0',
                '--blush' => '#f2e6f3', '--card' => '#ffffff', '--line' => '#e7d8e9',
                '--muted' => '#96829d', '--text-muted' => '#6f5c78',
            ],
        ],
    ],

    'fonts' => [
        'marcellus' => [
            'name' => 'Marcellus (default)',
            'vars' => [],
            'link' => null,
        ],
        'cormorant' => [
            'name' => 'Cormorant Garamond — finer, more romantic',
            // Already in Lumine's base font stylesheet — zero extra requests.
            'vars' => ['--display' => '"Cormorant Garamond", serif'],
            'link' => null,
        ],
        'prata' => [
            'name' => 'Prata — high-contrast didone',
            'vars' => ['--display' => '"Prata", serif'],
            'link' => 'https://fonts.googleapis.com/css2?family=Prata&display=swap',
        ],
    ],
];
