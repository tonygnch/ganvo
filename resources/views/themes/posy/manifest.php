<?php

/*
 | Posy — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles, and content fields whose defaults fall back to
 | the platform copy. Read by ThemeCustomizer; edited via the Store Admin
 | "Customize theme" page.
 |
 | Posy ships as a soft seasonal florist, but nothing here is flower-bound:
 | a ceramicist keeps the washi tape and tilted polaroids, a stationer
 | swaps the sage palette for blush and rewrites the seasonal notes.
 */

return [
    'sections' => [
        'hero_collage' => [
            'label' => 'Hero photo collage (floating polaroids)',
            'default' => true,
        ],
        'seasonal_strip' => [
            'label' => 'Seasonal value-props strip (italic notes)',
            'default' => true,
        ],
    ],

    'motifs' => [
        'washi_tape' => [
            'label' => 'Washi-tape strips on cards',
            'default' => true,
        ],
        'polaroid_tilt' => [
            'label' => 'Polaroid tilt on product cards',
            'default' => true,
        ],
    ],

    'content' => [
        'hero_sub' => [
            'label' => 'Hero — subheading',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.hero.sub',
        ],
        'seasonal_1' => [
            'label' => 'Seasonal strip — first note',
            'type' => 'text',
            'default_lang' => 'site.storefront.value_props.shipping_title',
        ],
        'seasonal_2' => [
            'label' => 'Seasonal strip — second note',
            'type' => 'text',
            'default_lang' => 'site.storefront.value_props.returns_title',
        ],
        'seasonal_3' => [
            'label' => 'Seasonal strip — third note',
            'type' => 'text',
            'default_lang' => 'site.storefront.value_props.checkout_title',
        ],
    ],

    'palettes' => [
        'sage' => [
            'name' => 'Sage cream (default)',
            'vars' => [],
        ],
        'blush' => [
            'name' => 'Blush — warm rose',
            'vars' => [
                '--bg' => '#f3ecea', '--soft' => '#ece0dd', '--soft2' => '#e2d1cc',
                '--card' => '#fdf8f6', '--line' => '#ddcac4', '--muted' => '#8a7570',
                '--ink' => '#33251f', '--deep' => '#2b1e19',
                '--tape' => 'rgba(190, 130, 120, .28)',
            ],
        ],
        'fern' => [
            'name' => 'Fern — deeper green',
            'vars' => [
                '--bg' => '#e4e9d8', '--soft' => '#d6ddc2', '--soft2' => '#c8d1ae',
                '--card' => '#f6f9ec', '--line' => '#c2cca8', '--muted' => '#6c7659',
                '--ink' => '#202a17', '--deep' => '#1a2312',
                '--tape' => 'rgba(100, 125, 75, .32)',
            ],
        ],
    ],

    'fonts' => [
        'dmserif' => [
            'name' => 'DM Serif Display (default)',
            'vars' => [],
            'link' => null,
        ],
        'cormorant' => [
            'name' => 'Cormorant — delicate, all-serif',
            // Already in Posy's base font stylesheet — zero extra requests.
            'vars' => ['--display' => '"Cormorant Garamond", serif'],
            'link' => null,
        ],
        'playfair' => [
            'name' => 'Playfair Display — high-contrast editorial',
            'vars' => ['--display' => '"Playfair Display", serif'],
            'link' => 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400&display=swap',
        ],
    ],
];
