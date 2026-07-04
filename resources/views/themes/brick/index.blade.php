@extends('themes.brick.layout')

@section('content')
    @php
        $isFiltered = ($filters['q'] ?? null)
            || ($filters['category'] ?? null)
            || ($filters['min_price'] ?? null) !== null
            || ($filters['max_price'] ?? null) !== null
            || ($filters['in_stock'] ?? false)
            || (($filters['sort'] ?? 'newest') !== 'newest')
            || $products->currentPage() > 1;

        // Hero visual: merchant hero image if set, else the first product's image.
        $heroProduct = $products->first();
        $csHero = $store->heroBanner();

        // Merchant-controlled collection-strip sizing (band height + title size),
        // injected as CSS custom properties the .cs-* rules read below.
        $csDisplay = $store->collectionDisplay();
        $heroImageUrl = $csHero['enabled'] && $csHero['image_path']
            ? \Illuminate\Support\Facades\Storage::url($csHero['image_path'])
            : ($heroProduct && $heroProduct->image_path
                ? \Illuminate\Support\Facades\Storage::url($heroProduct->image_path)
                : null);
    @endphp

    <style>
        /* Merchant-controlled collection-strip knobs (Store Settings → Storefront
           → Collection strips). Defaults match the prior hard-coded values. */
        :root {
            --cs-band-h: {{ $csDisplay['band_height_px'] }}px;
            --cs-title-max: {{ $csDisplay['title_size_px'] }}px;
        }

        /* ===== HERO — split block, big type + product image =====
           Height is capped so the hero stays near the fold and the catalog
           below it isn't pushed far down. The copy column scrolls internally
           only as a last resort (very long merchant headlines); normally the
           tightened type fits. min/max use the viewport so it scales sanely. */
        .hero { display: grid; grid-template-columns: 1.1fr .9fr; gap: 0; border: 2.5px solid var(--ink); box-shadow: var(--pop-lg); margin: 28px 0 0; background: var(--paper); min-height: 380px; max-height: 76vh; }
        .hero .copy { padding: 40px 40px; display: flex; flex-direction: column; justify-content: safe center; border-right: 2.5px solid var(--ink); overflow: auto; }
        .hero .eyebrow { display: inline-flex; align-self: flex-start; background: var(--ink); color: var(--accent); font-family: var(--display); font-weight: 800; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; padding: 6px 12px; margin-bottom: 20px; flex-shrink: 0; }
        .hero h1 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(32px, 4.2vw, 62px); line-height: .92; letter-spacing: -.03em; }
        .hero h1 .hl { background: var(--accent); padding: 0 .12em; box-decoration-break: clone; -webkit-box-decoration-break: clone; }
        .hero h1.no-hl .hl { background: transparent; padding: 0; }
        .hero p { font-size: 16px; max-width: 42ch; margin: 20px 0 26px; color: var(--text-muted); }
        .hero .cta { display: flex; gap: 14px; flex-wrap: wrap; flex-shrink: 0; }
        .hero .vis { position: relative; min-height: 360px; background: var(--soft); }
        .hero .vis img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; }
        .hero .vis .pricetag { position: absolute; right: 18px; bottom: 18px; background: var(--accent); border: 2.5px solid var(--ink); box-shadow: var(--pop); padding: 10px 14px; font-family: var(--display); font-weight: 800; font-size: 14px; text-transform: uppercase; }

        /* selling-points ticker — sits flush under the hero block */
        .ticker { display: flex; flex-wrap: wrap; gap: 0; border: 2.5px solid var(--ink); border-top: none; }
        .ticker .t { flex: 1 1 0; min-width: 180px; padding: 16px 20px; border-right: 2.5px solid var(--ink); font-family: var(--display); font-weight: 700; font-size: 12px; text-transform: uppercase; display: flex; align-items: center; gap: 10px; }
        .ticker .t:last-child { border-right: none; }
        .ticker .t .num { background: var(--accent); border: 2.5px solid var(--ink); width: 26px; height: 26px; display: grid; place-items: center; font-size: 12px; flex-shrink: 0; }

        .home-empty { border: 2.5px solid var(--ink); box-shadow: var(--pop); padding: 60px 24px; text-align: center; font-family: var(--display); font-weight: 800; text-transform: uppercase; }

        @media (max-width: 900px) {
            /* Stacked: drop the height cap so the copy + image both get room. */
            .hero { grid-template-columns: 1fr; max-height: none; }
            .hero .copy { border-right: none; border-bottom: 2.5px solid var(--ink); overflow: visible; }
            .hero .vis { min-height: 300px; }
            .ticker .t { flex-basis: 50%; border-bottom: 2.5px solid var(--ink); }
        }
        @media (max-width: 540px) {
            .hero h1 { font-size: clamp(30px, 8vw, 46px); }
            .hero .copy { padding: 30px 22px; }
        }

        /* ============================================================
           COLLECTION STRIPS — BRICK theme · "CATALOG BLOCK"
           Restyle of the shared `.cs-*` partial to match brick. Every
           selector is prefixed `.wrap ` to beat the partial's own
           un-prefixed `.cs-*` rules (which ship in a later <style>).
           Mirrors .pcard, .sec-head a, .pcard .tag, .pcard .pr, the
           .ph hatch, and .ed-head from layout.blade.php. No !important.
           ============================================================ */

        /* ---- The strip = one confident bordered poster slab ---- */
        .wrap .cs-strip {
            position: relative;
            margin: 0 0 56px;
            padding: 28px;
            border: 2.5px solid var(--ink);
            border-radius: 0;
            background: var(--paper);
            box-shadow: var(--pop-lg);
            overflow: visible;            /* let card focus rings + hover offsets escape */
        }

        /* ---- BANNER CASE — banner image + header share ONE grid cell, so the
           image (and its scrim) STRETCH to the header's real height. The header
           bottom-anchors its title + description; the band is at least
           --cs-band-h tall but GROWS with long content, and the stretched scrim
           grows with it — so text can never land on bare paper below the scrim
           (the failure of the old fixed-height absolute backdrop). ---- */
        .wrap .cs-strip:has(.cs-banner) {
            display: grid;
            grid-template-columns: 1fr;
            row-gap: 24px;                 /* space between the band (row 1) and the product grid (row 2) */
            padding-top: 0;                /* band runs flush to the inner top edge */
        }
        .wrap .cs-banner {
            grid-area: 1 / 1;              /* same cell as the header → they overlap */
            align-self: stretch;           /* fill the cell → match the header's height */
            position: relative;            /* positions the scrim ::after */
            min-height: var(--cs-band-h, 210px);
            margin: 0 -28px;               /* bleed sides to the inner border edges */
            z-index: 0;
            background-size: cover;
            background-position: center;
            opacity: 1;                    /* kill the partial's .25 ghost */
            border-bottom: 2.5px solid var(--ink);
            pointer-events: none;
        }
        /* Ink scrim, darkest at the bottom where the bottom-anchored text sits.
           Floor .58 at the very top still clears 4.5:1 over a white photo. It
           spans the full (stretched) banner, so it always covers the header. */
        .wrap .cs-banner::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(10, 10, 10, .58) 0%, rgba(10, 10, 10, .86) 100%);
        }
        .wrap .cs-banner + .cs-head {
            grid-area: 1 / 1;              /* overlay the banner; the cell grows to this content */
            z-index: 2;
            min-height: var(--cs-band-h, 210px);
            box-sizing: border-box;
            margin: 0 -28px;               /* bleed sides; row-gap handles the space below */
            padding: 28px;
            align-items: flex-end;         /* anchor title + view-all to the band's bottom */
            background: transparent;
            border-bottom: 0;
            color: var(--paper);
        }
        .wrap .cs-banner + .cs-head h2 { color: var(--paper); }
        .wrap .cs-banner + .cs-head p  { color: var(--paper); }
        .wrap .cs-banner + .cs-head .cs-view-all {
            background: var(--paper);
            color: var(--ink);
            box-shadow: var(--pop-sm);
        }
        .wrap .cs-banner + .cs-head .cs-view-all:hover {
            background: var(--ink);
            color: var(--paper);          /* accent would vanish on the dark photo */
            transform: translate(-1px, -1px);
            box-shadow: var(--pop);
        }

        /* ---- DEFAULT (NO-BANNER) CASE — solid-accent header band.
           Accent as a SOLID FILL with ink text on top (~17:1), bleeding to
           the inner edges like .ed-head. Scoped so the banner case keeps its
           paper-on-scrim header. ---- */
        .wrap .cs-strip:not(:has(.cs-banner)) .cs-head {
            margin: -28px -28px 24px;
            padding: 26px 28px;
            background: var(--accent);
            border-bottom: 2.5px solid var(--ink);
        }
        .wrap .cs-strip:not(:has(.cs-banner)) .cs-head h2 { color: var(--ink); }
        .wrap .cs-strip:not(:has(.cs-banner)) .cs-head p  { color: var(--ink); }
        .wrap .cs-strip:not(:has(.cs-banner)) .cs-head .cs-view-all:hover {
            background: var(--ink);
            color: var(--paper);
        }

        /* ---- Header layout (shared baseline) ---- */
        .wrap .cs-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin: 0 0 24px;
            flex-wrap: wrap;
        }
        .wrap .cs-head h2 {
            margin: 0;
            font-family: var(--display);
            font-weight: 900;
            text-transform: uppercase;
            font-size: clamp(24px, 8vw, var(--cs-title-max, 44px));
            line-height: .95;
            letter-spacing: -0.02em;
            color: var(--ink);
        }
        .wrap .cs-head p {
            margin: 8px 0 0;
            font-family: var(--body);
            font-size: 14px;
            line-height: 1.5;
            max-width: 52ch;
            color: var(--text-muted);
        }

        /* ---- view-all: faithful .sec-head a clone ---- */
        .wrap .cs-view-all {
            display: inline-flex;
            align-items: center;
            min-height: 44px;
            font-family: var(--display);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .02em;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--ink);
            border: 2.5px solid var(--ink);
            border-radius: 0;
            padding: 8px 14px;
            background: var(--paper);
            box-shadow: var(--pop-sm);
            transition: transform .12s ease, box-shadow .12s ease, background-color .12s ease, color .12s ease;
        }
        .wrap .cs-view-all:hover {
            background: var(--accent);
            color: var(--ink);
            transform: translate(-1px, -1px);
            box-shadow: var(--pop);
        }
        .wrap .cs-view-all:active {
            transform: translate(5px, 5px);
            box-shadow: 0 0 0 var(--shadow);
        }

        /* ---- Grid ---- */
        .wrap .cs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 18px;
            counter-reset: cs;            /* ordinal index for corner chips */
        }

        /* ---- Cards: mini-.pcard clones ---- */
        .wrap .cs-card {
            counter-increment: cs;
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 0;
            color: inherit;
            text-decoration: none;
            border: 2.5px solid var(--ink);
            border-radius: 0;
            background: var(--paper);
            box-shadow: var(--pop-sm);
            overflow: hidden;            /* the focus outline (outline-offset:2px) paints outside the box, not clipped */
            transition: transform .14s ease, box-shadow .14s ease;
        }
        .wrap .cs-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: var(--pop);
        }
        .wrap .cs-card:active {
            transform: translate(5px, 5px);
            box-shadow: 0 0 0 var(--shadow);
        }

        /* CSS-counter index chip cloning .pcard .tag — accent corner block.
           Ordinal-only so it conveys order, never meaning-by-colour. */
        .wrap .cs-card::before {
            content: counter(cs, decimal-leading-zero);
            position: absolute;
            top: 0;
            left: 0;
            z-index: 3;
            background: var(--accent);
            color: var(--ink);
            border-right: 2.5px solid var(--ink);
            border-bottom: 2.5px solid var(--ink);
            font-family: var(--display);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .06em;
            line-height: 1;
            padding: 6px 9px;
            pointer-events: none;
        }

        /* Image box: square, border-bottom like .pcard .imgwrap. Empty state
           reuses the brick .ph hatch so a card with no <img> reads intentional. */
        .wrap .cs-img {
            position: relative;
            aspect-ratio: 1 / 1;
            border-radius: 0;
            border-bottom: 2.5px solid var(--ink);
            overflow: hidden;
            background: var(--soft);
            background-image: repeating-linear-gradient(45deg, rgba(10, 10, 10, .06) 0 12px, transparent 12px 24px);
        }
        .wrap .cs-img img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: filter .14s ease;
        }
        /* Brightness-only hover (matches .pcard); transform:none kills the
           partial's leaked `transform: scale(1.04)` so the image doesn't snap-scale. */
        .wrap .cs-card:hover .cs-img img { filter: brightness(1.05); transform: none; }

        /* ---- Meta: name + accent price chip (literal .pcard .pr recipe) ---- */
        .wrap .cs-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
            padding: 12px 14px;
            flex: 1;                  /* fill the card's height (cards stretch to equal height) */
        }
        .wrap .cs-name {
            font-family: var(--display);
            font-weight: 700;
            font-size: 14px;
            line-height: 1.15;
            text-transform: uppercase;
            letter-spacing: -0.01em;
            color: var(--ink);
        }
        .wrap .cs-price {
            align-self: flex-start;
            margin-top: auto;         /* pin price to the card's bottom so prices align across the row */
            font-family: var(--display);
            font-weight: 800;
            font-size: 13px;
            white-space: nowrap;
            color: var(--ink);
            background: var(--accent);
            border: 2.5px solid var(--ink);
            border-radius: 0;
            padding: 2px 7px;
            font-variant-numeric: tabular-nums;
        }

        /* ---- Responsive ---- */
        @media (max-width: 1024px) {
            .wrap .cs-grid { grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); }
        }
        @media (max-width: 768px) {
            .wrap .cs-strip { padding: 22px; box-shadow: var(--pop); }
            .wrap .cs-strip:has(.cs-banner) { row-gap: 20px; }
            .wrap .cs-banner { min-height: calc(var(--cs-band-h, 210px) * 0.9); margin: 0 -22px; }
            .wrap .cs-banner + .cs-head { min-height: calc(var(--cs-band-h, 210px) * 0.9); margin: 0 -22px; padding: 22px; }
            .wrap .cs-strip:not(:has(.cs-banner)) .cs-head { margin: -22px -22px 20px; padding: 22px 22px; }
            .wrap .cs-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
        }
        @media (max-width: 375px) {
            .wrap .cs-strip { padding: 16px; }
            .wrap .cs-strip:has(.cs-banner) { row-gap: 18px; }
            .wrap .cs-banner { min-height: calc(var(--cs-band-h, 210px) * 0.95); margin: 0 -16px; }
            .wrap .cs-banner + .cs-head { min-height: calc(var(--cs-band-h, 210px) * 0.95); margin: 0 -16px; padding: 18px 16px; flex-direction: column; align-items: flex-start; justify-content: flex-end; }
            .wrap .cs-strip:not(:has(.cs-banner)) .cs-head { margin: -16px -16px 18px; padding: 18px 16px; }
            .wrap .cs-head { flex-direction: column; align-items: flex-start; }
            .wrap .cs-grid { grid-template-columns: 1fr; }
        }

        /* ---- Reduced motion — kill all hover/active transforms & transitions ---- */
        @media (prefers-reduced-motion: reduce) {
            .wrap .cs-card,
            .wrap .cs-card:hover,
            .wrap .cs-card:active,
            .wrap .cs-view-all:hover,
            .wrap .cs-view-all:active,
            .wrap .cs-banner + .cs-head .cs-view-all:hover {
                transform: none;
            }
            .wrap .cs-card,
            .wrap .cs-view-all,
            .wrap .cs-img img,
            .wrap .cs-card:hover .cs-img img { transition: none; }
        }
    </style>

    <main>
        <div class="wrap">
            @if (! $isFiltered)
                {{-- ===== HERO ===== --}}
                <section class="hero">
                    <div class="copy">
                        <span class="eyebrow">{{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</span>
                        <h1 class="{{ $theme->on('hl_mark') ? '' : 'no-hl' }}">@if ($csHero['subtitle'] !== ''){{ $csHero['subtitle'] }}@else{!! __('site.storefront.hero.headline', ['tenant' => '<span class="hl">' . e($tenant->name) . '</span>']) !!}@endif</h1>
                        <p>{{ $theme->copy('hero_sub') }}</p>
                        <div class="cta">
                            <a class="btn accent" href="#shop">{{ $csHero['cta_label'] !== '' ? $csHero['cta_label'] : __('site.storefront.hero.cta_primary') }} <span class="arc">→</span></a>
                            <a class="btn" href="#shop">{{ __('site.storefront.hero.cta_secondary') }}</a>
                        </div>
                    </div>
                    <div class="vis ph">
                        @if ($heroImageUrl)
                            <img src="{{ $heroImageUrl }}" alt="{{ $csHero['title'] !== '' ? $csHero['title'] : $tenant->name }}">
                        @else
                            <span>{{ $tenant->name }}</span>
                        @endif
                        @if ($heroProduct && $theme->on('pricetag'))
                            <span class="pricetag">@money($heroProduct->price_cents)</span>
                        @endif
                    </div>
                </section>

                {{-- ===== SELLING POINTS ===== --}}
                @if ($theme->on('ticker'))
                    <div class="ticker">
                        <div class="t"><span class="num">01</span>{{ __('site.storefront.value_props.shipping_title') }}</div>
                        <div class="t"><span class="num">02</span>{{ __('site.storefront.value_props.returns_title') }}</div>
                        <div class="t"><span class="num">03</span>{{ __('site.storefront.value_props.checkout_title') }}</div>
                    </div>
                @endif
            @endif

            {{-- ===== COLLECTION STRIPS (only when the merchant curates them) ===== --}}
            @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
                <div class="sec-head rv">
                    <h2>{{ __('site.storefront.collections.heading') }}</h2>
                </div>
                @include('storefront.partials.collection-strips')
            @endif

            {{-- ===== SHOP ALL — the catalog, kept high on the page ===== --}}
            <div class="sec-head rv" id="shop">
                <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
            </div>

            @include('storefront.partials.catalog-controls')

            @if ($products->isEmpty())
                <div class="home-empty rv">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="pgrid">
                    @foreach ($products as $product)
                        @include('themes.brick._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>

                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
