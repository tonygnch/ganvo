<?php

/*
 | Terra (slug "gallery") — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles (with editable label text), and content fields
 | whose defaults fall back to the platform copy. Read by ThemeCustomizer;
 | edited via the Store Admin "Customize theme" page.
 |
 | Terra ships as a warm tactile lifestyle store, but nothing here is bound
 | to that: a plant shop relabels the "Featured" chip to "New in", a strict
 | catalog store turns the story band and newsletter off entirely.
 */

return [
    'sections' => [
        'value_strip' => [
            'label' => 'Value-props strip under the hero',
            'default' => true,
        ],
        'featured_grid' => [
            'label' => 'Featured products grid',
            'default' => true,
        ],
        'category_splits' => [
            'label' => 'Category split panels',
            'default' => true,
        ],
        'story_band' => [
            'label' => 'Editorial story band',
            'default' => true,
        ],
        'newsletter' => [
            'label' => 'Newsletter sign-up block',
            'default' => true,
        ],
    ],

    'motifs' => [
        'hero_float' => [
            'label' => 'Floating product card over the hero image',
            'default' => true,
        ],
        'featured_badge' => [
            'label' => 'Chip on the first featured product',
            'default' => true,
            'text_label' => 'What the chip says',
            'text_default_lang' => 'site.storefront.featured.badge',
        ],
    ],

    'content' => [
        'shop_heading' => [
            'label' => 'Shop-all catalog heading',
            'type' => 'text',
            'default_lang' => 'site.storefront.shop_all.h2',
        ],
        'story_body' => [
            'label' => 'Story band — body text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.promo.p',
        ],
        'news_body' => [
            'label' => 'Newsletter block — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.footer.tagline',
        ],
    ],

    'palettes' => [
        'stone' => [
            'name' => 'Stone (default)',
            'vars' => [],
        ],
        'clay' => [
            'name' => 'Clay — warmer, terracotta-lean',
            'vars' => [
                '--bg' => '#f6ece1', '--ink' => '#3a2d23', '--soft' => '#eed9c5',
                '--soft2' => '#e5ccb2', '--card' => '#fcf5ec', '--line' => '#e1c8ae',
                '--muted' => '#997f68',
            ],
        ],
        'slate' => [
            'name' => 'Slate — cooler, mineral light',
            'vars' => [
                '--bg' => '#eef0f0', '--ink' => '#2d3236', '--soft' => '#dde3e4',
                '--soft2' => '#d0d8da', '--card' => '#f8fafa', '--line' => '#ccd5d7',
                '--muted' => '#7c878e',
            ],
        ],
    ],

    'fonts' => [
        'bricolage' => [
            'name' => 'Bricolage Grotesque (default)',
            'vars' => [],
            'link' => null,
        ],
        'gabarito' => [
            'name' => 'Gabarito — rounder, friendlier',
            'vars' => ['--display' => '"Gabarito", sans-serif'],
            'link' => 'https://fonts.googleapis.com/css2?family=Gabarito:wght@500;600;700&display=swap',
        ],
    ],
];
