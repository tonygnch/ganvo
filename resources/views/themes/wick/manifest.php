<?php

/*
 | Wick — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles (with editable label text), and content fields
 | whose defaults fall back to the platform copy. Read by ThemeCustomizer;
 | edited via the Store Admin "Customize theme" page.
 |
 | Wick ships as a candle apothecary, but nothing here is wax-bound: a
 | perfumer keeps the batch stamps as "Blend", a tea house snuffs the flame.
 */

return [
    'sections' => [
        'facts_strip' => [
            'label' => 'Bench-notes strip (mono facts band)',
            'default' => true,
        ],
        'explain' => [
            'label' => 'Editorial ticket (art panel + manifesto)',
            'default' => true,
        ],
        'discovery_case' => [
            'label' => 'Discovery set (six-jar sampler panel)',
            'default' => true,
        ],
        'news_band' => [
            'label' => 'The dropping list (newsletter)',
            'default' => true,
        ],
    ],

    'motifs' => [
        'batch_numerals' => [
            'label' => 'Batch stamps on product cards (BATCH 01, 02…)',
            'default' => true,
            'text_label' => 'Batch label text',
            'text_default' => 'BATCH',
        ],
        'jar_label' => [
            'label' => 'Cream apothecary label on the hero jar',
            'default' => true,
        ],
        'flame' => [
            'label' => 'Lit flames + breathing candle glow',
            'default' => true,
        ],
        'watermark' => [
            'label' => 'Ghost № watermark behind the hero',
            'default' => true,
        ],
    ],

    'content' => [
        'label_note' => [
            'label' => 'Hero jar label — bottom line',
            'type' => 'text',
            'default_lang' => 'site.storefront.shop_all.eyebrow',
        ],
        'explain_body' => [
            'label' => 'Editorial ticket — manifesto text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.footer.tagline',
        ],
        'case_body' => [
            'label' => 'Discovery set — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.promo.p',
        ],
        'news_body' => [
            'label' => 'Newsletter — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.promo.p',
        ],
    ],

    // Visitor-toggle alternate color mode. Wick is dark-native; "Daylight
    // bench" retunes the core tokens to warm parchment (aliases + shared
    // partials cascade automatically; --deep stays dark so the ticker and
    // footer keep their slab contrast; --accent stays the merchant's knob).
    'modes' => [
        'light' => [
            'name' => 'Daylight bench',
            'vars' => [
                '--bg' => '#f5eee1',
                '--surface' => '#fcf7ed',
                '--surface2' => '#ece1cd',
                '--line' => '#dcccb0',
                '--line2' => '#c3ad8a',
                '--txt' => '#2b2013',
                '--muted' => '#77664d',
                '--faint' => '#99876c',
            ],
        ],
    ],

    'images' => [
        'explain_image' => [
            'label' => 'Story band image',
            'hint' => 'Darker, moodier photos sit best on the candlelit canvas.',
            'size' => '900×1100',
            // Higgsfield art-directed default (wax-pouring workbench scene);
            // merchants replace it from Customize Theme → Images.
            'default' => 'images/demo/wick/story-1.jpg',
        ],
    ],

    'palettes' => [
        'umber' => [
            'name' => 'Umber (default)',
            'vars' => [],
        ],
        'soot' => [
            'name' => 'Soot — near-black, cooler',
            'vars' => [
                '--bg' => '#111214', '--surface' => '#1a1b1e', '--surface2' => '#232428',
                '--line' => '#2e3034', '--line2' => '#44474d', '--muted' => '#a9abb2',
                '--faint' => '#7f828a', '--deep' => '#0a0a0c',
            ],
        ],
        'honey' => [
            'name' => 'Honey — warmer, slightly lighter',
            'vars' => [
                '--bg' => '#221a10', '--surface' => '#2e2416', '--surface2' => '#3a2e1c',
                '--line' => '#4a3b24', '--line2' => '#5f4c30', '--muted' => '#c4b394',
                '--faint' => '#9a8768', '--deep' => '#171006',
            ],
        ],
    ],

    'fonts' => [
        'fraunces' => [
            'name' => 'Fraunces (default)',
            'vars' => [],
            'link' => null,
        ],
        'cormorant' => [
            'name' => 'Cormorant Garamond — finer, more perfumery',
            'vars' => [
                '--display' => '"Cormorant Garamond", serif',
                '--serif' => '"Cormorant Garamond", serif',
            ],
            'link' => 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400..700;1,400..700&display=swap',
        ],
        'monostark' => [
            'name' => 'Space Mono display — stark, utilitarian',
            // Already in Wick's base font stylesheet — zero extra requests.
            'vars' => ['--display' => '"Space Mono", monospace'],
            'link' => null,
        ],
    ],
];
