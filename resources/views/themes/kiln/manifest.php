<?php

/*
 | Kiln — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles (with editable label text), and content fields
 | whose defaults fall back to the platform copy. Read by ThemeCustomizer;
 | edited via the Store Admin "Customize theme" page.
 |
 | Kiln ships as a ceramics studio, but nothing here is clay-bound: a
 | woodworker keeps the placard as "Hand-cut · Small batch", a print shop
 | turns the thrown-rings ornament off entirely.
 */

return [
    'sections' => [
        'meta_row' => [
            'label' => 'Studio facts strip (Wheel-thrown / Food-safe…)',
            'default' => true,
        ],
        'maker_quote' => [
            'label' => 'Maker statement (centred quote)',
            'default' => true,
        ],
        'process_band' => [
            'label' => 'Process band (image + numbered steps)',
            'default' => true,
        ],
        'news_band' => [
            'label' => 'Newsletter band',
            'default' => true,
        ],
    ],

    'motifs' => [
        'thrown_rings' => [
            'label' => 'Thrown-rings ornament (hero ring field + ring marks)',
            'default' => true,
        ],
        'work_numbers' => [
            'label' => 'Catalogue numbering on pieces (01 / 02)',
            'default' => true,
        ],
        'placard' => [
            'label' => 'Gallery placard line under titles',
            'default' => true,
            'text_label' => 'What the placard says',
            'text_default_lang' => 'site.storefront.kiln.maker_sign',
        ],
    ],

    'content' => [
        'hero_lede' => [
            'label' => 'Hero — lede paragraph',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.kiln.hero_lede',
        ],
        'maker_quote' => [
            'label' => 'Maker statement — quote',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.kiln.maker_quote',
        ],
        'process_p' => [
            'label' => 'Process band — intro text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.kiln.process_p',
        ],
        'news_p' => [
            'label' => 'Newsletter band — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.kiln.news_p',
        ],
    ],

    'palettes' => [
        'stone' => [
            'name' => 'Studio stone (default)',
            'vars' => [],
        ],
        'sand' => [
            'name' => 'Sand — warmer, sunlit',
            'vars' => [
                '--bg' => '#ece5d8', '--soft' => '#e2d8c4', '--soft2' => '#d7cab2',
                '--card' => '#f7f1e4', '--line' => '#d8ccb4', '--line2' => '#c8b99c',
                '--muted' => '#8a8069',
            ],
        ],
        'gallery' => [
            'name' => 'Gallery white — paler, cooler',
            'vars' => [
                '--bg' => '#f0efeb', '--soft' => '#e6e4dd', '--soft2' => '#dbd8cf',
                '--card' => '#fbfaf7', '--line' => '#dcd9d0', '--line2' => '#ccc8bb',
                '--muted' => '#8b897c',
            ],
        ],
    ],

    'fonts' => [
        'schibsted' => [
            'name' => 'Schibsted Grotesk (default)',
            'vars' => [],
            'link' => null,
        ],
        'newsreader' => [
            'name' => 'Newsreader — serif labels, softer',
            // Already in Kiln's base font stylesheet — zero extra requests.
            'vars' => ['--display' => '"Newsreader", serif'],
            'link' => null,
        ],
        'fraunces' => [
            'name' => 'Fraunces — wonky old-style',
            'vars' => ['--display' => '"Fraunces", serif'],
            'link' => 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&display=swap',
        ],
    ],
];
