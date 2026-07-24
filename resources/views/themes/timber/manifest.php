<?php

/*
 | Timber — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles (with editable label text), and content fields
 | whose defaults fall back to the platform copy. Read by ThemeCustomizer;
 | edited via the Store Admin "Customize theme" page.
 |
 | Timber ships as a treated-wood / building-materials yard, but nothing
 | here is sawdust-bound: a tile shop keeps the lot stamps, a tool dealer
 | swaps the use-class guide for the specs strip alone.
 */

return [
    'sections' => [
        'specs_strip' => [
            'label' => 'Yard-notes strip (mono facts band)',
            'default' => true,
        ],
        'use_classes' => [
            'label' => 'Use-class guide (UC1–UC4 treatment band)',
            'default' => true,
        ],
        'explain' => [
            'label' => 'Story band (sawmill photo + manifesto)',
            'default' => true,
        ],
        'bulk_band' => [
            'label' => 'Trade & bulk callout',
            'default' => true,
        ],
        'news_band' => [
            'label' => 'Price-list newsletter',
            'default' => true,
        ],
    ],

    'motifs' => [
        'lot_stamps' => [
            'label' => 'Lot stamps on product cards (LOT 01, 02…)',
            'default' => true,
            'text_label' => 'Lot label text',
            'text_default' => 'LOT',
        ],
        'grade_stamp' => [
            'label' => 'Grading stamp on the hero stack',
            'default' => true,
            'text_label' => 'Stamp text (grade mark)',
            'text_default' => 'C24 · KD',
        ],
        'ruler' => [
            'label' => 'Dimension-ruler ticks on panels',
            'default' => true,
        ],
        'watermark' => [
            'label' => 'Ghost dimension numeral behind the hero',
            'default' => true,
        ],
        'grain_rings' => [
            'label' => 'End-grain ring accents',
            'default' => true,
        ],
    ],

    'content' => [
        'spec_note' => [
            'label' => 'Hero spec plate — bottom line',
            'type' => 'text',
            'default_lang' => 'site.storefront.timber.spec_note',
        ],
        'explain_body' => [
            'label' => 'Story band — manifesto text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.footer.tagline',
        ],
        'bulk_body' => [
            'label' => 'Trade & bulk — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.timber.bulk_body',
        ],
        'news_body' => [
            'label' => 'Newsletter — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.promo.p',
        ],
    ],

    // Visitor-toggle alternate color mode. Timber is daylight-native; the
    // "Workshop night" mode drops the yard into dark walnut (aliases +
    // shared partials cascade automatically; --accent stays the merchant's
    // knob; --deep stays near-black for ticker/footer contrast either way).
    'modes' => [
        'dark' => [
            'name' => 'Workshop night',
            'vars' => [
                '--bg' => '#1f1810',
                '--surface' => '#292015',
                '--surface2' => '#332a1c',
                '--line' => '#443723',
                '--line2' => '#5a4a30',
                '--txt' => '#f0e7d6',
                '--muted' => '#b3a48c',
                '--faint' => '#8b7c62',
                '--plate' => '#f0e7d6',
                // light-native theme: on a dark page the walnut slab must RISE
                // above --bg (#1f1810), not sink below it, or the ticker,
                // trade band and drawer lose all separation.
                '--deep' => '#3a2f1e',
            ],
        ],
    ],

    'images' => [
        'explain_image' => [
            'label' => 'Story band image',
            'hint' => 'Sawmill, stacked boards, or workshop photography sits best.',
            'size' => '900×1100',
            'default' => null,
        ],
    ],

    'palettes' => [
        'pine' => [
            'name' => 'Pine (default)',
            'vars' => [],
        ],
        'sand' => [
            'name' => 'Sand — warmer, more cream',
            'vars' => [
                '--bg' => '#f6efe2', '--surface' => '#fdf8ee', '--surface2' => '#efe5d2',
                '--line' => '#ddcfb5', '--line2' => '#c4b090', '--muted' => '#75664f',
                '--faint' => '#998a70',
            ],
        ],
        'ash' => [
            'name' => 'Ash — cooler, concrete grey',
            'vars' => [
                '--bg' => '#eff0ec', '--surface' => '#f9faf7', '--surface2' => '#e3e5df',
                '--line' => '#ccd0c6', '--line2' => '#aab0a2', '--muted' => '#5f665c',
                '--faint' => '#868c82',
            ],
        ],
    ],

    'fonts' => [
        'barlow' => [
            'name' => 'Barlow Condensed (default)',
            'vars' => [],
            'link' => null,
        ],
        'archivo' => [
            'name' => 'Archivo — wider, heavier industrial',
            'vars' => [
                '--display' => '"Archivo", sans-serif',
            ],
            'link' => 'https://fonts.googleapis.com/css2?family=Archivo:wght@500..900&display=swap',
        ],
        'slab' => [
            'name' => 'Zilla Slab — classic sawmill slab-serif',
            'vars' => [
                '--display' => '"Zilla Slab", serif',
            ],
            'link' => 'https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@500;600;700&display=swap',
        ],
    ],
];
