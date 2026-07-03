@extends('themes.ember.layout')

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
        $heroProduct = $products->first();
        $heroImageUrl = $csHero['enabled'] && $csHero['image_path']
            ? \Illuminate\Support\Facades\Storage::url($csHero['image_path'])
            : null;
        // The board lists the first handful of products as menu rows.
        $boardRows = $products->take(5)->values();
    @endphp

    <style>
        /* ===== HERO — lede over the roast "menu board" ===== */
        .hero { padding: 56px 0 30px; }
        .hero .top { text-align: center; margin-bottom: 36px; }
        .hero .lead { max-width: 720px; margin: 0 auto; }
        .hero .lead h1 { font-family: var(--display); font-weight: 700; font-size: clamp(46px, 8vw, 108px); line-height: .96; letter-spacing: -.022em; margin: 14px 0 14px; }
        .hero .lead h1 em { font-style: italic; color: var(--accent); letter-spacing: -.01em; }
        .hero .lead p { color: var(--muted); font-size: 17px; max-width: 50ch; margin: 0 auto 26px; }
        .hero .lead .cta { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

        /* the board — card stock: hard offset shadow + a soft ambient drop,
           inner hairline "printed margin", and a coffee-ring stain kissed
           over the top-right corner. Menu rows carry leader dots + roast pips. */
        .board { border: 2px solid var(--ink); background: var(--card); border-radius: 4px; padding: 40px 44px; position: relative; box-shadow: 8px 8px 0 var(--soft2), 0 34px 60px -38px rgba(44, 30, 21, .55); }
        .board::before { content: ""; position: absolute; inset: 8px; border: 1px solid var(--rule); border-radius: 2px; pointer-events: none; }
        .board::after { content: ""; position: absolute; top: -32px; right: 30px; width: 112px; height: 102px; border: 3px solid var(--accent); border-radius: 50%; box-shadow: inset 0 0 0 1.5px var(--accent), inset 3px 4px 0 -1px color-mix(in srgb, var(--accent) 55%, transparent); opacity: .11; transform: rotate(-7deg); pointer-events: none; }
        .board.no-stain::after { display: none; }
        .board .bh { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; border-bottom: 2px solid var(--ink); padding-bottom: 14px; margin-bottom: 8px; }
        .board .bh h2 { font-family: var(--display); font-weight: 700; font-size: 28px; letter-spacing: -.015em; }
        /* the "roast schedule" chip — hand-stamped: tilted + overprint mask */
        .board .bh .when { font-family: var(--mono); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--accent); border: 1.5px solid var(--accent); border-radius: 2px; padding: 5px 11px; transform: rotate(-1.8deg); -webkit-mask-image: var(--stamp); mask-image: var(--stamp); }
        /* menu rows with leader dots */
        .mrow { display: grid; grid-template-columns: 1fr auto auto; gap: 12px; align-items: baseline; padding: 16px 0; border-bottom: 1px solid var(--rule); color: inherit; }
        .mrow:last-child { border-bottom: none; }
        .mrow:hover .nm { color: var(--accent); }
        .mrow .left { display: flex; flex-direction: column; }
        .mrow .nm { font-family: var(--display); font-weight: 600; font-size: 21px; letter-spacing: -.01em; transition: color .2s ease; }
        .mrow .nt { font-family: var(--mono); font-size: 11px; color: var(--muted); margin-top: 2px; text-transform: uppercase; letter-spacing: .04em; }
        .mrow .roast { margin-top: 7px; }
        /* leader dots drawn as a repeating radial so they can TIGHTEN on
           hover (background-size interpolates); ink warms to the accent. */
        .mrow .lead-dots { align-self: center; min-width: 40px; height: 3px; transform: translateY(-4px); background-image: radial-gradient(circle at 1.5px 1.5px, var(--line) 1.25px, transparent 1.6px); background-size: 9px 3px; background-repeat: repeat-x; transition: background-size .35s ease; }
        .mrow:hover .lead-dots { background-image: radial-gradient(circle at 1.5px 1.5px, var(--accent) 1.25px, transparent 1.6px); background-size: 6px 3px; }
        .mrow .pr { font-family: var(--display); font-weight: 600; font-size: 20px; color: var(--accent); font-variant-numeric: tabular-nums; transition: color .2s ease; }
        .mrow:hover .pr { color: var(--primary-strong); }
        .mrow:hover .roast i.on { transform: scale(1.3); }
        /* staggered pour-in: each row lands a beat after the last */
        .board .mrow { opacity: 0; animation: mrowIn .65s cubic-bezier(.19, .7, .16, 1) forwards; animation-delay: calc(.5s + var(--i, 0) * .09s); }
        @keyframes mrowIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) {
            .board .mrow { animation: none; opacity: 1; }
            .mrow .lead-dots { transition: none; }
            .mrow:hover .roast i.on { transform: none; }
        }

        /* ledger stats strip — tabular mono numerals between hard rules,
           reads like a roaster's tally book */
        .ledger { display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap; padding: 26px 0; margin-top: 30px; border-top: 1.5px solid var(--ink); border-bottom: 1.5px solid var(--ink); font-family: var(--mono); font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
        .ledger span { display: inline-flex; align-items: baseline; gap: 9px; }
        .ledger b { font-family: var(--mono); font-weight: 700; font-size: 19px; color: var(--ink); letter-spacing: -.02em; text-transform: none; font-variant-numeric: tabular-nums; }

        .home-empty { text-align: center; padding: 70px 24px; font-family: var(--display); font-style: italic; font-size: 22px; color: var(--muted); }

        /* ===== craft band — two-up, image + ledger copy ===== */
        .craft { display: grid; grid-template-columns: 1fr 1fr; margin: 90px 0; border: 1.5px solid var(--ink); border-radius: 4px; overflow: hidden; align-items: stretch; background: var(--card); box-shadow: 6px 6px 0 var(--soft2); }
        .craft .img { min-height: 420px; }
        /* warm ember light rising through the roastery photo/placeholder */
        .craft .img::after { content: ""; position: absolute; inset: 0; background: radial-gradient(85% 65% at 50% 112%, color-mix(in srgb, var(--accent) 42%, transparent) 0%, transparent 68%), linear-gradient(205deg, rgba(242, 234, 220, .12), transparent 40%); pointer-events: none; }
        .craft .txt { padding: 54px; }
        .craft .est { font-family: var(--mono); font-size: 12px; letter-spacing: .1em; text-transform: uppercase; color: var(--accent); }
        .craft h3 { font-family: var(--display); font-weight: 700; font-size: clamp(28px, 3.6vw, 44px); margin: 12px 0 14px; line-height: 1.04; letter-spacing: -.015em; }
        .craft h3 em { font-style: italic; color: var(--accent); }
        .craft p { color: var(--muted); max-width: 42ch; margin-bottom: 22px; }

        /* ===== subscription band — dark roastery slab, ember glow from the
           top edge + a printed inner margin echoing the board ===== */
        .subband { background: radial-gradient(75% 110% at 50% -12%, #452817 0%, rgba(69, 40, 23, 0) 62%), var(--deep); color: var(--soft); border-radius: 4px; padding: 56px; margin: 90px 0; text-align: center; position: relative; overflow: hidden; }
        .subband::before { content: ""; position: absolute; inset: 9px; border: 1px solid rgba(229, 216, 196, .16); border-radius: 2px; pointer-events: none; }
        .subband .est { font-family: var(--mono); font-size: 12px; letter-spacing: .1em; text-transform: uppercase; color: #e0a07a; }
        .subband h3 { font-family: var(--display); font-weight: 700; font-size: clamp(30px, 4vw, 52px); margin: 12px 0 10px; letter-spacing: -.018em; }
        .subband h3 em { font-style: italic; color: #e0a07a; }
        .subband p { color: #cdbdab; max-width: 44ch; margin: 0 auto 26px; }

        @media (max-width: 1000px) { .craft { grid-template-columns: 1fr; } .craft .img { min-height: 280px; } }
        @media (max-width: 680px) { .board { padding: 28px 22px; } .mrow { grid-template-columns: 1fr auto; } .mrow .lead-dots { display: none; } .subband, .craft .txt { padding: 38px 26px; } }

        /* category pills — squared chips, ink fill when active */
        .pills { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin: 28px 0 42px; }
        .pills .pill { border: 1.5px solid var(--line); background: var(--card); border-radius: 2px; padding: 10px 20px; font-family: var(--mono); font-size: 12px; text-transform: uppercase; letter-spacing: .02em; color: var(--ink); transition: background-color .2s ease, color .2s ease, border-color .2s ease; }
        .pills .pill.on, .pills .pill:hover { background: var(--ink); border-color: var(--ink); color: var(--bg); }

        /* ===== Collection rails — Ember restyle of the shared .cs-* partial. ===== */
        .wrap .cs-strip { position: relative; margin: 64px 0 0; padding: 40px 36px; border: 1.5px solid var(--ink); border-radius: 4px; background: var(--soft); overflow: hidden; }
        .wrap .cs-banner { display: none; }
        .wrap .cs-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin: 0 0 26px; flex-wrap: wrap; border-bottom: 2px solid var(--ink); padding-bottom: 14px; }
        .wrap .cs-head h2 { font-family: var(--display); font-weight: 700; font-size: clamp(26px, 3vw, 40px); line-height: 1.05; }
        .wrap .cs-head p { color: var(--muted); font-size: 14px; max-width: 50ch; margin-top: 4px; }
        .wrap .cs-view-all { font-family: var(--mono); font-size: 12px; text-transform: uppercase; color: var(--ink); border: 1.5px solid var(--ink); border-radius: 2px; padding: 10px 18px; background: var(--card); white-space: nowrap; transition: background-color .2s ease, color .2s ease; }
        .wrap .cs-view-all:hover { background: var(--ink); color: var(--bg); }
        .wrap .cs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); gap: 22px; }
        .wrap .cs-card { display: flex; flex-direction: column; color: inherit; background: var(--card); border: 1.5px solid var(--ink); border-radius: 4px; overflow: hidden; transition: transform .22s ease, box-shadow .22s ease; }
        .wrap .cs-card:hover { transform: translateY(-5px); box-shadow: 6px 6px 0 var(--soft2); }
        .wrap .cs-img { aspect-ratio: 1 / 1; overflow: hidden; background: radial-gradient(120% 120% at 50% 25%, #6b3a22, #2c1a10); }
        .wrap .cs-card:nth-child(even) .cs-img { background: radial-gradient(120% 120% at 50% 25%, #8a5232, #3a2415); }
        .wrap .cs-img img { width: 100%; height: 100%; object-fit: cover; }
        .wrap .cs-meta { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; text-align: left; padding: 16px 18px 18px; }
        .wrap .cs-name { font-family: var(--display); font-weight: 600; font-size: 17px; line-height: 1.2; }
        .wrap .cs-price { font-family: var(--display); font-weight: 600; font-size: 18px; font-variant-numeric: tabular-nums; color: var(--accent); }
        @media (prefers-reduced-motion: reduce) { .wrap .cs-card, .wrap .cs-card:hover { transform: none; } }
        @media (max-width: 540px) { .wrap .cs-strip { padding: 28px 22px; } .wrap .cs-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>

    <main>
        @if (! $isFiltered)
            <div class="wrap">
                <section class="hero">
                    <div class="top">
                        <div class="lead">
                            <div class="kicker reveal">{{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</div>
                            <h1 class="reveal s1">@if ($csHero['subtitle'] !== ''){{ $csHero['subtitle'] }}@else{!! __('site.storefront.hero.headline', ['tenant' => '<em>' . e($tenant->name) . '</em>']) !!}@endif</h1>
                            <p class="reveal s2">{{ __('site.storefront.hero.sub') }}</p>
                            <div class="cta reveal s3">
                                <a class="btn accent" href="#shop">{{ $csHero['cta_label'] !== '' ? $csHero['cta_label'] : __('site.storefront.hero.cta_primary') }}</a>
                                <a class="btn ghost" href="#shop">{{ __('site.storefront.hero.cta_secondary') }}</a>
                            </div>
                        </div>
                    </div>

                    @if ($boardRows->isNotEmpty() && $theme->on('menu_board'))
                        <div class="board reveal s2 {{ $theme->on('ring_stain') ? '' : 'no-stain' }}">
                            <div class="bh">
                                <h2>{{ $theme->copy('board_heading') }}</h2>
                                <span class="when">{{ __('site.storefront.shop_all.eyebrow') }}</span>
                            </div>
                            @foreach ($boardRows as $cp)
                                @php
                                    // Level pips — deterministic per product so the
                                    // board reads the same on every visit (2–4 of 5 lit).
                                    $roastOn = ($cp->id % 3) + 2;
                                @endphp
                                <a class="mrow" href="/products/{{ $cp->slug }}" style="--i: {{ $loop->index }};">
                                    <div class="left">
                                        <div class="nm">{{ $cp->name }}</div>
                                        @if ($cp->description)<div class="nt">{{ \Illuminate\Support\Str::limit(strip_tags($cp->description), 48) }}</div>@endif
                                        @if ($theme->on('roast_pips'))
                                            <div class="roast" role="img" aria-label="{{ $theme->label('roast_pips') }} {{ $roastOn }}/5">
                                                @for ($i = 0; $i < 5; $i++)<i class="{{ $i < $roastOn ? 'on' : '' }}"></i>@endfor
                                            </div>
                                        @endif
                                    </div>
                                    <div class="lead-dots" aria-hidden="true"></div>
                                    <div class="pr">@money($cp->price_cents)</div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            {{-- Ledger strip — figures drawn from the live catalog, not generic
                 "free shipping" boilerplate. Reads like a roaster's tally. --}}
            @if ($theme->on('stats_strip'))
                <div class="wrap">
                    <div class="ledger reveal">
                        <span><b>{{ $products->total() }}</b> {{ __('site.storefront.collections.heading') }}</span>
                        @if ($categories->isNotEmpty())<span><b>{{ $categories->count() }}</b> {{ __('site.storefront.controls.category') }}</span>@endif
                        <span><b>48h</b> {{ __('site.storefront.value_props.checkout_title') }}</span>
                        <span><b>{{ __('site.storefront.value_props.shipping_title') }}</b></span>
                    </div>
                </div>
            @endif
        @endif

        <div class="wrap">
            {{-- Curated collections (only when the merchant features them) --}}
            @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
                @include('storefront.partials.collection-strips')
            @endif

            {{-- Shop all — the catalog --}}
            <div class="sec-head reveal" id="shop">
                <span class="kicker">{{ __('site.storefront.shop_all.eyebrow') }}</span>
                <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
            </div>

            @if ($categories->isNotEmpty())
                <div class="pills reveal">
                    <a href="/" class="pill {{ ! ($filters['category'] ?? null) ? 'on' : '' }}">{{ __('site.storefront.controls.category_all') }}</a>
                    @foreach ($categories as $cat)
                        <a href="/?category={{ $cat->slug }}" class="pill {{ ($filters['category'] ?? null) === $cat->slug ? 'on' : '' }}">{{ $cat->name }}</a>
                    @endforeach
                </div>
            @endif

            @if ($products->isEmpty())
                <div class="home-empty reveal">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="blooms">
                    @foreach ($products as $product)
                        @include('themes.ember._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif
        </div>

        @if (! $isFiltered && ($theme->on('craft_band') || $theme->on('subscribe_band')))
            <div class="wrap">
                {{-- Craft band — "one roaster, one small room" two-up, image left,
                     ledger copy right. A signature Ember section. --}}
                @if ($theme->on('craft_band'))
                    <section class="craft reveal">
                        <div class="img bloomph ph" aria-hidden="true"></div>
                        <div class="txt">
                            <div class="est">// {{ __('site.storefront.nav.featured') }}</div>
                            <h3>{!! __('site.storefront.hero.headline', ['tenant' => '<em>' . e($tenant->name) . '</em>']) !!}</h3>
                            <p>{{ $theme->copy('craft_body') }}</p>
                            <a class="btn ghost" href="#shop">{{ __('site.storefront.hero.cta_secondary') }}</a>
                        </div>
                    </section>
                @endif

                {{-- Subscription / newsletter band — dark slab, reuses the promo
                     keys. Replaces a generic centered card. --}}
                @if ($theme->on('subscribe_band'))
                    <section class="subband reveal">
                        <div class="est">// {{ __('site.storefront.collections.heading') }}</div>
                        <h3>{!! __('site.storefront.promo.h2_prefix', ['tenant' => '<em>' . e($tenant->name) . '</em>']) !!}</h3>
                        <p>{{ $theme->copy('subscribe_body') }}</p>
                        <a class="btn accent" href="#shop">{{ __('site.storefront.hero.cta_primary') }}</a>
                    </section>
                @endif
            </div>
        @endif
    </main>

    @push('scripts')
        <script>
            // Ledger tally count-up — mono figures tick from 0 when the strip
            // scrolls into view. Skipped entirely under reduced motion.
            (function () {
                if (! ('IntersectionObserver' in window)) return;
                if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                var els = Array.prototype.slice.call(document.querySelectorAll('.ledger b'))
                    .filter(function (el) { return /^\d/.test(el.textContent.trim()); });
                if (! els.length) return;
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (en) {
                        if (! en.isIntersecting) return;
                        io.unobserve(en.target);
                        var m = en.target.textContent.trim().match(/^(\d+)(.*)$/);
                        if (! m) return;
                        var target = parseInt(m[1], 10), suffix = m[2] || '', t0 = null, dur = 900;
                        function tick(ts) {
                            if (t0 === null) t0 = ts;
                            var p = Math.min(1, (ts - t0) / dur);
                            var eased = 1 - Math.pow(1 - p, 3);
                            en.target.textContent = Math.round(target * eased) + suffix;
                            if (p < 1) requestAnimationFrame(tick);
                        }
                        requestAnimationFrame(tick);
                    });
                }, { threshold: .4 });
                els.forEach(function (el) { io.observe(el); });
            })();
        </script>
    @endpush
@endsection
