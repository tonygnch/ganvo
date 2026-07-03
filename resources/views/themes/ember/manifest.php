<?php

/*
 | Ember — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles (with editable label text), and content fields
 | whose defaults fall back to the platform copy. Read by ThemeCustomizer;
 | edited via the Store Admin "Customize theme" page.
 |
 | Ember ships as a coffee roaster, but nothing here is coffee-bound: a tea
 | house keeps the pips as "Strength", a bakery turns them off entirely.
 */

return [
    'sections' => [
        'menu_board' => [
            'label' => 'Menu board (hero product list)',
            'default' => true,
        ],
        'stats_strip' => [
            'label' => 'Ledger stats strip',
            'default' => true,
        ],
        'craft_band' => [
            'label' => 'Craft story band',
            'default' => true,
        ],
        'subscribe_band' => [
            'label' => 'Dark promo band',
            'default' => true,
        ],
    ],

    'motifs' => [
        'roast_pips' => [
            'label' => 'Level pips on menu rows (● ● ○)',
            'default' => true,
            'text_label' => 'What the pips measure',
            'text_default_lang' => 'site.storefront.ember.roast_label',
        ],
        'ring_stain' => [
            'label' => 'Coffee-ring stain on the board',
            'default' => true,
        ],
    ],

    'content' => [
        'board_heading' => [
            'label' => 'Menu board heading',
            'type' => 'text',
            'default_lang' => 'site.storefront.shop_all.h2',
        ],
        'craft_body' => [
            'label' => 'Craft band — story text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.footer.tagline',
        ],
        'subscribe_body' => [
            'label' => 'Promo band — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.promo.p',
        ],
    ],

    'palettes' => [
        'roast' => [
            'name' => 'Roast cream (default)',
            'vars' => [],
        ],
        'flour' => [
            'name' => 'Flour — lighter, cooler',
            'vars' => [
                '--bg' => '#f5f1e8', '--soft' => '#e9e2d3', '--soft2' => '#ded4bf',
                '--card' => '#fdfbf4', '--line' => '#d3c8ae', '--muted' => '#867a64',
                '--ink' => '#2d241a', '--deep' => '#201811',
            ],
        ],
        'rye' => [
            'name' => 'Rye — deeper, toastier',
            'vars' => [
                '--bg' => '#e9dcc8', '--soft' => '#dbc9ac', '--soft2' => '#cfba98',
                '--card' => '#f7efe0', '--line' => '#bfa87f', '--muted' => '#75603f',
                '--ink' => '#271c11', '--deep' => '#1c130a',
            ],
        ],
    ],

    'fonts' => [
        'spectral' => [
            'name' => 'Spectral (default)',
            'vars' => [],
            'link' => null,
        ],
        'dmserif' => [
            'name' => 'DM Serif Display — rounder, softer',
            // Already in Ember's base font stylesheet — zero extra requests.
            'vars' => ['--display' => '"DM Serif Display", serif'],
            'link' => null,
        ],
        'fraunces' => [
            'name' => 'Fraunces — wonky old-style',
            'vars' => ['--display' => '"Fraunces", serif'],
            'link' => 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap',
        ],
    ],
];
