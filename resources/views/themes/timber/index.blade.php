@extends('themes.timber.layout')

@section('content')
    @php
        $isFiltered = ($filters['q'] ?? null)
            || ($filters['category'] ?? null)
            || ($filters['min_price'] ?? null) !== null
            || ($filters['max_price'] ?? null) !== null
            || ($filters['in_stock'] ?? false)
            || (($filters['sort'] ?? 'newest') !== 'newest')
            || $products->currentPage() > 1;

        $csHero = $store->heroBanner();
        // The hero "stack on the yard" leans on the lead product: its name +
        // category drive the spec plate; its photo (if any) becomes the stack
        // face. Lead with a product that HAS photography (falls back to the
        // newest) — the stage is the theme's money shot.
        $heroProduct = $products->firstWhere('image_path') ?: $products->first();
        $heroImageUrl = $csHero['enabled'] && $csHero['image_path']
            ? \Illuminate\Support\Facades\Storage::url($csHero['image_path'])
            : ($heroProduct && $heroProduct->image_path
                ? \Illuminate\Support\Facades\Storage::url($heroProduct->image_path)
                : null);
        $heroProdCat = $heroProduct && $heroProduct->relationLoaded('categories') && $heroProduct->categories->isNotEmpty()
            ? $heroProduct->categories->first()->name
            : null;
        $tradeEmail = $tenant->contact_email ?: null;
    @endphp

    <style>
        /* ===== HERO — copy left, a stacked-boards stage right: five oversized
           sawn boards banded with the treatment amber, a grading stamp, a cream
           spec plate leaning on the stack. Signage over the timber yard. */
        .hero { position: relative; display: grid; grid-template-columns: 1.1fr .9fr; gap: 48px; align-items: center; padding: 54px 0 0; min-height: 76vh; }
        /* ghost dimension — an oversized outlined cutting size sunk into the paper */
        .hero::before { content: "45×95"; position: absolute; right: -3%; bottom: -10%; z-index: 0; pointer-events: none; font-family: var(--display); font-weight: 700; letter-spacing: -.02em; line-height: 1; font-size: clamp(140px, 22vw, 300px); color: transparent; -webkit-text-stroke: 1.5px color-mix(in srgb, var(--accent) 22%, transparent); }
        .hero.no-mark::before { display: none; }
        .hero .lead { position: relative; z-index: 3; }
        .hero .lead .k { font-family: var(--mono); font-size: 12px; letter-spacing: .06em; color: var(--accent-deep); text-transform: uppercase; }
        .hero .lead .k::before { content: "— "; color: var(--faint); }
        .hero .lead h1 { font-family: var(--display); font-weight: 700; letter-spacing: 0; text-transform: uppercase; font-size: clamp(56px, 8vw, 118px); line-height: .92; margin: 18px 0 18px; }
        .hero .lead h1 .o { color: var(--accent-deep); }
        .hero .lead h1 em { font-style: normal; color: var(--accent-deep); }
        .hero .lead p { color: var(--muted); font-size: 17px; max-width: 42ch; margin: 0 0 28px; }
        .hero .lead .cta { display: flex; gap: 14px; flex-wrap: wrap; }

        .hero .stage { position: relative; display: grid; place-items: center; min-height: 500px; }
        .hero .stage .ring.h1 { width: 170px; height: 170px; left: -2%; bottom: 6%; opacity: .6; }
        .hero .stage .ring.h2 { width: 84px; height: 84px; left: 12%; top: 8%; opacity: .4; }
        /* the stack — five boards, the bottom two treated (amber-banded).
           SIGNATURE MOTION: boards breathe apart a few px on an easy clock. */
        .hero .stack { position: relative; width: min(360px, 82%); z-index: 2; }
        .hero .stack .brd { position: relative; height: 52px; border-radius: 5px; border: 1px solid rgba(90, 68, 38, .4); background: linear-gradient(94deg, #e2d4b2 0%, #c9b489 48%, #d6c49c 100%); box-shadow: inset 0 -12px 18px -12px rgba(90, 68, 38, .5), 0 6px 10px -6px rgba(60, 44, 22, .4); margin-bottom: 10px; animation: stackbreathe 6.4s ease-in-out infinite; }
        .hero .stack .brd:nth-child(2) { margin-left: 14px; margin-right: -8px; animation-delay: .35s; }
        .hero .stack .brd:nth-child(3) { margin-left: -6px; margin-right: 10px; animation-delay: .7s; }
        .hero .stack .brd:nth-child(4) { margin-left: 8px; margin-right: -4px; animation-delay: 1.05s; }
        .hero .stack .brd:nth-child(5) { margin-bottom: 0; animation-delay: 1.4s; }
        /* the treated pair drinks the amber */
        .hero .stack .brd:nth-child(4), .hero .stack .brd:nth-child(5) { background: linear-gradient(94deg, color-mix(in srgb, var(--accent) 40%, #e2d4b2), color-mix(in srgb, var(--accent) 60%, #c9b489)); }
        /* end-grain caps on the right edge of each board */
        .hero .stack .brd::after { content: ""; position: absolute; right: 0; top: 0; bottom: 0; width: 14px; border-radius: 0 5px 5px 0; background: repeating-radial-gradient(circle at 120% 50%, rgba(90, 68, 38, .35) 0 2px, transparent 2px 6px), #cdb98e; border-left: 1px solid rgba(90, 68, 38, .35); }
        @keyframes stackbreathe { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
        /* photo face — when the lead product has photography it fills a framed
           face leaning behind the stack */
        .hero .face { position: absolute; right: -4%; top: 2%; width: 58%; aspect-ratio: 4 / 5; border: 1px solid var(--line2); border-radius: 8px; overflow: hidden; box-shadow: 0 26px 50px -30px rgba(60, 44, 22, .55); transform: rotate(2deg); z-index: 1; }
        .hero .face img { width: 100%; height: 100%; object-fit: cover; }
        /* grading stamp — inked circle, slow rotation like a rolling brand */
        /* the stamp sits clear of the stack (bottom-right of the stage) so the
           inked caps never collide with a board edge */
        .hero .gstamp { position: absolute; right: 2%; bottom: 2%; width: 112px; height: 112px; border: 2.5px solid var(--accent-deep); border-radius: 50%; display: grid; place-items: center; text-align: center; color: var(--accent-deep); font-family: var(--mono); font-weight: 600; font-size: 11px; letter-spacing: .12em; line-height: 1.5; text-transform: uppercase; transform: rotate(-10deg); opacity: .9; z-index: 4; animation: stampspin 60s linear infinite; background: var(--plate); box-shadow: 0 6px 16px -10px rgba(60, 44, 22, .5); }
        @keyframes stampspin { from { transform: rotate(-10deg); } to { transform: rotate(350deg); } }
        /* the cream spec plate leaning on the stack — cutting-list voice */
        .hero .plate { position: absolute; left: 6%; bottom: 8%; transform: rotate(-2deg); width: 190px; background: var(--plate); color: #2c2014; border: 1px solid var(--line2); border-radius: 4px; padding: 16px 15px; text-align: center; box-shadow: 0 14px 28px -14px rgba(60, 44, 22, .5); z-index: 3; }
        .hero .plate .ln { font-family: var(--mono); font-size: 9px; letter-spacing: .16em; text-transform: uppercase; color: #8a5f36; }
        .hero .plate h3 { font-family: var(--display); font-weight: 700; text-transform: uppercase; font-size: 20px; line-height: 1.05; margin: 6px 0; }
        .hero .plate .yr { font-family: var(--mono); font-weight: 600; font-size: 12px; letter-spacing: .1em; text-transform: uppercase; }
        .hero .plate .div { height: 1px; background: #d9c9ae; margin: 8px 0; }
        /* ruler along the stage floor */
        .hero .stage .rule-ticks { position: absolute; left: 6%; right: 6%; bottom: 0; }
        .hero .stage.no-rule .rule-ticks { display: none; }

        /* specs strip — mono yard notes on a ruled band */
        .strip { display: flex; justify-content: space-between; gap: 30px; flex-wrap: wrap; padding: 24px 0; margin-top: 34px; border-top: 2px solid var(--txt); border-bottom: 1px solid var(--line); font-family: var(--mono); font-size: 12px; color: var(--muted); }
        .strip b { color: var(--txt); }

        /* ===== USE-CLASS GUIDE — the vertical's signature band: UC1–UC4
           treatment classes as gauge cards. No generic theme has this. */
        .ucs { margin: 90px 0; }
        .ucs .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
        .ucs .uc { background: var(--surface); border: 1px solid var(--line); border-radius: 8px; padding: 24px 22px; box-shadow: 0 2px 0 0 var(--line); transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease; }
        .ucs .uc:hover { transform: translateY(-4px); border-color: var(--line2); box-shadow: 0 4px 0 0 var(--line2), 0 18px 30px -22px rgba(60, 44, 22, .45); }
        .ucs .uc .tag { font-family: var(--mono); font-weight: 600; font-size: 12px; letter-spacing: .1em; color: var(--accent-deep); border: 1.5px solid var(--accent-deep); border-radius: 4px; display: inline-block; padding: 3px 9px; transform: rotate(-1.5deg); }
        .ucs .uc h3 { font-family: var(--display); font-weight: 700; text-transform: uppercase; font-size: 22px; line-height: 1.1; margin: 12px 0 6px; }
        .ucs .uc p { color: var(--muted); font-size: 13.5px; line-height: 1.55; margin-bottom: 16px; }
        /* treatment-depth gauge — the amber sinks deeper class by class */
        .ucs .uc .gauge { height: 8px; border: 1px solid var(--line2); border-radius: 4px; background: var(--surface2); overflow: hidden; }
        .ucs .uc .gauge i { display: block; height: 100%; width: var(--depth, 25%); background: linear-gradient(90deg, color-mix(in srgb, var(--accent) 55%, #e2d4b2), var(--accent)); transition: width .8s cubic-bezier(.19, .7, .16, 1) .2s; }
        .ucs.reveal:not(.in) .uc .gauge i { width: 0; }

        /* ===== STORY BAND — sawmill photo (image slot) + manifesto. */
        .explain { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin: 100px 0; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; align-items: stretch; box-shadow: 0 2px 0 0 var(--line); }
        .explain .art { min-height: 440px; background: linear-gradient(140deg, #e5d9bd, #c9b489); position: relative; overflow: hidden; }
        /* CSS fallback art: giant end-grain rings + inked stamp */
        .explain .art .ring.a1 { width: 300px; height: 300px; right: -70px; bottom: -70px; opacity: .8; }
        .explain .art .ring.a2 { width: 150px; height: 150px; right: 190px; bottom: 40px; opacity: .5; }
        .explain .art .stamp { position: absolute; left: 40px; top: 40px; width: 126px; height: 126px; border: 2.5px solid var(--accent-deep); border-radius: 50%; display: grid; place-items: center; text-align: center; color: var(--accent-deep); font-family: var(--mono); font-weight: 600; font-size: 10px; letter-spacing: .1em; line-height: 1.5; text-transform: uppercase; transform: rotate(-12deg); opacity: .85; }
        .explain .txt { background: var(--surface); padding: 60px 54px; display: flex; flex-direction: column; justify-content: center; }
        .explain .k { font-family: var(--mono); font-size: 12px; color: var(--accent-deep); text-transform: uppercase; letter-spacing: .04em; }
        .explain h3 { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .01em; font-size: clamp(30px, 3.8vw, 46px); margin: 14px 0 16px; line-height: 1; }
        .explain h3 em { font-style: normal; color: var(--accent-deep); }
        .explain p { color: var(--muted); max-width: 44ch; margin-bottom: 14px; }

        /* ===== TRADE & BULK — the walnut counter: dark panel, plate type. */
        .bulk { position: relative; overflow: hidden; background: var(--deep); color: #f0e7d6; border-radius: 10px; padding: 54px; margin: 90px 0; display: grid; grid-template-columns: 1.2fr .8fr; gap: 40px; align-items: center; }
        .bulk::before { content: ""; position: absolute; inset: 0; pointer-events: none; background: repeating-linear-gradient(92deg, rgba(255, 255, 255, .025) 0 2px, transparent 2px 10px); }
        .bulk .k { font-family: var(--mono); font-size: 12px; color: var(--accent); text-transform: uppercase; letter-spacing: .04em; }
        .bulk h3 { font-family: var(--display); font-weight: 700; text-transform: uppercase; font-size: clamp(28px, 3.4vw, 44px); line-height: 1; margin: 12px 0 12px; }
        .bulk h3 em { font-style: normal; color: var(--accent); }
        .bulk p { color: #c9bda5; max-width: 52ch; }
        .bulk .side { display: flex; flex-direction: column; gap: 12px; align-items: flex-start; position: relative; z-index: 1; }
        .bulk .side .row { font-family: var(--mono); font-size: 12px; letter-spacing: .04em; text-transform: uppercase; color: #c9bda5; display: flex; gap: 10px; align-items: center; }
        .bulk .side .row::before { content: "▮"; color: var(--accent); }
        .bulk .side .btn { margin-top: 10px; }

        /* ===== NEWSLETTER — the price list, centered. */
        .news { text-align: center; margin: 90px auto; max-width: 540px; }
        .news h3 { font-family: var(--display); font-weight: 700; text-transform: uppercase; font-size: clamp(28px, 3.4vw, 42px); line-height: 1.05; }
        .news h3 em { font-style: normal; color: var(--accent-deep); }
        .news p { color: var(--muted); margin: 12px 0 24px; }
        .news form { display: flex; gap: 10px; }
        .news input { flex: 1; border: 1px solid var(--line2); border-radius: 6px; background: var(--surface); padding: 14px 18px; color: var(--txt); font-family: var(--mono); font-size: 13px; }
        .news input:focus { outline: none; border-color: var(--accent); }

        .home-empty { text-align: center; padding: 70px 24px; font-family: var(--display); text-transform: uppercase; font-size: 24px; color: var(--muted); }

        /* category pills — keeps the shared .pill contract; square chips */
        .pills { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin: 28px 0 42px; }
        .pills .pill { border: 1px solid var(--line2); background: var(--surface); border-radius: 5px; padding: 9px 16px; font-family: var(--mono); font-size: 11px; letter-spacing: .02em; text-transform: uppercase; color: var(--muted); box-shadow: 0 2px 0 0 var(--line); transition: background-color .2s ease, color .2s ease, border-color .2s ease, box-shadow .2s ease; }
        .pills .pill.on, .pills .pill:hover { background: var(--accent); border-color: var(--accent-deep); color: var(--on-accent); box-shadow: 0 2px 0 0 var(--accent-deep); }

        /* ===== Collection rails — Timber restyle of the shared .cs-* partial:
           each featured collection is a framed rack panel with price-list
           cards. `.wrap `-prefixed to beat the partial's own rules. */
        .wrap .cs-strip { position: relative; margin: 64px 0 0; padding: 40px 36px; border-radius: 10px; border: 1px solid var(--line); background: var(--surface); overflow: hidden; box-shadow: 0 2px 0 0 var(--line); }
        .wrap .cs-banner { display: none; }
        .wrap .cs-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin: 0 0 26px; flex-wrap: wrap; border-bottom: 2px solid var(--txt); padding-bottom: 14px; }
        .wrap .cs-head h2 { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .01em; font-size: clamp(26px, 3vw, 40px); line-height: 1.05; }
        .wrap .cs-head p { color: var(--muted); font-size: 14px; max-width: 50ch; margin-top: 4px; }
        .wrap .cs-view-all { font-family: var(--mono); font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--accent-deep); border: 1px solid var(--line2); border-radius: 5px; padding: 10px 16px; background: transparent; white-space: nowrap; box-shadow: 0 2px 0 0 var(--line); transition: border-color .2s ease, color .2s ease; }
        .wrap .cs-view-all:hover { border-color: var(--accent-deep); }
        .wrap .cs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); gap: 22px; }
        .wrap .cs-card { display: flex; flex-direction: column; color: inherit; transition: transform .22s cubic-bezier(.19, .7, .16, 1); }
        .wrap .cs-card:hover { transform: translateY(-4px); }
        .wrap .cs-img { aspect-ratio: 3 / 4; border: 1px solid var(--line); border-radius: 6px; overflow: hidden; background: linear-gradient(140deg, #e5d9bd, #c9b489); margin-bottom: 12px; }
        .wrap .cs-img img { width: 100%; height: 100%; object-fit: cover; }
        .wrap .cs-meta { display: flex; flex-direction: column; gap: 3px; padding: 0; }
        .wrap .cs-name { font-family: var(--display); font-weight: 600; text-transform: uppercase; font-size: 17px; line-height: 1.15; }
        .wrap .cs-price { font-family: var(--display); font-weight: 700; font-size: 17px; font-variant-numeric: tabular-nums; color: var(--txt); }

        @media (prefers-reduced-motion: reduce) {
            .hero .stack .brd, .hero .gstamp { animation: none; }
            .wrap .cs-card, .wrap .cs-card:hover, .ucs .uc, .ucs .uc:hover { transform: none; }
            .ucs .uc .gauge i, .ucs.reveal:not(.in) .uc .gauge i { width: var(--depth, 25%); transition: none; }
        }
        @media (max-width: 1000px) {
            .hero { grid-template-columns: 1fr; min-height: auto; padding: 36px 0 0; }
            .hero .stage { min-height: 420px; margin-top: 24px; }
            .hero .face { right: 0; }
            .explain, .bulk { grid-template-columns: 1fr; }
            .explain .art { min-height: 260px; }
            .ucs .grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 680px) {
            .bulk { padding: 32px 24px; }
            .ucs .grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .ucs .uc { padding: 18px 16px; }
        }
        @media (max-width: 540px) {
            .wrap .cs-strip { padding: 28px 22px; }
            .wrap .cs-grid { grid-template-columns: repeat(2, 1fr); }
            .ucs .grid { grid-template-columns: 1fr; }
        }
    </style>

    <main>
        @if (! $isFiltered)
            <div class="wrap">
                <section class="hero {{ $theme->on('watermark') ? '' : 'no-mark' }}">
                    <div class="lead">
                        <div class="k" data-gv-reveal="fade">{{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</div>
                        <h1 data-gv-split>@if ($csHero['subtitle'] !== ''){{ $csHero['subtitle'] }}@else{!! __('site.storefront.hero.headline', ['tenant' => '<span class="o">' . e($tenant->name) . '</span>']) !!}@endif</h1>
                        <p data-gv-reveal data-gv-delay="0.2">{{ __('site.storefront.hero.sub') }}</p>
                        <div class="cta" data-gv-reveal data-gv-delay="0.32">
                            <a class="btn" href="#shop">{{ $csHero['cta_label'] !== '' ? $csHero['cta_label'] : __('site.storefront.hero.cta_primary') }}</a>
                            <a class="btn outline" href="#shop">{{ __('site.storefront.hero.cta_secondary') }}</a>
                        </div>
                    </div>
                    <div class="stage {{ $theme->on('ruler') ? '' : 'no-rule' }}" data-gv-reveal="scale" data-gv-delay="0.25" data-gv-parallax="0.05" aria-hidden="true">
                        @if ($theme->on('grain_rings'))
                            <div class="ring h1"></div>
                            <div class="ring h2"></div>
                        @endif
                        @if ($heroImageUrl)
                            <div class="face"><img src="{{ $heroImageUrl }}" alt=""></div>
                        @endif
                        <div class="stack">
                            <div class="brd"></div><div class="brd"></div><div class="brd"></div><div class="brd"></div><div class="brd"></div>
                        </div>
                        @if ($theme->on('grade_stamp'))
                            <div class="gstamp">{{ $theme->label('grade_stamp') }}<br>★</div>
                        @endif
                        <div class="plate">
                            @if ($heroProdCat)<div class="ln">{{ $heroProdCat }}</div>@else<div class="ln">{{ $tenant->name }}</div>@endif
                            <h3>{{ $heroProduct->name ?? $tenant->name }}</h3>
                            <div class="div"></div>
                            <div class="yr">{{ $theme->label('lot_stamps') }} № {{ date('y') }}</div>
                            <div class="ln" style="margin-top: 6px;">{{ $theme->copy('spec_note') }}</div>
                        </div>
                        <div class="rule-ticks"></div>
                    </div>
                </section>
            </div>

            {{-- Yard notes band — mono specs, not a free-shipping strip. --}}
            @if ($theme->on('specs_strip'))
            <div class="wrap">
                <div class="strip" data-gv-reveal>
                    <span><b data-gv-counter="{{ $products->total() }}">{{ $products->total() }}</b> {{ __('site.storefront.footer.all_products') }}</span>
                    <span><b>{{ __('site.storefront.value_props.shipping_title') }}</b></span>
                    <span><b>{{ __('site.storefront.value_props.returns_title') }}</b></span>
                    <span><b>{{ __('site.storefront.value_props.checkout_title') }}</b></span>
                </div>
            </div>
            @endif
        @endif

        <div class="wrap">
            {{-- Curated collections (only when the merchant features them) --}}
            @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
                @include('storefront.partials.collection-strips')
            @endif

            {{-- On the racks — the catalog --}}
            <div class="sec-head" data-gv-reveal id="shop">
                <span class="kicker">{{ __('site.storefront.shop_all.eyebrow') }}</span>
                <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
            </div>

            @if ($categories->isNotEmpty())
                <div class="pills" data-gv-reveal data-gv-delay="0.1">
                    <a href="/" class="pill {{ ! ($filters['category'] ?? null) ? 'on' : '' }}">{{ __('site.storefront.controls.category_all') }}</a>
                    @foreach ($categories as $cat)
                        <a href="/?category={{ $cat->slug }}" class="pill {{ ($filters['category'] ?? null) === $cat->slug ? 'on' : '' }}">{{ $cat->name }}</a>
                    @endforeach
                </div>
            @endif

            @if ($products->isEmpty())
                <div class="home-empty reveal">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="racks {{ $theme->on('lot_stamps') ? '' : 'no-lot' }}" style="--lot-label: '{{ str_replace(['\\', '\''], '', $theme->label('lot_stamps')) }} '">
                    @foreach ($products as $product)
                        @include('themes.timber._card', ['product' => $product, 'badge' => null, 'gvDelay' => ($loop->index % 3) * 0.08])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif

            @if (! $isFiltered)
                {{-- Use-class guide — the treated-wood signature band. --}}
                @if ($theme->on('use_classes'))
                    <section class="ucs reveal">
                        <div class="sec-head" style="margin-top: 0;">
                            <span class="kicker">{{ __('site.storefront.timber.uc_eyebrow') }}</span>
                            <h2>{{ __('site.storefront.timber.uc_h2') }}</h2>
                        </div>
                        <div class="grid">
                            @foreach ([1 => '25%', 2 => '48%', 3 => '72%', 4 => '100%'] as $n => $depth)
                                <div class="uc" style="--depth: {{ $depth }};">
                                    <span class="tag">UC{{ $n }}</span>
                                    <h3>{{ __('site.storefront.timber.uc' . $n . '_title') }}</h3>
                                    <p>{{ __('site.storefront.timber.uc' . $n . '_desc') }}</p>
                                    <div class="gauge" role="img" aria-label="UC{{ $n }}"><i></i></div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Story band — the sawmill manifesto, framed two-up. --}}
                @if ($theme->on('explain'))
                    <section class="explain" data-gv-reveal>
                        <div class="art">
                            @if ($explainImg = $theme->image('explain_image'))
                                <img src="{{ $explainImg }}" alt="" loading="lazy" data-gv-parallax="0.08" style="width: 100%; height: 112%; object-fit: cover; display: block;">
                            @else
                                @if ($theme->on('grain_rings'))<div class="ring a1"></div><div class="ring a2"></div>@endif
                                <div class="stamp">{{ $tenant->name }}<br>★</div>
                            @endif
                        </div>
                        <div class="txt">
                            <div class="k">{{ __('site.storefront.featured.eyebrow') }}</div>
                            <h3>{!! __('site.storefront.hero.headline', ['tenant' => '<em>' . e($tenant->name) . '</em>']) !!}</h3>
                            <p>{{ __('site.storefront.hero.sub') }}</p>
                            <p>{{ $theme->copy('explain_body') }}</p>
                            <div><a class="btn outline" href="#shop">{{ __('site.storefront.hero.cta_secondary') }}</a></div>
                        </div>
                    </section>
                @endif

                {{-- Trade & bulk — the walnut counter. --}}
                @if ($theme->on('bulk_band'))
                    <section class="bulk" data-gv-reveal>
                        <div>
                            <div class="k">{{ __('site.storefront.timber.bulk_eyebrow') }}</div>
                            <h3>{{ __('site.storefront.timber.bulk_h2') }}</h3>
                            <p>{{ $theme->copy('bulk_body') }}</p>
                        </div>
                        <div class="side">
                            <span class="row">{{ __('site.storefront.timber.bulk_row1') }}</span>
                            <span class="row">{{ __('site.storefront.timber.bulk_row2') }}</span>
                            <span class="row">{{ __('site.storefront.timber.bulk_row3') }}</span>
                            @if ($tradeEmail)
                                <a class="btn" href="mailto:{{ $tradeEmail }}">{{ __('site.storefront.timber.bulk_cta') }}</a>
                            @else
                                <a class="btn" href="#shop">{{ __('site.storefront.featured.browse_all') }}</a>
                            @endif
                        </div>
                    </section>
                @endif

                {{-- The price list — newsletter. --}}
                @if ($theme->on('news_band'))
                <section class="news" data-gv-reveal="scale">
                    <h3>{!! __('site.storefront.promo.h2_prefix', ['tenant' => '<em>' . e($tenant->name) . '</em>']) !!}</h3>
                    <p>{{ $theme->copy('news_body') }}</p>
                    <form onsubmit="return false">
                        <input type="email" placeholder="{{ __('site.storefront.footer.newsletter_placeholder') }}" aria-label="{{ __('site.storefront.footer.newsletter_placeholder') }}">
                        <button type="submit" class="btn">{{ __('site.storefront.footer.subscribe') }}</button>
                    </form>
                </section>
                @endif
            @endif
        </div>
    </main>
@endsection
