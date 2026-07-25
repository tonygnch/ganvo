<?php

/*
 | Sankevi — theme customization manifest.
 |
 | Declares what a merchant may change about this theme beyond the global
 | store settings: named palette presets, font pairings, section toggles,
 | signature-motif toggles (with editable label text), image slots and
 | content fields whose defaults fall back to the platform copy. Read by
 | ThemeCustomizer; edited via the Store Admin "Customize theme" page.
 |
 | Sankevi ships as a Rhodope sawmill, but nothing here is sawdust-bound:
 | a stone quarry keeps the species band as a materials guide, a ceramics
 | studio keeps the plate numbers and drops the trade slab.
 */

return [
    'sections' => [
        'ledger_strip' => [
            'label' => 'Ledger strip (facts band under the hero)',
            'default' => true,
        ],
        'species_band' => [
            'label' => 'Species guide (four timbers + density gauges)',
            'default' => true,
        ],
        'forest_band' => [
            'label' => 'Full-bleed forest divider with pull quote',
            'default' => true,
        ],
        'story_band' => [
            'label' => 'Workshop band (photograph + manifesto)',
            'default' => true,
        ],
        'trade_band' => [
            'label' => 'Trade & sites callout (the moss slab)',
            'default' => true,
        ],
        'news_band' => [
            'label' => 'Price-list newsletter',
            'default' => true,
        ],
        // The catalogue at /shop is a separate destination from the landing
        // page, so it gets its own switch: off leaves the stock book opening
        // on type alone, which is what a merchant with no wide shot wants.
        'shop_masthead' => [
            'label' => 'Shop masthead (photographic cover over the stock book)',
            'default' => true,
        ],
    ],

    'motifs' => [
        'planed_corner' => [
            'label' => 'Planed corner — the chamfer cut off photographs, panels and buttons',
            'default' => true,
        ],
        'gutter_index' => [
            'label' => 'Gutter index — the vertical section label in the left margin',
            'default' => true,
            'text_label' => 'Index prefix',
            'text_default' => '№',
        ],
        'sheet_marks' => [
            'label' => 'Sheet numbers on product cards and cart lines',
            'default' => true,
            'text_label' => 'Sheet prefix',
            'text_default' => '№',
        ],
        'brand_seal' => [
            'label' => 'Brand seal (nav mark, hero plate, footer watermark)',
            'default' => true,
        ],
        'bark_texture' => [
            'label' => 'Bark grain over the whole page',
            'default' => true,
        ],
    ],

    'content' => [
        'hero_note' => [
            'label' => 'Hero plate — the line under the product name',
            'type' => 'text',
            'default_lang' => 'site.storefront.sankevi.hero_note',
        ],
        'forest_quote' => [
            'label' => 'Forest divider — the pull quote',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.sankevi.forest_quote',
        ],
        'story_body' => [
            'label' => 'Workshop band — manifesto text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.sankevi.story_body',
        ],
        'trade_body' => [
            'label' => 'Trade & sites — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.sankevi.trade_body',
        ],
        'news_body' => [
            'label' => 'Newsletter — text',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.promo.p',
        ],
    ],

    // Sankevi is dark-native: the bark ground IS the theme. "Daylight" is the
    // visitor toggle — the same yard at ten in the morning. Moss and the ink
    // that sits on the accent stay put, because both are materials rather than
    // backgrounds and have to survive either ground.
    'modes' => [
        'light' => [
            'name' => 'Daylight',
            'vars' => [
                '--bg' => '#f2ede1',
                '--surface' => '#faf6ec',
                '--surface2' => '#e8e1d1',
                '--line' => '#d8cfbb',
                '--line2' => '#bdb199',
                '--txt' => '#1b1810',
                '--muted' => '#5c5545',
                '--faint' => '#7d7461',
                // The footer/drawer slab must RISE off a pale page, not sink
                // below it, or both lose their edge entirely.
                '--deep' => '#221e16',
            ],
        ],
    ],

    'images' => [
        'hero_image' => [
            'label' => 'Hero photograph',
            'hint' => 'Wide shot — the yard, the stacks, the mountain. Ships with the dawn yard.',
            'size' => '1920×1080',
            'default' => '/images/demo/sankevi/yard.webp',
        ],
        'forest_image' => [
            'label' => 'Forest divider photograph',
            'hint' => 'Full-bleed band behind the pull quote. Landscape, low contrast reads best.',
            'size' => '1800×1200',
            'default' => '/images/demo/sankevi/forest.webp',
        ],
        'story_image' => [
            'label' => 'Workshop band photograph',
            'hint' => 'Hands, tools, shavings — the making, not the product.',
            'size' => '1200×900',
            'default' => '/images/demo/sankevi/workshop.webp',
        ],
        'shop_image' => [
            'label' => 'Shop masthead photograph',
            'hint' => 'The cover of the catalogue at /shop. Very wide crop — the band is short and full-bleed. Ships with the end-grain stack.',
            'size' => '2100×900',
            'default' => '/images/demo/sankevi/endgrain.webp',
        ],
        'seal_image' => [
            'label' => 'Brand seal',
            'hint' => 'Small square mark. Used in the nav when no logo is set, on the hero plate and behind the footer.',
            'size' => '400×400',
            // Cream colourway: the theme is dark-native and the seal sits on the
            // bark header, the hero plate and the footer watermark. mark.svg is
            // the walnut master for light grounds and print.
            'default' => '/images/brand/sankevi/mark-cream.svg',
        ],
    ],

    'palettes' => [
        'bark' => [
            'name' => 'Bark (default)',
            'vars' => [],
        ],
        'ash' => [
            'name' => 'Ash — cooler, smoke over the yard',
            'vars' => [
                '--bg' => '#131414', '--surface' => '#1b1d1c', '--surface2' => '#232625',
                '--line' => '#2f3331', '--line2' => '#434947', '--muted' => '#a8adaa',
                '--faint' => '#7f8683', '--moss' => '#3a4a3f', '--moss-soft' => '#48594c',
                '--deep' => '#0e100f',
            ],
        ],
        'umber' => [
            'name' => 'Umber — warmer, closer to the heartwood',
            'vars' => [
                '--bg' => '#1a1309', '--surface' => '#241a0e', '--surface2' => '#2e2213',
                '--line' => '#3b2c19', '--line2' => '#523d23', '--muted' => '#bcab8f',
                '--faint' => '#918066', '--moss' => '#454c2c', '--moss-soft' => '#555e37',
                '--deep' => '#130e06',
            ],
        ],
    ],

    'fonts' => [
        'alegreya' => [
            'name' => 'Alegreya (default)',
            'vars' => [],
            'link' => null,
        ],
        'literata' => [
            'name' => 'Literata — steadier, more bookish',
            'vars' => [
                '--display' => '"Literata", Georgia, serif',
            ],
            'link' => 'https://fonts.googleapis.com/css2?family=Literata:ital,opsz,wght@0,7..72,400..700;1,7..72,400..500&display=swap',
        ],
        'yeseva' => [
            'name' => 'Yeseva One — louder, more decorative',
            'vars' => [
                '--display' => '"Yeseva One", Georgia, serif',
            ],
            'link' => 'https://fonts.googleapis.com/css2?family=Yeseva+One&display=swap',
        ],
    ],
];
