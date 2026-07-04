@extends('themes.wick.layout')

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
        // The hero "jar on the stage" leans on the lead product. Its name +
        // category drive the jar label; its photo (if any) fills the vessel.
        $heroProduct = $products->first();
        $heroImageUrl = $csHero['enabled'] && $csHero['image_path']
            ? \Illuminate\Support\Facades\Storage::url($csHero['image_path'])
            : ($heroProduct && $heroProduct->image_path
                ? \Illuminate\Support\Facades\Storage::url($heroProduct->image_path)
                : null);
        $heroProdCat = $heroProduct && $heroProduct->relationLoaded('categories') && $heroProduct->categories->isNotEmpty()
            ? $heroProduct->categories->first()->name
            : null;
        // The discovery-case art panel uses up to six product photos as thumbs.
        $caseSix = $products->take(6)->values();
    @endphp

    <style>
        /* ===== HERO — copy left, a single dramatic amber jar on its stage right.
           A cream jar label leans on the vessel; a small lit votive keeps it
           company. This is the Wick signature: candlelit theatre, not a card rail. */
        .hero { position: relative; display: grid; grid-template-columns: 1.1fr .9fr; gap: 48px; align-items: center; padding: 54px 0 0; min-height: 80vh; }
        /* ghost batch number — an oversized outlined numeral sunk into the dark */
        .hero::before { content: "№01"; position: absolute; right: -3%; bottom: -8%; z-index: 0; pointer-events: none; font-family: var(--display); font-weight: 800; letter-spacing: -.05em; line-height: 1; font-size: clamp(150px, 24vw, 320px); color: transparent; -webkit-text-stroke: 1px rgba(217, 154, 78, .12); }
        .hero.no-mark::before { display: none; }
        .hero .stage.no-flame .glow, .hero .stage.no-flame .flame, .hero .stage.no-flame .votive::before { display: none; }
        .hero .lead { position: relative; z-index: 3; }
        .hero .lead .k { font-family: var(--mono); font-size: 12px; letter-spacing: .06em; color: var(--accent); text-transform: uppercase; }
        .hero .lead .k::before { content: "// "; color: var(--faint); }
        .hero .lead h1 { font-family: var(--display); font-weight: 800; letter-spacing: -.03em; font-size: clamp(54px, 8vw, 120px); line-height: .9; margin: 18px 0 18px; }
        .hero .lead h1 .o { color: var(--accent); text-shadow: 0 0 40px color-mix(in srgb, var(--accent) 45%, transparent); }
        .hero .lead h1 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--accent); }
        .hero .lead p { color: var(--muted); font-size: 17px; max-width: 40ch; margin: 0 0 28px; }
        .hero .lead .cta { display: flex; gap: 14px; flex-wrap: wrap; }

        .hero .stage { position: relative; display: grid; place-items: center; min-height: 520px; }
        /* melted-wax rings left on the workbench */
        .hero .stage .halo.h1 { width: 150px; height: 150px; left: 2%; bottom: 4%; }
        .hero .stage .halo.h2 { width: 74px; height: 74px; left: 16%; bottom: 16%; opacity: .55; }
        /* SIGNATURE MOTION — the candle glow behind the jar breathes like a real
           flame: opacity + scale drift on an irregular clock. Very subtle. */
        .hero .stage .glow { position: absolute; left: 50%; top: 52%; transform: translate(-50%, -50%); width: 520px; height: 520px; border-radius: 50%; pointer-events: none; z-index: 0; background: radial-gradient(circle, rgba(217, 154, 78, .17) 0%, rgba(217, 154, 78, .07) 38%, transparent 68%); animation: flicker 7.3s ease-in-out infinite; will-change: opacity, transform; }
        @keyframes flicker {
            0%, 100% { opacity: .85; transform: translate(-50%, -50%) scale(1); }
            13%      { opacity: 1;   transform: translate(-50%, -50%) scale(1.03); }
            27%      { opacity: .78; transform: translate(-50%, -50%) scale(.985); }
            41%      { opacity: .95; transform: translate(-50%, -50%) scale(1.015); }
            58%      { opacity: .82; transform: translate(-50%, -50%) scale(.99); }
            71%      { opacity: 1;   transform: translate(-50%, -50%) scale(1.025); }
            86%      { opacity: .88; transform: translate(-50%, -50%) scale(1.005); }
        }
        .hero .jarbig { position: relative; width: 230px; height: 320px; border-radius: 26px 26px 20px 20px; background: linear-gradient(168deg, #5f4426, #241811); border: 1px solid var(--line2); box-shadow: 0 40px 80px -30px rgba(0, 0, 0, .7), inset 0 -70px 90px -50px rgba(232, 176, 106, .35); overflow: hidden; animation: bfloat 7s ease-in-out infinite; z-index: 2; }
        @keyframes bfloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        /* brass lid resting on the rim */
        .hero .jarbig .cap { position: absolute; top: -1px; left: 50%; transform: translateX(-50%); width: 210px; height: 26px; background: linear-gradient(180deg, #7a5f3a, #45331d); border-radius: 12px; z-index: 2; }
        .hero .jarbig img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        /* lit from within — a warm core rising off the wax pool */
        .hero .jarbig::after { content: ""; position: absolute; inset: auto 0 0 0; height: 55%; pointer-events: none; background: radial-gradient(120% 100% at 50% 100%, rgba(232, 176, 106, .30), transparent 70%); }
        /* the tiny flame — a soft ellipse with a radial halo, breathing with the glow */
        .hero .stage .flame { position: absolute; left: 50%; top: 13%; transform: translateX(-50%); width: 14px; height: 26px; border-radius: 50% 50% 50% 50% / 62% 62% 38% 38%; background: radial-gradient(circle at 50% 72%, #ffe9c4 0%, #e8b06a 46%, rgba(217, 154, 78, .25) 78%, transparent 100%); box-shadow: 0 0 26px 8px rgba(232, 176, 106, .30), 0 0 60px 22px rgba(217, 154, 78, .12); z-index: 4; animation: flicker 7.3s ease-in-out infinite reverse; }
        .hero .label { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%) rotate(-1.5deg); width: 178px; background: var(--label); color: #2c2014; border-radius: 3px; padding: 18px 16px; text-align: center; box-shadow: 0 14px 30px -12px rgba(0, 0, 0, .6); z-index: 3; animation: lfloat 7s ease-in-out infinite; }
        @keyframes lfloat { 0%, 100% { transform: translate(-50%, -50%) rotate(-1.5deg); } 50% { transform: translate(-50%, -56%) rotate(-.8deg); } }
        .hero .label .ln { font-family: var(--mono); font-size: 9px; letter-spacing: .16em; text-transform: uppercase; color: #8a5f36; }
        .hero .label h3 { font-family: var(--serif); font-weight: 500; font-style: italic; font-size: 22px; line-height: 1.05; margin: 6px 0; }
        .hero .label .yr { font-family: var(--mono); font-weight: 700; font-size: 13px; letter-spacing: .1em; text-transform: uppercase; }
        .hero .label .div { height: 1px; background: #d9c9ae; margin: 8px 0; }
        /* a small lit votive keeping the big jar company */
        .hero .votive { position: absolute; right: 8%; top: 22%; width: 78px; height: 96px; border-radius: 14px 14px 12px 12px; background: linear-gradient(160deg, rgba(217, 154, 78, .5), rgba(217, 154, 78, .1)); border: 1px solid var(--line2); box-shadow: inset 0 -26px 30px -16px rgba(232, 176, 106, .35), 0 0 34px -6px rgba(217, 154, 78, .25); animation: bfloat 6s ease-in-out infinite .5s; z-index: 1; }
        .hero .votive::before { content: ""; position: absolute; left: 50%; top: -10px; transform: translateX(-50%); width: 8px; height: 15px; border-radius: 50% 50% 50% 50% / 62% 62% 38% 38%; background: radial-gradient(circle at 50% 72%, #ffe9c4 0%, #e8b06a 55%, transparent 100%); box-shadow: 0 0 18px 5px rgba(232, 176, 106, .28); }

        /* facts strip — mono bench notes on a ruled band (NOT a shipping strip) */
        .strip { display: flex; justify-content: space-between; gap: 30px; flex-wrap: wrap; padding: 26px 0; margin-top: 30px; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); font-family: var(--mono); font-size: 12px; color: var(--muted); }
        .strip b { color: var(--txt); }

        /* ===== EDITORIAL TICKET — art panel + manifesto. Sets Wick apart
           from a card-rail layout with a dense, framed two-up. */
        .explain { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin: 100px 0; border: 1px solid var(--line); border-radius: 12px; overflow: hidden; align-items: stretch; }
        .explain .art { min-height: 460px; background: radial-gradient(120% 120% at 30% 20%, #52391f, #1a120c); position: relative; }
        /* rings of cooled wax left on the art panel */
        .explain .art::before { content: ""; position: absolute; right: 36px; bottom: 34px; width: 132px; height: 132px; border-radius: 50%; border: 2px solid rgba(217, 154, 78, .30); box-shadow: inset 0 0 24px rgba(217, 154, 78, .18), 0 0 26px rgba(217, 154, 78, .10); }
        .explain .art::after { content: ""; position: absolute; right: 62px; bottom: 78px; width: 64px; height: 64px; border-radius: 50%; border: 1px solid rgba(217, 154, 78, .16); }
        .explain .art .stamp { position: absolute; left: 40px; top: 40px; width: 122px; height: 122px; border: 2px solid var(--accent); border-radius: 50%; display: grid; place-items: center; text-align: center; color: var(--accent); font-family: var(--mono); font-size: 10px; letter-spacing: .1em; line-height: 1.5; text-transform: uppercase; transform: rotate(-12deg); opacity: .85; animation: stampspin 50s linear infinite; }
        @keyframes stampspin { from { transform: rotate(-12deg); } to { transform: rotate(348deg); } }
        .explain .txt { background: var(--surface); padding: 60px 54px; display: flex; flex-direction: column; justify-content: center; }
        .explain .k { font-family: var(--mono); font-size: 12px; color: var(--accent); text-transform: uppercase; letter-spacing: .04em; }
        .explain h3 { font-family: var(--display); font-weight: 800; letter-spacing: -.02em; font-size: clamp(30px, 3.8vw, 46px); margin: 14px 0 16px; line-height: 1.02; }
        .explain h3 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--accent); }
        .explain p { color: var(--muted); max-width: 42ch; margin-bottom: 14px; }

        /* ===== DISCOVERY SET — a curated sampler on a surface panel, with a 6-up
           thumbnail wall built from real product photos / jar marks. */
        .case { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 54px; margin: 90px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center; }
        .case .k { font-family: var(--mono); font-size: 12px; color: var(--accent); text-transform: uppercase; letter-spacing: .04em; }
        .case h3 { font-family: var(--display); font-weight: 800; letter-spacing: -.02em; font-size: clamp(28px, 3.4vw, 42px); margin: 12px 0 12px; }
        .case h3 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--accent); }
        .case p { color: var(--muted); max-width: 38ch; margin-bottom: 20px; }
        .case .grid6 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .case .grid6 .b { height: 132px; border-radius: 8px; border: 1px solid var(--line); overflow: hidden; display: grid; place-items: center; }
        .case .grid6 .b img { width: 100%; height: 100%; object-fit: cover; }
        .case .grid6 .b .jar-mark { width: 52px; height: 62%; border-radius: 10px 10px 8px 8px; background: var(--jar); border: 1px solid var(--line2); box-shadow: inset 0 -18px 22px -12px rgba(217, 154, 78, .28); }
        /* the six jars slot into the set one by one as it scrolls in */
        .case.reveal .grid6 .b { opacity: 0; transform: translateY(16px); transition: opacity .6s ease, transform .7s cubic-bezier(.19, .7, .16, 1); }
        .case.reveal.in .grid6 .b { opacity: 1; transform: none; }
        .case.reveal.in .grid6 .b:nth-child(1) { transition-delay: .10s; } .case.reveal.in .grid6 .b:nth-child(2) { transition-delay: .18s; }
        .case.reveal.in .grid6 .b:nth-child(3) { transition-delay: .26s; } .case.reveal.in .grid6 .b:nth-child(4) { transition-delay: .34s; }
        .case.reveal.in .grid6 .b:nth-child(5) { transition-delay: .42s; } .case.reveal.in .grid6 .b:nth-child(6) { transition-delay: .50s; }

        /* ===== NEWSLETTER — the dropping list, centered. */
        .news { text-align: center; margin: 90px auto; max-width: 540px; }
        .news h3 { font-family: var(--display); font-weight: 800; letter-spacing: -.02em; font-size: clamp(28px, 3.4vw, 42px); }
        .news h3 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--accent); }
        .news p { color: var(--muted); margin: 12px 0 24px; }
        .news form { display: flex; gap: 10px; }
        .news input { flex: 1; border: 1px solid var(--line2); border-radius: 99px; background: var(--surface); padding: 14px 20px; color: var(--txt); font-family: var(--mono); font-size: 13px; }
        .news input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 20%, transparent), 0 0 26px -6px color-mix(in srgb, var(--accent) 40%, transparent); }

        .home-empty { text-align: center; padding: 70px 24px; font-family: var(--serif); font-style: italic; font-size: 22px; color: var(--muted); }

        /* category pills — keeps the shared .pill contract; replaces a sort/search toolbar */
        .pills { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin: 28px 0 42px; }
        .pills .pill { border: 1px solid var(--line2); background: var(--surface); border-radius: 99px; padding: 9px 18px; font-family: var(--mono); font-size: 11px; letter-spacing: .02em; text-transform: uppercase; color: var(--muted); transition: background-color .2s ease, color .2s ease, border-color .2s ease; }
        .pills .pill.on, .pills .pill:hover { background: var(--accent); border-color: var(--accent); color: var(--bg); }

        /* ===== Collection rails — dark Wick restyle of the shared .cs-*
           partial: each featured collection is a framed surface panel with dark
           jar-label cards. `.wrap `-prefixed to beat the partial's own rules. */
        .wrap .cs-strip { position: relative; margin: 64px 0 0; padding: 40px 36px; border-radius: 12px; border: 1px solid var(--line); background: var(--surface); overflow: hidden; }
        .wrap .cs-banner { display: none; }
        .wrap .cs-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin: 0 0 26px; flex-wrap: wrap; border-bottom: 1px solid var(--line); padding-bottom: 14px; }
        .wrap .cs-head h2 { font-family: var(--display); font-weight: 800; letter-spacing: -.02em; font-size: clamp(26px, 3vw, 40px); line-height: 1.05; }
        .wrap .cs-head p { color: var(--muted); font-size: 14px; max-width: 50ch; margin-top: 4px; }
        .wrap .cs-view-all { font-family: var(--mono); font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--accent); border: 1px solid var(--line2); border-radius: 99px; padding: 10px 18px; background: transparent; white-space: nowrap; transition: border-color .2s ease, color .2s ease; }
        .wrap .cs-view-all:hover { border-color: var(--accent); }
        .wrap .cs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); gap: 22px; }
        .wrap .cs-card { display: flex; flex-direction: column; color: inherit; transition: transform .25s cubic-bezier(.19, .7, .16, 1); }
        .wrap .cs-card:hover { transform: translateY(-5px); }
        .wrap .cs-img { aspect-ratio: 3 / 4; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; background: var(--jar2); margin-bottom: 12px; }
        .wrap .cs-card:nth-child(even) .cs-img { background: var(--jar); }
        .wrap .cs-img img { width: 100%; height: 100%; object-fit: cover; }
        .wrap .cs-meta { display: flex; flex-direction: column; gap: 3px; padding: 0; }
        .wrap .cs-name { font-family: var(--serif); font-weight: 500; font-size: 17px; line-height: 1.2; }
        .wrap .cs-price { font-family: var(--display); font-weight: 800; font-size: 17px; font-variant-numeric: tabular-nums; color: var(--txt); }

        @media (prefers-reduced-motion: reduce) {
            .hero .votive, .hero .jarbig, .hero .label, .hero .stage .glow, .hero .stage .flame, .explain .art .stamp { animation: none; }
            .wrap .cs-card, .wrap .cs-card:hover { transform: none; }
            .case.reveal .grid6 .b { opacity: 1; transform: none; transition: none; }
        }
        @media (max-width: 1000px) {
            .hero { grid-template-columns: 1fr; min-height: auto; padding: 36px 0 0; }
            .hero .stage { min-height: 420px; margin-top: 24px; }
            .explain, .case { grid-template-columns: 1fr; }
            .explain .art { min-height: 260px; }
        }
        @media (max-width: 680px) {
            .case { padding: 30px 24px; }
        }
        @media (max-width: 540px) {
            .wrap .cs-strip { padding: 28px 22px; }
            .wrap .cs-grid { grid-template-columns: repeat(2, 1fr); }
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
                    <div class="stage {{ $theme->on('flame') ? '' : 'no-flame' }}" data-gv-reveal="scale" data-gv-delay="0.25" data-gv-parallax="0.06" aria-hidden="true">
                        <div class="glow"></div>
                        <div class="halo h1"></div>
                        <div class="halo h2"></div>
                        <div class="votive"></div>
                        <div class="flame"></div>
                        <div class="jarbig">
                            <div class="cap"></div>
                            @if ($heroImageUrl)<img src="{{ $heroImageUrl }}" alt="">@endif
                        </div>
                        @if ($theme->on('jar_label'))
                            <div class="label">
                                @if ($heroProdCat)<div class="ln">{{ $heroProdCat }}</div>@else<div class="ln">{{ $tenant->name }}</div>@endif
                                <h3>{{ $heroProduct->name ?? $tenant->name }}</h3>
                                <div class="div"></div>
                                <div class="yr">{{ $theme->label('batch_numerals') }} № {{ date('y') }}</div>
                                <div class="ln" style="margin-top: 6px;">{{ $theme->copy('label_note') }}</div>
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            {{-- Facts band — bench notes in mono, not a free-shipping strip. --}}
            @if ($theme->on('facts_strip'))
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

            {{-- On the bench now — the catalog --}}
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
                <div class="blooms {{ $theme->on('batch_numerals') ? '' : 'no-batch' }}" style="--batch-label: '{{ str_replace(['\\', '\''], '', $theme->label('batch_numerals')) }} '">
                    @foreach ($products as $product)
                        @include('themes.wick._card', ['product' => $product, 'badge' => null, 'gvDelay' => ($loop->index % 3) * 0.09])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif

            @if (! $isFiltered)
                {{-- Editorial ticket — the manifesto, framed two-up. --}}
                @if ($theme->on('explain'))
                    <section class="explain" data-gv-reveal>
                        <div class="art" style="overflow: hidden;">
                            @if ($explainImg = $theme->image('explain_image'))
                                <img src="{{ $explainImg }}" alt="" loading="lazy" data-gv-parallax="0.09" style="width: 100%; height: 112%; object-fit: cover; display: block;">
                            @else
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

                {{-- Discovery set — curated sampler on a surface panel. --}}
                @if ($products->isNotEmpty() && $theme->on('discovery_case'))
                    <section class="case reveal">
                        <div>
                            <div class="k">{{ __('site.storefront.featured.eyebrow') }}</div>
                            <h3>{{ __('site.storefront.featured.h2') }}</h3>
                            <p>{{ $theme->copy('case_body') }}</p>
                            <a class="btn" href="#shop">{{ __('site.storefront.featured.browse_all') }}</a>
                        </div>
                        <div class="grid6" aria-hidden="true">
                            @for ($i = 0; $i < 6; $i++)
                                @php
                                    $cp = $caseSix[$i % max(1, $caseSix->count())] ?? null;
                                    $cu = $cp && $cp->image_path ? \Illuminate\Support\Facades\Storage::url($cp->image_path) : null;
                                @endphp
                                <div class="b {{ $cu ? '' : 'jar' }}">@if ($cu)<img src="{{ $cu }}" alt="">@else<span class="jar-mark"></span>@endif</div>
                            @endfor
                        </div>
                    </section>
                @endif

                {{-- The dropping list — newsletter. --}}
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
