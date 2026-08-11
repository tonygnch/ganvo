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
        // The workshop band used to sit on the landing page. The merchant asked
        // for the family and the history to live in one place, so it moved
        // wholesale onto /about — this switch follows it there rather than
        // being retired, because a merchant who turned it off should stay off.
        'story_band' => [
            'label' => 'Heritage band on the About page (photograph + manifesto)',
            'default' => true,
        ],
        // What replaced it on the landing page: capabilities, then reasons.
        'offer_band' => [
            'label' => 'Capabilities grid (what the yard can do)',
            'default' => true,
        ],
        'why_band' => [
            'label' => 'Reasons to choose us (+ the counted facts)',
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
        // Key kept as 'gutter_index' so stores that already saved this toggle
        // keep their setting — but it is no longer an index. The № prefix and
        // the 01/02/03 numbering came off at the owner's request; what is left
        // is the section's name, set once, vertically, in the left margin.
        'gutter_index' => [
            'label' => 'Gutter label — the vertical section name in the left margin',
            'default' => true,
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
        /* A chain-of-custody mark is a claim a buyer can verify, so it is
           toggleable rather than hard-coded: a merchant whose certification
           lapses must be able to take it down themselves, without waiting on us
           and without leaving a false claim in the footer meanwhile. */
        /* The brand line above the hero headline. Off for a merchant whose
           headline stands on its own — it otherwise repeats what the header
           says a couple of centimetres higher. */
        'hero_mark' => [
            'label' => 'Brand line above the hero headline',
            'default' => true,
        ],
        'brand_cert' => [
            'label' => 'Certification mark in the footer',
            'default' => true,
        ],
    ],

    'content' => [
        'hero_note' => [
            'label' => 'Hero — the capability line under the opening paragraph',
            'type' => 'text',
            'default_lang' => 'site.storefront.sankevi.hero_note',
        ],
        'forest_quote' => [
            'label' => 'Forest divider — the pull quote',
            'type' => 'textarea',
            'default_lang' => 'site.storefront.sankevi.forest_quote',
        ],
        /* The licence NUMBER belongs to the merchant, not to the theme, and it
           changes when a certificate is renewed under a new body. Keeping it as
           editable copy means that renewal is a text edit, not a deploy. */
        'cert_code' => [
            'label' => 'Certification licence code',
            'type' => 'text',
            'default_lang' => 'site.storefront.sankevi.cert_code',
        ],
        /* The heading used to be read straight out of the lang file by the
           Blade, so a merchant could rewrite the paragraph under it but not the
           line itself — which left Sankevi with our invented "one family, one
           saw" headline sitting on top of their real story. It is a copy slot
           now, like every other line on the page. */
        /* THE THREE COUNTERS in the "why us" band.
           They used to be hard-coded in the Blade — a 40 km sourcing radius and
           a 12% kiln moisture, described in the code as "yard constants". They
           are nothing of the kind: they are claims about a particular business,
           and for a merchant who does not fell their own timber they are simply
           false. Sankevi shipped with all three of them on the page.

           Value and label travel together as slots, because splitting them was
           the other half of the bug: with the number in code and the label in
           the lang file, correcting one silently left the other describing
           something else.

           The value is free text ("25+", "12%", "5") — the Blade takes the
           leading digits for the count-up animation and keeps the rest as the
           suffix, so a merchant types what they want to see rather than filling
           in two fields. A value with no digits simply does not animate. */
        'fact_1_value' => [
            'label' => 'Why-us counter 1 — value',
            'hint' => 'e.g. 25+ — digits animate, anything after them is shown as a suffix.',
            'type' => 'text',
            'default_lang' => 'site.storefront.sankevi.fact_1_value',
        ],
        'fact_1_label' => [
            'label' => 'Why-us counter 1 — label',
            'type' => 'text',
            'default_lang' => 'site.storefront.sankevi.fact_1_label',
        ],
        'fact_2_value' => [
            'label' => 'Why-us counter 2 — value',
            'type' => 'text',
            'default_lang' => 'site.storefront.sankevi.fact_2_value',
        ],
        'fact_2_label' => [
            'label' => 'Why-us counter 2 — label',
            'type' => 'text',
            'default_lang' => 'site.storefront.sankevi.fact_2_label',
        ],
        'fact_3_value' => [
            'label' => 'Why-us counter 3 — value',
            'type' => 'text',
            'default_lang' => 'site.storefront.sankevi.fact_3_value',
        ],
        'fact_3_label' => [
            'label' => 'Why-us counter 3 — label',
            'type' => 'text',
            'default_lang' => 'site.storefront.sankevi.fact_3_label',
        ],
        /* site.storefront.footer.tagline is shared by EVERY theme, so editing
           it to suit one merchant changes the footer of every store on the
           platform. A slot of its own, the way wick and forma already do it. */
        'footer_tagline' => [
            'label' => 'Footer tagline',
            'type' => 'text',
            'default_lang' => 'site.storefront.footer.tagline',
        ],
        'story_h2_html' => [
            'label' => 'Workshop band — heading (HTML allowed)',
            'type' => 'text',
            'default_lang' => 'site.storefront.sankevi.story_h2_html',
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
                // The catalogue right way up: its paper is the ground and the
                // green its logo is printed in is the ink. Same four colours as
                // the dark variant, swapped over.
                '--bg' => '#f6e4cf',
                '--surface' => '#fdf7ec',
                '--surface2' => '#eeddc3',
                '--line' => '#e0cdb0',
                '--line2' => '#c6b18f',
                '--txt' => '#072922',
                '--muted' => '#4a5a4a',
                // Darkened from #7d7461, which measured 3.96:1 on the Daylight
                // ground — under AA for the 10.5–11px letterspaced labels this
                // token exists to set (the gutter index, the capability
                // numerals, the counted-fact captions, every .kicker).
                '--faint' => '#5f6b57',
                // The footer/drawer slab must RISE off a pale page, not sink
                // below it, or both lose their edge entirely.
                '--deep' => '#071c15',
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
            // THE CLIENT'S OWN MARK, lifted from their trade catalogue — the
            // chevron tree above SANKEVI LTD. It replaces the S-as-sawn-boards
            // seal we drew before we had ever seen their branding; that file
            // stays in the repo (mark.svg / mark-cream.svg) rather than being
            // deleted, because it is still the only VECTOR mark we have and
            // the client has not sent artwork yet.
            //
            // Cream colourway by default: the theme is dark-native and the seal
            // sits on the forest header, the hero plate and the footer
            // watermark. mark-forest.png is the pair for light grounds.
            'default' => '/images/brand/sankevi/mark-cream.png',
        ],
        'cert_image' => [
            'label' => 'Certification mark',
            'hint' => 'Shown in the footer beside the licence code. The footer is dark in both modes, so a light-on-dark artwork is correct.',
            'size' => '370×512',
            'default' => '/images/brand/sankevi/fsc-cream.png',
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

    // The theme ships INDUSTRIAL: Oswald, a condensed signage gothic, over IBM
    // Plex Sans, an engineering face. Every alternate below has to clear the
    // same bar the default did — real Cyrillic — because this storefront is
    // Bulgarian first, and that alone rules out most of the obvious display
    // gothics (Anton, Bebas Neue, Archivo Black, Barlow Condensed, Antonio,
    // Big Shoulders, Saira Condensed and Space Grotesk are all Latin-only).
    //
    // Note for anyone adding one: the theme sets its <em> accents in WEIGHT
    // rather than italic, because Oswald has no italic and a browser would
    // otherwise fake a different oblique per engine. A pairing that does have
    // italics loses nothing by that — the accent simply reads as bold.
    'fonts' => [
        'oswald' => [
            'name' => 'Oswald + IBM Plex Sans (default) — industrial',
            'vars' => [],
            'link' => null,
        ],
        'plex_condensed' => [
            'name' => 'IBM Plex Condensed — engineered, quieter than Oswald',
            'vars' => [
                '--display' => '"IBM Plex Sans Condensed", "Arial Narrow", sans-serif',
            ],
            'link' => 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Condensed:wght@300;400;500;600;700&display=swap',
        ],
        'fira_condensed' => [
            'name' => 'Fira Condensed — technical, a touch softer',
            'vars' => [
                '--display' => '"Fira Sans Extra Condensed", "Arial Narrow", sans-serif',
            ],
            'link' => 'https://fonts.googleapis.com/css2?family=Fira+Sans+Extra+Condensed:wght@300;400;500;600;700&display=swap',
        ],
        'russo' => [
            'name' => 'Russo One — heavy machine plate (headings only)',
            'vars' => [
                '--display' => '"Russo One", "Arial Black", sans-serif',
            ],
            'link' => 'https://fonts.googleapis.com/css2?family=Russo+One&display=swap',
        ],
        // The theme's original voice, kept for a merchant who wants the mill
        // to read as a workshop rather than a plant. Restores BOTH faces.
        'alegreya' => [
            'name' => 'Alegreya + Commissioner — the original serif',
            'vars' => [
                '--display' => '"Alegreya", Georgia, "Times New Roman", serif',
                '--body' => '"Commissioner", "Helvetica Neue", sans-serif',
            ],
            'link' => 'https://fonts.googleapis.com/css2?family=Alegreya:ital,wght@0,400;0,500;0,700;0,800;1,400;1,500&family=Commissioner:wght@300;400;500;600&display=swap',
        ],
        'literata' => [
            'name' => 'Literata + Commissioner — steadier, more bookish',
            'vars' => [
                '--display' => '"Literata", Georgia, serif',
                '--body' => '"Commissioner", "Helvetica Neue", sans-serif',
            ],
            'link' => 'https://fonts.googleapis.com/css2?family=Literata:ital,opsz,wght@0,7..72,400..700;1,7..72,400..500&family=Commissioner:wght@300;400;500;600&display=swap',
        ],
    ],
];
