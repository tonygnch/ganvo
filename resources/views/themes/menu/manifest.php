<?php

/*
 | Menu — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles (with editable label text), and content fields
 | whose defaults fall back to the platform copy. Read by ThemeCustomizer;
 | edited via the Store Admin "Customize theme" page.
 |
 | Menu is a deliberately thin, printed-cafe-menu theme, so the surface is
 | small: the dotted leader lines are its identity and stay untouched.
 */

return [
    'sections' => [
        'menu_header' => [
            'label' => 'Menu title block (eyebrow + heading)',
            'default' => true,
        ],
        'collections' => [
            'label' => 'Featured collection strips',
            'default' => true,
        ],
    ],

    'motifs' => [
        'ornament' => [
            'label' => 'Dot ornament under the heading (— ● —)',
            'default' => true,
        ],
        'out_stamp' => [
            'label' => 'Sold-out tag on menu rows',
            'default' => true,
            'text_label' => 'What sold-out rows say',
            'text_default_lang' => 'site.storefront.product.out_of_stock',
        ],
    ],

    'content' => [
        'menu_eyebrow' => [
            'label' => 'Menu eyebrow (small line above the heading)',
            'type' => 'text',
            'default_lang' => 'site.storefront.shop_all.eyebrow',
        ],
        'menu_heading' => [
            'label' => 'Menu heading',
            'type' => 'text',
            'default_lang' => 'site.storefront.shop_all.h2',
        ],
    ],

    'palettes' => [
        'paper' => [
            'name' => 'Warm cream (default)',
            'vars' => [],
        ],
        'ivory' => [
            'name' => 'Ivory — lighter, airier',
            'vars' => [
                '--paper' => '#fdfaf3', '--paper-deep' => '#f4eee0',
                '--ink' => '#33291d', '--ink-soft' => '#6c5c44',
                '--rule' => '#e0d5bc',
            ],
        ],
        'bistro' => [
            'name' => 'Bistro — cream with a dark-red lean',
            'vars' => [
                '--paper' => '#faf3ea', '--paper-deep' => '#f0e2d4',
                '--ink' => '#3a1a14', '--ink-soft' => '#7a4a3c',
                '--rule' => '#dcc3b0',
            ],
        ],
    ],

    'fonts' => [
        'playfair' => [
            'name' => 'Playfair Display (default)',
            'vars' => [],
            'link' => null,
        ],
        'cormorant' => [
            'name' => 'Cormorant Garamond — lighter, more delicate',
            'vars' => ['--display' => '"Cormorant Garamond", Georgia, serif'],
            'link' => 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&display=swap',
        ],
    ],
];
