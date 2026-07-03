<?php

/*
 | Forma — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles (with editable label text), and content fields
 | whose defaults fall back to the platform copy. Read by ThemeCustomizer;
 | edited via the Store Admin "Customize theme" page.
 |
 | Forma ships as a single-product hardware configurator, but nothing here
 | is bottle-bound: a lamp maker relabels the FIG. chips "PLATE", a
 | supplement brand swaps "18/8" for its own material spec.
 */

return [
    'sections' => [
        'metabar' => [
            'label' => 'Instrument metabar (top mono strip)',
            'default' => true,
        ],
        'spec_row' => [
            'label' => 'Spec readout strip (big animated numerals)',
            'default' => true,
        ],
        'spec_sheet' => [
            'label' => 'Technical datasheet band',
            'default' => true,
        ],
        'cobalt_band' => [
            'label' => 'Accent promo band (cobalt slab)',
            'default' => true,
        ],
    ],

    'motifs' => [
        'dim_lines' => [
            'label' => 'Dimension lines (H / Ø annotations)',
            'default' => true,
        ],
        'fig_caption' => [
            'label' => 'Figure caption chips (FIG. 01 …)',
            'default' => true,
            'text_label' => 'Figure label text',
            'text_default' => 'FIG.',
        ],
        'crosshairs' => [
            'label' => 'Registration crosshair marks',
            'default' => true,
        ],
        'blueprint_grid' => [
            'label' => 'Blueprint graph grid on the stage',
            'default' => true,
        ],
    ],

    'content' => [
        'shop_heading' => [
            'label' => 'Catalogue heading',
            'type' => 'text',
            'default_lang' => 'site.storefront.shop_all.h2',
        ],
        'band_body' => [
            'label' => 'Promo band — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.footer.tagline',
        ],
        'spec_material' => [
            'label' => 'Spec strip — material figure (e.g. 18/8)',
            'type' => 'text',
            'default' => '18/8',
        ],
    ],

    'palettes' => [
        'paper' => [
            'name' => 'Paper (default)',
            'vars' => [],
        ],
        'graphite' => [
            'name' => 'Graphite — warmer, stone-grey',
            'vars' => [
                '--bg' => '#f2f0ec', '--soft' => '#e7e4dd', '--card' => '#fbfaf7',
                '--line' => '#e0dcd3', '--line2' => '#d2cdc2', '--muted' => '#726d63',
                '--ink' => '#1a1712',
            ],
        ],
        'ice' => [
            'name' => 'Ice — cooler, blue-grey',
            'vars' => [
                '--bg' => '#eef1f4', '--soft' => '#e2e7ec', '--card' => '#fbfcfe',
                '--line' => '#d9dfe6', '--line2' => '#c9d1da', '--muted' => '#5e6875',
                '--ink' => '#10141b',
            ],
        ],
    ],

    'fonts' => [
        'sora' => [
            'name' => 'Sora (default)',
            'vars' => [],
            'link' => null,
        ],
        'grotesk' => [
            'name' => 'Space Grotesk — techier display',
            // Already in Forma's base font stylesheet — zero extra requests.
            'vars' => ['--display' => "'Space Grotesk', sans-serif"],
            'link' => null,
        ],
        'archivo' => [
            'name' => 'Archivo — grotesque, industrial',
            'vars' => ['--display' => "'Archivo', sans-serif"],
            'link' => 'https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800&display=swap',
        ],
    ],
];
