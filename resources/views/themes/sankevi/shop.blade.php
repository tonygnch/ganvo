@php
    /*
     | Sankevi — THE STOCK BOOK (/shop).
     |
     | The landing page is the forest; this is the yard's own book. Its
     | existence is what splits the two: StorefrontController renders
     | themes.{theme}.shop whenever the shopper is browsing (searching,
     | filtering, paginating) or asked for /shop outright, and falls back to
     | index() for themes that ship no such view.
     |
     | The device is a LEDGER, not a grid: one full-width band per item, the
     | photograph bleeding off the outer edge, the name set large in the
     | serif, the mill's spec column beside it and the price at the far edge.
     | Bands alternate which side they bleed from, so the page reads like a
     | book opened flat rather than a wall of cards.
     |
     | Contract with the controller (unchanged, and pagination depends on it):
     | q · sort · category · min_price · max_price · in_stock, all GET, all
     | round-tripped through this page's own form and chips.
     */
    $title = __('site.storefront.product.breadcrumb_shop');

    // Every band prints its section and its choice axes. Without this the
    // paginator would fire two queries per row; the paginator forwards
    // unknown calls to its collection, so this loads exactly the page shown.
    $products->loadMissing(['categories', 'options.values']);

    // Prices travel in cents but the form speaks major units, like the
    // controller's own toCents() expects.
    $minPriceInput = $filters['min_price'] !== null ? number_format($filters['min_price'] / 100, 2, '.', '') : '';
    $maxPriceInput = $filters['max_price'] !== null ? number_format($filters['max_price'] / 100, 2, '.', '') : '';
    $activeCategory = $filters['category'] ?? null;

    // The page is a section of the book when a family is chosen, so it takes
    // that family's name — in the browser tab, the masthead and the crumb.
    // Leaving the generic heading up made every section look like the same
    // page and told the tab nothing.
    $activeCat = $activeCategory ? $categories->firstWhere('slug', $activeCategory) : null;
    $isSearch = filled($filters['q'] ?? null);
    if ($activeCat) {
        $title = $activeCat->name;
    } elseif ($isSearch) {
        $title = __('site.storefront.controls.search') . ': ' . $filters['q'];
    }
    $activeSort = $filters['sort'] ?? 'category';
    $hasActiveFilters = $filters['q']
        || $activeCategory
        || $filters['min_price'] !== null
        || $filters['max_price'] !== null
        || $filters['in_stock']
        || $activeSort !== 'category';

    /* How many filters are actually on, for the badge on the drawer's button.
       A count, not a dot: "3" tells a shopper the list they are looking at has
       been narrowed three ways, which is the thing they forget after scrolling.
       The category is not counted — it is already shown as a selected chip. */
    $activeFilterCount = collect([
        filled($filters['q'] ?? null),
        ($filters['sort'] ?? 'category') !== 'category',
        filled($minPriceInput ?? null),
        filled($maxPriceInput ?? null),
        (bool) ($filters['in_stock'] ?? false),
    ])->filter()->count();

    /*
     | Section chips swap ONE key and keep everything else the shopper has
     | already narrowed to — same querystring contract as the form, so the
     | two are interchangeable. `page` is deliberately dropped: a new section
     | starts at its own first sheet.
     */
    $chipUrl = function (?string $slug) use ($filters, $activeSort, $minPriceInput, $maxPriceInput) {
        $params = array_filter([
            'q'         => $filters['q'],
            'category'  => $slug,
            'min_price' => $minPriceInput ?: null,
            'max_price' => $maxPriceInput ?: null,
            'in_stock'  => $filters['in_stock'] ? '1' : null,
            'sort'      => $activeSort !== 'category' ? $activeSort : null,
        ], fn ($v) => $v !== null && $v !== '');

        return '/shop' . ($params ? '?' . http_build_query($params) : '');
    };

    $coverUrl = $theme->on('shop_masthead') ? $theme->image('shop_image') : null;
    $sheetMark = $theme->on('sheet_marks') ? trim($theme->label('sheet_marks')) : null;
    $csContactOn = $store->contactPage()['enabled'];
@endphp
@extends('themes.sankevi.layout')

