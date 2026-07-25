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
    $activeSort = $filters['sort'] ?? 'newest';
    $hasActiveFilters = $filters['q']
        || $activeCategory
        || $filters['min_price'] !== null
        || $filters['max_price'] !== null
        || $filters['in_stock']
        || $activeSort !== 'newest';

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
            'sort'      => $activeSort !== 'newest' ? $activeSort : null,
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
        .sift { display: grid; grid-template-columns: minmax(0, 1.9fr) minmax(0, 1.15fr) minmax(0, .62fr) minmax(0, .62fr) auto auto; gap: 0 22px; align-items: end; margin-top: 26px; padding: 22px 0 24px; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line2); }
        .sift .f { display: flex; flex-direction: column; gap: 8px; min-width: 0; }
        .sift .f > span { font-size: 10px; font-weight: 500; letter-spacing: .24em; text-transform: uppercase; color: var(--faint); }
        .sift input[type="search"], .sift input[type="number"], .sift select {
            width: 100%; min-width: 0; padding: 9px 2px; background: transparent; border: 0; border-bottom: 1px solid var(--line2);
            color: var(--txt); font-family: var(--body); font-size: 15px; transition: border-color .25s ease;
        }
        /* the native select needs an explicit ground or the OS paints it white */
        .sift select { background: transparent; }
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
            .entry .plate, .entry.rev .plate { grid-column: 1; grid-row: 1; min-height: 0; aspect-ratio: 16 / 10; }
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
        @media (max-width: 760px) {
            .sift { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
            .sift .f-search { grid-column: 1 / -1; }
            .sift .f-sort { grid-column: 1 / -1; }
            .sift .f-check { grid-column: 1 / -1; }
            .sift .go { grid-column: 1 / -1; justify-content: space-between; }
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
                            {!! __('site.storefront.sankevi.shop_h1_html') !!}
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
                            @foreach ($categories as $cat)
                                <a href="{{ $chipUrl($cat->slug) }}" class="pill {{ $activeCategory === $cat->slug ? 'on' : '' }}"
                                   @if ($activeCategory === $cat->slug) aria-current="true" @endif>{{ $cat->name }}</a>
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
                <form class="sift" method="get" action="/shop" role="search">
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
                        <button type="submit" class="btn">{{ __('site.storefront.sankevi.shop_refine') }}</button>
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
                                <b class="pr">@money($product->price_cents)</b>
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
