<?php

/*
 | Brick — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles (with editable label text), and content fields
 | whose defaults fall back to the platform copy. Read by ThemeCustomizer;
 | edited via the Store Admin "Customize theme" page.
 |
 | Brick ships as loud streetwear, but nothing here is apparel-bound: a
 | record shop keeps the numbered chips as crate markers, a bakery swaps
 | acid lime for hot punch and drops the price sticker.
 |
 | NOTE: collection-strip sizing (band height / title size) is already a
 | Store Settings knob (collectionDisplay()) — deliberately NOT duplicated
 | here. The offset hard shadows are Brick's identity and stay untouchable.
 */

return [
    'sections' => [
        'ticker' => [
            'label' => 'Selling-points strip (01 / 02 / 03 band under the hero)',
            'default' => true,
        ],
        'related' => [
            'label' => 'Related-products rail on the product page',
            'default' => true,
        ],
    ],

    'motifs' => [
        'hl_mark' => [
            'label' => 'Accent highlighter behind the store name (hero headline)',
            'default' => true,
        ],
        'pricetag' => [
            'label' => 'Price sticker on the hero image',
            'default' => true,
        ],
        'num_chips' => [
            'label' => 'Numbered stamp chips (ticker, collection cards, menu)',
            'default' => true,
        ],
    ],

    'content' => [
        'hero_sub' => [
            'label' => 'Hero — supporting paragraph',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.hero.sub',
        ],
        'footer_tagline' => [
            'label' => 'Footer — tagline',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.footer.tagline',
        ],
    ],

    'palettes' => [
        'acid' => [
            'name' => 'Acid lime (default)',
            'vars' => [],
        ],
        'punch' => [
            'name' => 'Hot punch — magenta heat',
            // Shift the accent family + soft paper tones; ink and paper stay.
            'vars' => [
                '--accent' => '#ff5db1', '--soft' => '#f6e2ec', '--soft2' => '#eccfdf',
            ],
        ],
        'volt' => [
            'name' => 'Volt cyan — cold electric',
            'vars' => [
                '--accent' => '#3fe3ff', '--soft' => '#e0f1f4', '--soft2' => '#cfe6ea',
            ],
        ],
    ],

    'fonts' => [
        'lexend' => [
            'name' => 'Lexend Mega (default)',
            'vars' => [],
            'link' => null,
        ],
        'archivo' => [
            'name' => 'Archivo Black — heavier, poster slab',
            'vars' => ['--display' => '"Archivo Black", system-ui, sans-serif'],
            'link' => 'https://fonts.googleapis.com/css2?family=Archivo+Black&display=swap',
        ],
        'grotesk' => [
            'name' => 'Space Grotesk — techy, tighter',
            'vars' => ['--display' => '"Space Grotesk", system-ui, sans-serif'],
            'link' => 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&display=swap',
        ],
    ],
];