@section('content')
    <style>
        /*
         | ===== THE COVER — a short, very wide photographic band with the
         | title standing on its floor. Full-bleed by simply not living in a
         | .wrap; the scrim is mixed from --bg so it veils dark on bark and
         | pale in daylight without a second declaration.
         */
        .cover { position: relative; overflow: hidden; display: flex; align-items: flex-end; min-height: clamp(320px, 42vh, 500px); padding: clamp(48px, 7vw, 92px) 0 clamp(26px, 3.4vw, 46px); }
        .cover .shot { position: absolute; inset: -7% 0; z-index: 0; }
        .cover .shot img { width: 100%; height: 100%; object-fit: cover; }
        .cover.has-shot::after {
            content: ""; position: absolute; inset: 0; z-index: 1; pointer-events: none;
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--bg) 26%, transparent), color-mix(in srgb, var(--bg) 66%, transparent) 44%, var(--bg) 99%),
                linear-gradient(90deg, color-mix(in srgb, var(--bg) 84%, transparent), color-mix(in srgb, var(--bg) 44%, transparent) 54%, transparent 82%);
        }
        .cover:not(.has-shot) { min-height: 0; border-bottom: 1px solid var(--line); }
        /* the band is a flex container, so the content column has to be told
           to fill it — a shrink-to-fit .wrap would drift off the site's grid */
        .cover .wrap { position: relative; z-index: 2; width: 100%; }
        /* the gutter index hangs off THIS box, not off the full-bleed band,
           so it lands in the page margin exactly as it does elsewhere */
        .cover .head { position: relative; }
        .cover .crumb { font-size: 11px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }
        .cover .crumb a:hover { color: var(--accent-ink); }
        .cover .kicker { display: block; margin: 22px 0 12px; }
        .cover h1 { font-family: var(--display); font-weight: 500; font-size: clamp(31px, 4.4vw, 60px); line-height: .98; letter-spacing: 0; max-width: 16ch; }
        .cover h1 em { font-style: normal; font-weight: 600; color: var(--accent-ink); }
        .cover p { color: var(--muted); max-width: 54ch; margin-top: 16px; font-size: 15.5px; }

        /* ===== THE RAIL — sections, tally and the order form. No panel: a
           ruled strip, the way the yard's own paperwork is ruled. ===== */
        .rail { padding: 30px 0 0; }
        .rail .sections { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        .rail .chips { display: flex; gap: 9px; flex-wrap: wrap; min-width: 0; }
        .rail .chips .pill { transition: color .25s ease, border-color .25s ease, background-color .25s ease; }
        .rail .chips .pill:hover { border-color: var(--accent); color: var(--accent-ink); }
        .rail .chips .pill.on, .rail .chips .pill.on:hover { background: var(--accent); border-color: var(--accent); color: var(--on-accent); }
        .rail .tally { margin-left: auto; display: flex; align-items: baseline; gap: 12px; font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .rail .tally .q { color: var(--accent-ink); text-transform: none; letter-spacing: .04em; font-size: 12px; max-width: 22ch; overflow: hidden; text-overflow: ellipsis; }

        /* the order form — bare fields on hairlines, labels in the spec voice */
        /* Ruled off the chips above it and nothing else: the stock book below
           opens with its own full-width rule, so a second one here landed 4px
           short of it, inset against a band that bleeds — two lines where the
           page only ever meant one. */
        .sift { display: grid; grid-template-columns: minmax(0, 1.9fr) minmax(0, 1.15fr) minmax(0, .62fr) minmax(0, .62fr) auto auto; gap: 0 22px; align-items: end; margin-top: 26px; padding: 22px 0 24px; border-top: 1px solid var(--line); }
        .sift .f { display: flex; flex-direction: column; gap: 8px; min-width: 0; }
        .sift .f > span { font-size: 10px; font-weight: 500; letter-spacing: .24em; text-transform: uppercase; color: var(--faint); }
        .sift input[type="search"], .sift input[type="number"], .sift select {
            width: 100%; min-width: 0; padding: 9px 2px; background-color: transparent; border: 0; border-bottom: 1px solid var(--line2);
            color: var(--txt); font-family: var(--body); font-size: 15px; transition: border-color .25s ease;
        }
        /* the native select needs an explicit ground or the OS paints it white */
            /* background-COLOR, not the shorthand: the layout draws this
               select's chevron as a background-image, and `background:`
               would reset it — leaving a select with no arrow at all,
               since appearance:none has already removed the native one. */
        .sift select {
            background-color: transparent;
            /* this field is an underline, not a box — pull the layout's chevron
               in to the same 2px gutter the text uses, and reserve room for it */
            padding-right: 20px; background-position: right 1px center;
        }
        .sift select option { background: var(--surface); color: var(--txt); }
        .sift input::placeholder { color: var(--faint); }
        .sift input:focus, .sift select:focus { outline: none; border-bottom-color: var(--accent); box-shadow: none; }
        .sift .f-check { flex-direction: row; align-items: center; gap: 10px; padding-bottom: 9px; cursor: pointer; white-space: nowrap; }
        .sift .f-check input { width: 16px; height: 16px; accent-color: var(--accent); flex-shrink: 0; }
        .sift .f-check span { font-size: 11px; font-weight: 500; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); }
        .sift .go { display: flex; align-items: center; gap: 16px; }
        .sift .btn { padding: 12px 24px; }
        .sift .clear { font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); border-bottom: 1px solid transparent; padding-bottom: 3px; white-space: nowrap; transition: color .25s ease, border-color .25s ease; }
        .sift .clear:hover { color: var(--accent-ink); border-color: currentColor; }

        /*
         | ===== THE BOOK — one band per item, full width of the page.
         |
         | The outer gutter is a padding on the band itself, so it lines up
         | with the site's content column WITHOUT touching 100vw: `100%` here
         | resolves against .book (the page width), which is exact whether or
         | not a scrollbar is showing. The un-padded side is where the
         | photograph runs off the edge.
         */
        .book { --gut: max(40px, calc((100% - 1280px) / 2 + 40px)); margin-top: 4px; border-bottom: 1px solid var(--line2); }
        .entry {
            position: relative; display: grid; align-items: stretch; color: inherit;
            grid-template-columns: minmax(0, 40%) minmax(0, 1fr) minmax(168px, auto);
            padding-right: var(--gut); border-top: 1px solid var(--line);
            transition: background-color .5s ease, box-shadow .5s ease;
        }
        .entry.rev { grid-template-columns: minmax(168px, auto) minmax(0, 1fr) minmax(0, 40%); padding-right: 0; padding-left: var(--gut); }
        .entry:hover { background: var(--surface); box-shadow: 0 34px 60px -54px rgba(0, 0, 0, .9); }
        /* All three sit on ONE band. Without the explicit row, a mirrored band
           (whose photograph is placed in the LAST column while the entry keeps
           the middle one) makes auto-placement give up and start a second row. */
        .entry > * { grid-row: 1; }

        /* the plate — bleeds off the outer edge, chamfered on the inner one */
        .entry .plate { grid-column: 1; position: relative; overflow: hidden; min-height: clamp(250px, 26vw, 380px); background: linear-gradient(150deg, var(--surface2), #191510); }
        .entry.rev .plate { grid-column: 3; }
        .entry .plate .shot { position: absolute; inset: -6% 0; }
        .entry .plate img { width: 100%; height: 100%; object-fit: cover; transition: transform 1.3s cubic-bezier(.19, .74, .16, 1), filter .55s ease; }
        /* the photograph sits under a veil until the row is hovered — then it
           warms. Filter and the veil, never a transform on the parallaxed
           element, which the motion kit owns. */
        .entry .plate::after { content: ""; position: absolute; inset: 0; z-index: 2; pointer-events: none; background: linear-gradient(90deg, color-mix(in srgb, var(--deep) 42%, transparent), transparent 62%); opacity: .85; transition: opacity .55s ease; }
        /* the veil is heaviest where the sheet number stands, so it mirrors
           with the band */
        .entry.rev .plate::after { background: linear-gradient(270deg, color-mix(in srgb, var(--deep) 42%, transparent), transparent 62%); }
        .entry:hover .plate::after { opacity: .25; }
        .entry:hover .plate img { transform: scale(1.045); filter: saturate(1.14) brightness(1.07); }
        /* an unphotographed specimen: the board glyph on the grain field, with
           a hairline on the inner edge so the band still has a boundary */
        .entry .plate.ph { display: grid; place-items: center; border-right: 1px solid var(--line); }
        .entry.rev .plate.ph { border-right: 0; border-left: 1px solid var(--line); }
        /* The sheet number, stood on its end against the outer edge. It sits ON
           the photograph in both modes, so it keeps the bark ink and a shadow
           rather than following --txt, which goes dark in daylight. */
        .entry .no { position: absolute; z-index: 3; top: 26px; left: 26px; writing-mode: vertical-rl; text-orientation: mixed; font-size: 10.5px; font-weight: 500; letter-spacing: .32em; text-transform: uppercase; color: #f4efe2; text-shadow: 0 1px 12px rgba(0, 0, 0, .6); display: flex; align-items: center; gap: 12px; }
        .entry .no::after { content: ""; width: 1px; height: 40px; background: linear-gradient(180deg, var(--accent), transparent); }
        .entry.rev .no { left: auto; right: 26px; }

        /* the entry itself */
        .entry .body { grid-column: 2; padding: clamp(34px, 3.4vw, 54px) clamp(24px, 3vw, 48px); display: flex; flex-direction: column; justify-content: center; min-width: 0; }
        .entry .body .sec { font-size: 10.5px; font-weight: 500; letter-spacing: .22em; text-transform: uppercase; color: var(--faint); }
        .entry .body h2 { font-family: var(--display); font-weight: 500; font-size: clamp(25px, 2.8vw, 38px); line-height: 1.04; letter-spacing: 0; margin: 12px 0 0; transition: color .3s ease; }
        .entry:hover .body h2 { color: var(--accent-ink); }
        .entry .body .desc { color: var(--muted); font-size: 14.5px; line-height: 1.6; margin-top: 12px; max-width: 46ch; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        /* the spec column — the tight ruled register, the yard's own hand */
        .entry dl { margin-top: 22px; max-width: 44ch; }
        .entry dl .r { display: grid; grid-template-columns: minmax(0, 9.5em) minmax(0, 1fr); gap: 10px 16px; padding: 7px 0; border-top: 1px solid var(--line); }
        .entry dl .r:first-child { border-top: 1px solid var(--line2); }
        .entry dl dt { font-size: 10px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); align-self: center; }
        .entry dl dd { font-size: 13px; letter-spacing: .04em; color: var(--muted); font-variant-numeric: tabular-nums; min-width: 0; }
        .entry dl dd.out { color: var(--faint); }

        /* the ask — price at the far edge, and the fact that a size has to be
           chosen before there is a real one */
        .entry .ask { grid-column: 3; display: flex; flex-direction: column; align-items: flex-end; justify-content: center; gap: 10px; padding: clamp(34px, 3.4vw, 54px) 0; text-align: right; }
        .entry.rev .ask { grid-column: 1; align-items: flex-start; text-align: left; padding: clamp(34px, 3.4vw, 54px) 0; }
        .entry .ask .choice { font-size: 9.5px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; color: var(--accent-ink); border: 1px solid color-mix(in srgb, var(--accent) 45%, transparent); padding: 5px 10px; clip-path: polygon(7px 0, 100% 0, 100% calc(100% - 7px), calc(100% - 7px) 100%, 0 100%, 0 7px); }
        body.no-cut .entry .ask .choice { clip-path: none; }
        .entry .ask .from { display: block; font-size: 10px; font-weight: 500; letter-spacing: .22em; text-transform: uppercase; color: var(--faint); }
        .entry .ask .pr { font-family: var(--display); font-weight: 500; font-size: clamp(26px, 2.6vw, 38px); line-height: 1; font-variant-numeric: tabular-nums; }
        .entry .ask .open { display: inline-flex; align-items: center; gap: 9px; margin-top: 4px; font-size: 10px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); transition: color .3s ease; }
        .entry .ask .open i { font-style: normal; transition: transform .4s cubic-bezier(.19, .74, .16, 1); }
        .entry:hover .ask .open { color: var(--accent-ink); }
        .entry:hover .ask .open i { transform: translateX(5px); }

        @media (prefers-reduced-motion: reduce) {
            .entry:hover .plate img { transform: none; }
            .entry:hover .ask .open i { transform: none; }
        }

        /* ===== the empty page of the book ===== */
        .book-empty { margin: 64px 0 0; padding: clamp(54px, 7vw, 96px) clamp(24px, 4vw, 60px); border: 1px solid var(--line); display: flex; flex-direction: column; align-items: center; text-align: center; gap: 18px; }
        .book-empty h2 { font-family: var(--display); font-weight: 500; font-size: clamp(26px, 3.2vw, 40px); line-height: 1.04; letter-spacing: 0; }
        .book-empty h2 em { font-style: normal; font-weight: 600; color: var(--accent-ink); }
        .book-empty p { color: var(--muted); max-width: 48ch; }
        .book-empty .acts { display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; margin-top: 8px; }

        /* ===== pagination — the shared partial, re-skinned for the bark
           ground. Two-class selectors so they beat the partial's own rules
           whatever order the stylesheets land in. ===== */
        .book-foot { padding-top: 4px; }
        .book-foot .sp-pager { margin: 44px 0 0; }
        .book-foot .sp-pager-list { gap: 8px; }
        .book-foot .sp-pager-link {
            min-width: 46px; height: 44px; padding: 0 14px; border-radius: 0; background: transparent;
            border: 1px solid var(--line); color: var(--muted); font-family: var(--body); font-size: 10.5px;
            font-weight: 500; letter-spacing: .16em; text-transform: uppercase;
            transition: color .25s ease, border-color .25s ease, background-color .25s ease;
        }
        .book-foot .sp-pager-link:hover { background: transparent; border-color: var(--accent); color: var(--accent-ink); }
        .book-foot .sp-pager-current .sp-pager-link { background: var(--accent); border-color: var(--accent); color: var(--on-accent); }
        .book-foot .sp-pager-disabled .sp-pager-link { opacity: .3; border-color: var(--line); color: var(--muted); }

        @media (max-width: 1180px) {
            .sift { grid-template-columns: minmax(0, 1.6fr) minmax(0, 1.1fr) minmax(0, .6fr) minmax(0, .6fr); gap: 20px 20px; }
            .sift .f-check { grid-column: 1 / 3; padding-bottom: 0; }
            .sift .go { grid-column: 3 / -1; justify-content: flex-end; }
        }
        @media (max-width: 900px) {
            /*
             | The phone reads the band top to bottom: photograph, entry,
             | price. The alternation is switched off — there is no spread to
             | mirror on one column — and the photograph keeps its bleed by
             | running the full width of the page.
             */
            .entry, .entry.rev { grid-template-columns: minmax(0, 1fr); padding: 0; }
            /* A REAL floor, not 0. This used to read `min-height: 0` — cancelling
               the desktop floor and leaving the box with nothing but
               aspect-ratio to stand on. Where aspect-ratio does not resolve on
               a grid item (older iOS Safari has known bugs with exactly this
               combination) the plate collapses to zero height and the row
               renders as text with no photograph, which is precisely the
               reported symptom.

               The floor is deliberately SHORTER than the 16/10 height at every
               width (56vw < 62.5vw), so when aspect-ratio works it still wins
               and the crop is unchanged; the floor only catches the fall. */
            .entry .plate, .entry.rev .plate {
                grid-column: 1; grid-row: 1;
                /* WIDTH FIRST, then the ratio. Without this the plate is 46px
                   narrower than its column on iOS — and only on iOS. Given a
                   min-height and an aspect-ratio, WebKit takes the height as
                   the definite size and derives the WIDTH from the ratio
                   (56vw = 246px, × 16/10 = 394 in a 440 column), where Blink
                   stretches the grid item to the column and derives the height.
                   Both readings are defensible; stating the inline size settles
                   it, and the ratio then does what it was written to do.
                   Invisible in Chrome DevTools at any width — it needs a real
                   WebKit to show up. */
                width: 100%;
                min-height: min(56vw, 260px); aspect-ratio: 16 / 10;
                /* The planed corner stays. It was taken off here once, on the
                   reading that a diagonal bitten out of a full-bleed photograph
                   looks like the photograph failing to reach the edge. The
                   thing that actually looked like a gap was a gap — the plate
                   was 46px short on iOS (see the width above) — and with that
                   fixed the notch reads as what it is on every other surface in
                   the theme. */
            }
            /* top veil for the sheet number, bottom veil to hand the band over
               to the entry beneath it */
            .entry .plate::after, .entry.rev .plate::after { background: linear-gradient(180deg, color-mix(in srgb, var(--deep) 46%, transparent), transparent 30%, transparent 52%, color-mix(in srgb, var(--deep) 42%, transparent)); }
            .entry .body, .entry.rev .body { grid-column: 1; grid-row: 2; padding: 26px 22px 0; }
            /* two ends of one line, with the "there is a size to choose" note
               on its own row above — a grid, so the note hugs its text
               instead of stretching to the full width a flex line would give it */
            .entry .ask, .entry.rev .ask {
                grid-column: 1; grid-row: 3; display: grid; grid-template-columns: auto auto;
                justify-content: space-between; align-items: end; gap: 14px 16px;
                padding: 20px 22px 34px; text-align: left;
            }
            .entry .ask .choice { grid-column: 1 / -1; justify-self: start; }
            .entry .no, .entry.rev .no { top: 18px; left: 18px; right: auto; }
            .cover h1 { max-width: none; }
        }
        /* The trigger and the close only exist on a phone; above 760 the rail
           is a row of controls and needs neither. */
        .sift-open, .sift-close, .sift-veil { display: none; }

        @media (max-width: 760px) {
            .sift-open {
                display: grid; place-items: center;
                /* Low on the edge rather than centred: the middle of the screen
                   is where the page's own headings and photographs are, and the
                   tab sat on top of them. Down here it is also where a thumb
                   already is. */
                position: fixed; left: 0; bottom: 96px;
                z-index: 120;                     /* under the veil (190), over the page */
                width: 48px; height: 52px;        /* a real touch target, 44 minimum */
                padding: 0; border: none; cursor: pointer;
                background: var(--accent); color: var(--on-accent);
                /* the theme's planed corner, on the two edges that face the page */
                clip-path: polygon(0 0, 100% 0, 100% calc(100% - 9px), calc(100% - 9px) 100%, 0 100%);
                box-shadow: 6px 0 22px -12px rgba(0, 0, 0, .55);
            }
            .sift-open svg { width: 21px; height: 21px; }
            /* Out of the way while the panel it opens is open. */
            .sift-open[aria-expanded="true"] { opacity: 0; pointer-events: none; }
            /* INSIDE the button, not hanging off it: the tab is clipped for its
               planed corner, and a clip-path cuts anything outside the box —
               a badge at top:-6px was being erased rather than overlapped. */
            .sift-open .n {
                position: absolute; top: 3px; right: 4px;
                display: grid; place-items: center; min-width: 16px; height: 16px;
                padding: 0 4px; border-radius: 8px;
                /* Cream on the deep green, spelled out rather than taken from
                   --txt: that token follows the colour mode, so in daylight it
                   is dark ink and the number disappeared into the disc behind
                   it. This pair is fixed in both modes, the same one the
                   announcement band uses. */
                background: var(--deep); color: #f1ebdd;
                font-size: 9.5px; font-weight: 600; letter-spacing: 0;
            }
            .sift-veil {
                display: block; position: fixed; inset: 0; z-index: 190;
                background: rgba(10, 10, 10, .45);
            }
            .sift-veil[hidden] { display: none; }

            /* THE SAME PANEL THE BASKET USES — off the right edge, slid in over
               the page, with the page behind it dimmed. Stacked inline these
               controls ran to most of a phone screen, so the shop opened on its
               own filters instead of on any stock. */
            .sift {
                /* Opens from the LEFT, the side its tab is on. A panel that
                   flies in from the far edge makes the button you pressed feel
                   like it belongs to something else; coming from under the tab
                   it reads as that tab unfolding. */
                position: fixed; top: 0; left: 0; bottom: 0; z-index: 200;
                width: min(380px, 90vw);
                /* align-items: stretch, explicitly. The desktop rule is a grid
                   with `align-items: end` — sensible there, where the fields sit
                   on one baseline — but in a column flexbox the cross axis is
                   horizontal, so `end` shrank every field to its text and pinned
                   it to the right edge of the panel. */
                display: flex; flex-direction: column; align-items: stretch; gap: 0;
                margin: 0; padding: 58px 22px 26px;
                background: var(--surface); border-right: 1px solid var(--line2);
                box-shadow: 24px 0 60px -30px rgba(0, 0, 0, .55);
                overflow-y: auto; overscroll-behavior: contain;
                transform: translateX(-102%); visibility: hidden;
                transition: transform .42s cubic-bezier(.19, .7, .16, 1), visibility .42s;
            }
            .sift.is-open { transform: none; visibility: visible; }
            @media (prefers-reduced-motion: reduce) { .sift { transition: none; } }

            .sift .f { margin-bottom: 18px; }
            .sift .go { margin-top: auto; padding-top: 20px; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
            .sift .go .btn { flex: 1; text-align: center; }
            .sift-close {
                display: block; position: absolute; top: 14px; right: 16px;
                background: none; border: none; color: var(--txt);
                font-size: 26px; line-height: 1; padding: 4px 8px; cursor: pointer;
            }
            /* The grid placements these fields used to need are gone with the
               grid: in the drawer they are one column, in order. */
            .rail .tally { margin-left: 0; width: 100%; white-space: normal; }
        }
        @media (max-width: 430px) {
            .entry .body, .entry.rev .body { padding: 22px 18px 0; }
            .entry .ask, .entry.rev .ask { padding: 18px 18px 30px; }
            /* Bulgarian runs long: the register drops to a stacked read
               rather than squeezing a 9.5em label column at 390px. */
            .entry dl .r { grid-template-columns: minmax(0, 1fr); gap: 4px; }
            .sift .go { flex-direction: column; align-items: stretch; gap: 12px; }
            .sift .btn { width: 100%; }
            .sift .clear { text-align: center; }
        }
    </style>

    <main>
        {{-- The cover of the book: one very wide photograph, the title on its floor. --}}
        <section class="cover {{ $coverUrl ? 'has-shot' : '' }}">
            @if ($coverUrl)
                <span class="shot" data-gv-parallax="0.05"><img src="{{ $coverUrl }}" alt=""></span>
            @endif
            <div class="wrap">
                <div class="head">
                    @if ($theme->on('gutter_index'))
                        <span class="gx" aria-hidden="true">{{ __('site.storefront.sankevi.shop_eyebrow') }}</span>
                    @endif
                    <div class="crumb">
                        <a href="/">{{ $tenant->name }}</a> /
                        @if ($activeCat)
                            <a href="{{ $chipUrl(null) }}">{{ __('site.storefront.product.breadcrumb_shop') }}</a> / {{ $activeCat->name }}
                        @else
                            {{ __('site.storefront.product.breadcrumb_shop') }}
                        @endif
                    </div>
                    {{-- The heading is the family when one is chosen; the house
                         line only stands in for "everything". --}}
                    <h1 class="rise" style="--d: .1s;">
                        @if ($activeCat)
                            {{ $activeCat->name }}
                        @elseif ($isSearch)
                            {!! __('site.storefront.sankevi.shop_h1_search_html', ['q' => '<em>' . e($filters['q']) . '</em>']) !!}
                        @else
                            {!! $theme->editable('shop_h1_html') !!}
                        @endif
                    </h1>
                    <p class="rise" style="--d: .22s;">
                        {{ $activeCat && filled($activeCat->description)
                            ? $activeCat->description
                            : __('site.storefront.sankevi.shop_intro') }}
                    </p>
                </div>
            </div>
        </section>

        <div class="wrap">
            <div class="rail">
                {{-- Sections of the yard + the live count. --}}
                <div class="sections">
                    @if ($categories->isNotEmpty())
                        <div class="chips">
                            <a href="{{ $chipUrl(null) }}" class="pill {{ $activeCategory ? '' : 'on' }}">{{ __('site.storefront.sankevi.shop_all') }}</a>
                            {{-- The sections, and — once one is opened — what is
                                 filed inside it.

                                 Subsections stay folded away until their own
                                 section is the one being looked at. Drawn all
                                 the time they are noise on every visit, and
                                 they need a treatment to tell them from the
                                 sections; drawn in context they are plainly
                                 the inside of the chip beside them, and can be
                                 the same pill as everything else.

                                 A subsection's own chip keeps them open, or
                                 narrowing to one would take away the way
                                 back to its siblings. --}}
                            @foreach ($categories as $cat)
                                <a href="{{ $chipUrl($cat->slug) }}" class="pill {{ $activeCategory === $cat->slug ? 'on' : '' }}"
                                   @if ($activeCategory === $cat->slug) aria-current="true" @endif>{{ $cat->name }}</a>
                                @if ($activeCategory === $cat->slug || $cat->children->contains('slug', $activeCategory))
                                    @foreach ($cat->children as $sub)
                                        <a href="{{ $chipUrl($sub->slug) }}" class="pill {{ $activeCategory === $sub->slug ? 'on' : '' }}"
                                           @if ($activeCategory === $sub->slug) aria-current="true" @endif>{{ $sub->name }}</a>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    @endif
                    <div class="tally">
                        <span>{{ trans_choice('site.storefront.controls.result_count', $products->total(), ['count' => $products->total()]) }}</span>
                        @if ($filters['q'])<span class="q">“{{ $filters['q'] }}”</span>@endif
                    </div>
                </div>

                {{-- The order form. Every key the controller reads is here, and
                     `category` rides along hidden so searching never silently
                     drops the section the shopper is standing in. --}}
                {{-- MOBILE: the filters move into a drawer, opened from here.
                     Stacked inline they ran to most of a phone screen — search,
                     sort, two prices, a checkbox and a button — so the shop
                     opened on its own controls rather than on any stock. This
                     is the same slide-in the basket uses, because it is the
                     same idea: a panel of things to set, over the page, done
                     with when you are done with it. Rendered on every width and
                     hidden above 760, so no JS decides what exists. --}}
                {{-- A TAB ON THE EDGE, not a button in the flow. It has to be
                     reachable from anywhere in a long list of stock, so it rides
                     with the screen rather than sitting where the rail happens
                     to be. Icon-only, so it takes no width from the page — the
                     name is on the button for anything that reads it aloud. --}}
                <button type="button" class="sift-open" data-sift-open
                        aria-expanded="false" aria-controls="gv-sift"
                        aria-label="{{ trim(strip_tags((string) $theme->copy('shop_refine'))) }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                         stroke-linecap="round" aria-hidden="true">
                        <path d="M3 7h6M15 7h6M3 12h12M19 12h2M3 17h3M12 17h9"/>
                        <circle cx="12" cy="7" r="2.2"/><circle cx="17" cy="12" r="2.2"/><circle cx="9" cy="17" r="2.2"/>
                    </svg>
                    @if ($activeFilterCount ?? 0)<span class="n">{{ $activeFilterCount }}</span>@endif
                </button>
                <div class="sift-veil" data-sift-veil hidden></div>
                <form class="sift" id="gv-sift" method="get" action="/shop" role="search">
                    <button type="button" class="sift-close" data-sift-close aria-label="{{ __('site.storefront.product.close') }}">&times;</button>
                    @if ($activeCategory)
                        <input type="hidden" name="category" value="{{ $activeCategory }}">
                    @endif
                    <label class="f f-search">
                        <span>{{ __('site.storefront.controls.search') }}</span>
                        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('site.storefront.controls.search_placeholder') }}" autocomplete="off">
                    </label>
                    <label class="f f-sort">
                        <span>{{ __('site.storefront.controls.sort') }}</span>
                        <select name="sort" data-sift-auto>
                            {{-- The yard's own order, and what the shop opens
                                 on: sections in the order the chips above run.
                                 Not offered on a category page, where every
                                 product shares the category and it sorts
                                 nothing. --}}
                            <option value="category" @selected($activeSort === 'category')>{{ __('site.storefront.controls.sort_category') }}</option>
                            <option value="newest" @selected($activeSort === 'newest')>{{ __('site.storefront.controls.sort_newest') }}</option>
                            <option value="price_asc" @selected($activeSort === 'price_asc')>{{ __('site.storefront.controls.sort_price_asc') }}</option>
                            <option value="price_desc" @selected($activeSort === 'price_desc')>{{ __('site.storefront.controls.sort_price_desc') }}</option>
                            <option value="name_asc" @selected($activeSort === 'name_asc')>{{ __('site.storefront.controls.sort_name_asc') }}</option>
                        </select>
                    </label>
                    <label class="f f-price">
                        <span>{{ __('site.storefront.controls.price_min') }}</span>
                        <input type="number" name="min_price" step="0.01" min="0" value="{{ $minPriceInput }}" placeholder="0">
                    </label>
                    <label class="f f-price">
                        <span>{{ __('site.storefront.controls.price_max') }}</span>
                        <input type="number" name="max_price" step="0.01" min="0" value="{{ $maxPriceInput }}" placeholder="∞">
                    </label>
                    <label class="f f-check">
                        <input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock'])>
                        <span>{{ __('site.storefront.controls.in_stock_only') }}</span>
                    </label>
                    <div class="go">
                        <button type="submit" class="btn">{!! $theme->editable('shop_refine') !!}</button>
                        @if ($hasActiveFilters)
                            <a class="clear" href="/shop">{{ __('site.storefront.controls.clear') }}</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        @if ($products->isEmpty())
            <div class="wrap">
                <div class="book-empty" data-gv-reveal>
                    <span class="board-glyph" aria-hidden="true"><i></i></span>
                    <h2>{!! __('site.storefront.sankevi.shop_empty_h_html') !!}</h2>
                    <p>{{ __('site.storefront.sankevi.shop_empty_p') }}</p>
                    <div class="acts">
                        <a class="btn" href="/shop">{{ __('site.storefront.sankevi.shop_empty_cta') }}</a>
                        @if ($csContactOn)
                            <a class="btn outline" href="/contact">{{ __('site.storefront.sankevi.trade_cta') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <section class="book">
                @foreach ($products as $product)
                    @php
                        $imgUrl = $product->image_path
                            ? \Illuminate\Support\Facades\Storage::url($product->image_path)
                            : null;
                        $section = $product->categories->first();
                        // Numbered from the paginator, not a CSS counter: sheet
                        // 13 must still be sheet 13 on the second page.
                        $sheetNo = str_pad((string) ($products->firstItem() + $loop->index), 2, '0', STR_PAD_LEFT);
                        // The choice axes (Sankevi: Дължина × Широчина). Their
                        // presence is why the row links out instead of adding to
                        // the cart — the size has to be chosen on the entry page.
                        $axes = $product->options;
                        $inStock = $product->stock_quantity > 0;
                    @endphp
                    <a class="entry {{ $loop->even ? 'rev' : '' }}" href="/products/{{ $product->slug }}" data-gv-reveal>
                        {{-- The planed corner runs on the photograph here too:
                             the chamfer lands on the page edge on one corner and
                             on the entry's own edge on the other. --}}
                        <div class="plate cut cut-lg {{ $imgUrl ? '' : 'ph' }}">
                            @if ($imgUrl)
                                <span class="shot" data-gv-parallax="0.045">
                                    <img src="{{ $imgUrl }}" alt="{{ $product->name }}" loading="lazy">
                                </span>
                            @else
                                <span class="board-glyph" aria-hidden="true"><i></i></span>
                            @endif
                            @if ($sheetMark)
                                <span class="no" aria-hidden="true">{{ $sheetMark }} {{ $sheetNo }}</span>
                            @endif
                        </div>

                        <div class="body">
                            @if ($section)<span class="sec">{{ $section->name }}</span>@endif
                            <h2>{{ $product->name }}</h2>
                            @if ($product->description)
                                <p class="desc">{{ $product->description }}</p>
                            @endif

                            <dl>
                                @foreach ($axes as $axis)
                                    <div class="r">
                                        <dt>{{ $axis->name }}</dt>
                                        <dd>{{ $axis->values->pluck('value')->join(' · ') }}</dd>
                                    </div>
                                @endforeach
                                <div class="r">
                                    <dt>{{ __('site.storefront.controls.availability') }}</dt>
                                    <dd class="{{ $inStock ? '' : 'out' }}">
                                        {{ $inStock ? __('site.storefront.product.in_stock_badge') : __('site.storefront.product.out_of_stock') }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="ask">
                            @if ($axes->isNotEmpty())
                                <span class="choice">{{ __('site.storefront.sankevi.shop_choose', ['axes' => $axes->pluck('name')->join(' × ')]) }}</span>
                            @endif
                            <span class="fig">
                                {{-- With axes the listed price is the smallest
                                     section's: the real one is quoted once the
                                     cut is chosen. --}}
                                @if ($axes->isNotEmpty())<span class="from">{{ __('site.storefront.sankevi.shop_from') }}</span>@endif
                                @php
                                    $gvRange = $product->priceRange();
                                    $gvCur = $displayCurrency ?? (isset($store) ? $store->currency : 'EUR');
                                    $gvPrice = $gvRange
                                        ? __('site.storefront.product.price_range', [
                                            'min' => \App\Services\Money::display($gvRange[0], $displayRate ?? 1.0, $gvCur),
                                            'max' => \App\Services\Money::display($gvRange[1], $displayRate ?? 1.0, $gvCur),
                                        ])
                                        : \App\Services\Money::display((int) $product->price_cents, $displayRate ?? 1.0, $gvCur);
                                @endphp
                                <b class="pr">{{ $gvPrice }}@if ($u = $product->priceUnitSuffix())<i class="pu">{{ $u }}</i>@endif</b>
                            </span>
                            <span class="open">{{ __('site.storefront.sankevi.shop_open') }} <i aria-hidden="true">→</i></span>
                        </div>
                    </a>
                @endforeach
            </section>

            <div class="wrap">
                <div class="book-foot">
                    @include('storefront.partials.pagination')
                </div>
            </div>
        @endif
    </main>

    @push('scripts')
<script>
    /*
     | FILTER DRAWER (phones only — above 760 the form is a row in the rail and
     | this never runs anything visible).
     |
     | Hoisted to the body for the same reason the sticky add-to-cart is: the
     | panel is position:fixed, and the rail it is written inside animates in,
     | which leaves a transform on an ancestor and turns "fixed to the viewport"
     | into "fixed to that ancestor". The panel would open halfway down the page.
     */
    (function () {
        var form = document.getElementById('gv-sift');
        var open = document.querySelector('[data-sift-open]');
        var veil = document.querySelector('[data-sift-veil]');
        if (! form || ! open || ! veil) return;

        var phone = window.matchMedia('(max-width: 760px)');

        /*
         | HOISTED ONLY WHILE IT IS A DRAWER.
         |
         | The panel and the tab are position:fixed on a phone, and the rail
         | they are written inside animates in — a transform on an ancestor
         | turns "fixed to the viewport" into "fixed to that ancestor", so they
         | have to hang off the body to escape it.
         |
         | ABOVE 760 THEY MUST GO BACK. The form is not a drawer there, it is a
         | row of controls in the rail, and leaving it on the body moved it to
         | the foot of the page: the filters vanished from where they belong and
         | the category chips closed up against the first product, because the
         | row that had been sitting between them was gone. Remember where it
         | came from and put it back.
         */
        var home = form.parentElement, after = form.nextSibling;

        function place() {
            if (phone.matches) {
                [form, veil, open].forEach(function (el) {
                    if (el.parentElement !== document.body) document.body.appendChild(el);
                });
            } else if (form.parentElement !== home) {
                home.insertBefore(form, after);
            }
        }

        place();

        var lastFocus = null;

        function show(on) {
            form.classList.toggle('is-open', on);
            veil.hidden = ! on;
            open.setAttribute('aria-expanded', on ? 'true' : 'false');
            // The page must not scroll behind an open panel; the drawer has its
            // own overflow and overscroll-behavior to keep the gesture inside.
            document.documentElement.style.overflow = on ? 'hidden' : '';

            if (on) {
                lastFocus = document.activeElement;
                /* The first FIELD, not the first focusable — that is the close
                   button, and someone who just opened a filter panel wants the
                   search box, not the way out of it. */
                var first = form.querySelector('input:not([type=hidden]), select');
                if (first) setTimeout(function () { first.focus({ preventScroll: true }); }, 120);
            } else {
                /* Back to the button that opened it. Falling back to the
                   trigger rather than trusting what was focused before: a tap
                   does not always leave focus on the button it pressed, and
                   returning focus to <body> loses a keyboard user's place. */
                var back = (lastFocus && lastFocus !== document.body) ? lastFocus : open;
                back.focus({ preventScroll: true });
            }
        }

        open.addEventListener('click', function () { show(true); });
        veil.addEventListener('click', function () { show(false); });
        form.querySelector('[data-sift-close]').addEventListener('click', function () { show(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && form.classList.contains('is-open')) show(false);
        });

        // Leaving the phone layout with the panel open would strand a fixed
        // element over a desktop rail that already shows the same controls.
        phone.addEventListener('change', function (e) {
            if (! e.matches) show(false);
            place();
        });
    })();
</script>
@endpush

@push('scripts')
        <script>
            // Sorting is a one-touch decision, so the select submits itself.
            // The Refine button stays for keyboard and no-JS shoppers — this
            // only ever saves a click, it is never the way the form works.
            (function () {
                var sel = document.querySelector('.sift [data-sift-auto]');
                if (! sel || ! sel.form) return;
                sel.addEventListener('change', function () { sel.form.submit(); });
            })();
        </script>
    @endpush
@endsection
