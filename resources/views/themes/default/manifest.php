<?php

/*
 | Atelier (default) — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles (with editable label text), and content fields
 | whose defaults fall back to the platform copy. Read by ThemeCustomizer;
 | edited via the Store Admin "Customize theme" page.
 |
 | Atelier ships as an editorial fashion magazine, but nothing here is
 | fashion-bound: a bookshop keeps the lookbook as its front table, a
 | jeweller swaps the marquee star for a diamond.
 |
 | This is the platform's default theme — the surface is deliberately
 | conservative so untouched stores render exactly as before.
 */

return [
    'sections' => [
        'brand_marquee' => [
            'label' => 'Brand marquee (scrolling name strip)',
            'default' => true,
        ],
        'lookbook' => [
            'label' => 'Lookbook rail (horizontal editorial scroll)',
            'default' => true,
        ],
        'film_block' => [
            'label' => 'Editorial dark split (image + promo copy)',
            'default' => true,
        ],
        'colophon' => [
            'label' => 'Newsletter colophon (bottom of home)',
            'default' => true,
        ],
    ],

    'motifs' => [
        'marquee_star' => [
            'label' => 'Star separators in the brand marquee (✶)',
            'default' => true,
            'text_label' => 'Separator character',
            'text_default' => '✶',
        ],
        'ken_burns' => [
            'label' => 'Slow Ken Burns drift on the hero image',
            'default' => true,
        ],
        'scroll_progress' => [
            'label' => 'Accent scroll-progress bar (top of page)',
            'default' => true,
        ],
    ],

    'content' => [
        'lookbook_hint' => [
            'label' => 'Lookbook rail — scroll hint',
            'type' => 'text',
            'default_lang' => 'site.storefront.lookbook.hint',
        ],
        'film_body' => [
            'label' => 'Editorial dark split — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.promo.p',
        ],
        'colophon_body' => [
            'label' => 'Newsletter colophon — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.footer.tagline',
        ],
    ],

    'palettes' => [
        'paper' => [
            'name' => 'Paper (default)',
            'vars' => [],
        ],
        'ivory' => [
            'name' => 'Ivory — lighter, cooler',
            'vars' => [
                '--paper' => '#f2f0ea', '--soft' => '#e6e3da', '--soft2' => '#d9d5c9',
                '--line' => '#dad6cc', '--muted' => '#8a877c', '--ink' => '#131311',
                '--rule' => '#13131122',
            ],
        ],
        'noir' => [
            'name' => 'Noir paper — deeper, warmer',
            'vars' => [
                '--paper' => '#e5dccb', '--soft' => '#d5c9b2', '--soft2' => '#c6b79b',
                '--line' => '#c9bda2', '--muted' => '#7d735e', '--ink' => '#0e0c08',
                '--rule' => '#0e0c0822',
            ],
        ],
    ],

    'fonts' => [
        'cormorant' => [
            'name' => 'Cormorant Garamond (default)',
            'vars' => [],
            'link' => null,
        ],
        'fraunces' => [
            'name' => 'Fraunces — wonky old-style serif',
            'vars' => ['--serif' => '"Fraunces", serif'],
            'link' => 'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..700&display=swap',
        ],
        'bricolage' => [
            'name' => 'Bricolage Grotesque headlines — modern, editorial-sans',
            // Already in Atelier's base font stylesheet — zero extra requests.
            'vars' => ['--display' => '"Bricolage Grotesque", sans-serif'],
            'link' => null,
        ],
    ],
];
