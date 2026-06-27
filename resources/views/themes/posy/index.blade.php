@extends('themes.posy.layout')

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
        // Up to four products feather the collage around the lead.
        $collage = $products->take(4)->values();
    @endphp

    <style>
        /* ===== HERO — collage ===== */
        .hero { position: relative; padding: 48px 0 70px; }
        .hero .lead { position: relative; z-index: 3; max-width: 620px; margin: 0 auto; text-align: center; padding-top: 20px; }
        .hero .lead h1 { font-family: var(--display); font-size: clamp(50px, 8vw, 104px); line-height: .96; margin: 18px 0 18px; font-weight: 400; }
        .hero .lead h1 em { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .hero .lead p { color: var(--muted); font-size: 17px; max-width: 42ch; margin: 0 auto 28px; }
        .hero .lead .cta { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
        .collage { position: relative; height: 0; }
        .citem { position: absolute; border-radius: 6px; box-shadow: 0 20px 44px -22px rgba(40, 50, 31, .5); background: var(--card); padding: 10px 10px 14px; }
        .citem .pic { border-radius: 3px; overflow: hidden; }
        .citem .pic img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .citem .cap { font-family: var(--serif); font-style: italic; font-size: 15px; text-align: center; margin-top: 8px; min-height: 1.2em; }
        .c1 { width: 190px; left: 2%; top: 30px; transform: rotate(-7deg); } .c1 .pic { height: 200px; }
        .c2 { width: 160px; right: 3%; top: 0; transform: rotate(6deg); } .c2 .pic { height: 170px; }
        .c3 { width: 150px; left: 7%; bottom: -150px; transform: rotate(5deg); } .c3 .pic { height: 160px; }
        .c4 { width: 172px; right: 6%; bottom: -180px; transform: rotate(-6deg); } .c4 .pic { height: 182px; }
        .cdot { position: absolute; border-radius: 50%; }
        .cd1 { width: 80px; height: 80px; background: var(--bloom); right: 20%; top: 24px; animation: floaty 7s ease-in-out infinite; }
        .cd2 { width: 54px; height: 54px; background: var(--leaf); left: 24%; bottom: -90px; animation: floaty 6s ease-in-out infinite .6s; }
        @keyframes floaty { 0%, 100% { transform: translateY(0) rotate(0); } 50% { transform: translateY(-14px) rotate(4deg); } }

        /* seasonal value-props strip */
        .seasonal { display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; padding: 30px 0; margin-top: 30px; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); font-family: var(--serif); font-style: italic; font-size: 19px; color: var(--muted); }
        .seasonal b { font-family: var(--body); font-style: normal; font-weight: 600; color: var(--ink); }

        .home-empty { text-align: center; padding: 70px 24px; font-family: var(--serif); font-style: italic; font-size: 22px; color: var(--muted); }

        @media (max-width: 1000px) { .collage { display: none; } }

        /* category pills — replaces the generic search/sort filter toolbar */
        .pills { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin: 28px 0 42px; }
        .pills .pill { border: 1px solid var(--line); background: var(--card); border-radius: 99px; padding: 10px 22px; font-size: 13px; font-weight: 600; color: var(--ink); transition: background-color .2s ease, color .2s ease, border-color .2s ease; }
        .pills .pill.on, .pills .pill:hover { background: var(--accent); border-color: var(--accent); color: #fbfcf5; }

        /* ===== Collection rails — soft Posy restyle of the shared .cs-* partial.
           Each featured collection becomes a soft-sage panel with cream polaroid
           cards. Selectors are `.wrap `-prefixed to beat the partial's own
           later-in-source `.cs-*` rules. ===== */
        /* margin-top separates the first rail from the seasonal strip above
           (the old section heading used to provide this gap); the same top
           margin spaces the rails from each other via collapse. */
        .wrap .cs-strip { position: relative; margin: 64px 0 0; padding: 40px 36px; border-radius: 24px; background: var(--soft); overflow: hidden; }
        /* Drop the partial's full-bleed banner backdrop — as a faint ghost behind
           the cards it just muddies the soft panel. The cream cards (with their
           own flower photos) carry the imagery. */
        .wrap .cs-banner { display: none; }
        .wrap .cs-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin: 0 0 26px; flex-wrap: wrap; }
        .wrap .cs-head h2 { font-family: var(--display); font-weight: 400; font-size: clamp(26px, 3vw, 40px); line-height: 1.05; }
        .wrap .cs-head p { color: var(--muted); font-size: 14px; max-width: 50ch; margin-top: 4px; }
        .wrap .cs-view-all { font-size: 13px; font-weight: 600; color: var(--accent); border: 1px solid var(--accent); border-radius: 99px; padding: 10px 20px; background: var(--card); white-space: nowrap; transition: background-color .2s ease, color .2s ease; }
        .wrap .cs-view-all:hover { background: var(--accent); color: #fbfcf5; }
        .wrap .cs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); gap: 22px; }
        .wrap .cs-card { display: flex; flex-direction: column; color: inherit; background: var(--card); border-radius: 6px; padding: 10px 10px 16px; box-shadow: 0 14px 32px -22px rgba(40, 50, 31, .4); transition: transform .3s cubic-bezier(.19, .7, .16, 1), box-shadow .3s ease; }
        .wrap .cs-card:hover { transform: translateY(-5px); box-shadow: 0 26px 48px -26px rgba(40, 50, 31, .5); }
        .wrap .cs-img { aspect-ratio: 1 / 1; border-radius: 3px; overflow: hidden; background: var(--bloom); margin-bottom: 12px; }
        .wrap .cs-card:nth-child(even) .cs-img { background: var(--leaf); }
        .wrap .cs-img img { width: 100%; height: 100%; object-fit: cover; }
        .wrap .cs-meta { display: flex; flex-direction: column; align-items: center; gap: 3px; text-align: center; padding: 0; }
        .wrap .cs-name { font-family: var(--display); font-weight: 400; font-size: 17px; line-height: 1.2; }
        .wrap .cs-price { font-family: var(--body); font-weight: 600; font-size: 18px; font-variant-numeric: tabular-nums; color: var(--accent); }
        @media (prefers-reduced-motion: reduce) { .wrap .cs-card, .wrap .cs-card:hover { transform: none; } }
        @media (max-width: 540px) { .wrap .cs-strip { padding: 28px 22px; } .wrap .cs-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>

    <main>
        @if (! $isFiltered)
            <div class="wrap">
                <section class="hero">
                    <div class="collage" aria-hidden="true">
                        @foreach (['c1', 'c2', 'c3', 'c4'] as $i => $cls)
                            @php
                                $cp = $collage[$i] ?? null;
                                $cu = $cp && $cp->image_path ? \Illuminate\Support\Facades\Storage::url($cp->image_path) : null;
                                if ($i === 0 && $heroImageUrl) { $cu = $heroImageUrl; }
                            @endphp
                            <div class="citem {{ $cls }} reveal s{{ min($i, 3) }}">
                                <div class="tape {{ $i % 2 ? 'r' : '' }}"></div>
                                <div class="pic {{ $cu ? '' : ($i % 2 ? 'ph' : 'bloomph') }}">@if ($cu)<img src="{{ $cu }}" alt="">@endif</div>
                                <div class="cap">{{ $cp->name ?? '' }}</div>
                            </div>
                        @endforeach
                        <div class="cdot cd1 reveal s1"></div><div class="cdot cd2 reveal s3"></div>
                    </div>
                    <div class="lead">
                        <div class="kicker reveal">{{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</div>
                        <h1 class="reveal s1">@if ($csHero['subtitle'] !== ''){{ $csHero['subtitle'] }}@else{!! __('site.storefront.hero.headline', ['tenant' => '<em>' . e($tenant->name) . '</em>']) !!}@endif</h1>
                        <p class="reveal s2">{{ __('site.storefront.hero.sub') }}</p>
                        <div class="cta reveal s2">
                            <a class="btn" href="#shop">{{ $csHero['cta_label'] !== '' ? $csHero['cta_label'] : __('site.storefront.hero.cta_primary') }}</a>
                            <a class="btn outline" href="#shop">{{ __('site.storefront.hero.cta_secondary') }}</a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="seasonal">
                <span><b>{{ __('site.storefront.value_props.shipping_title') }}</b></span>
                <span><b>{{ __('site.storefront.value_props.returns_title') }}</b></span>
                <span><b>{{ __('site.storefront.value_props.checkout_title') }}</b></span>
            </div>
        @endif

        <div class="wrap">
            {{-- Curated collections (only when the merchant features them) --}}
            @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
                {{-- Each rail self-titles, so no extra section heading here. --}}
                @include('storefront.partials.collection-strips')
            @endif

            {{-- Shop all — the catalog --}}
            <div class="sec-head reveal" id="shop">
                <span class="kicker">{{ __('site.storefront.shop_all.eyebrow') }}</span>
                <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
            </div>

            {{-- Posy category pills (replaces the generic search/sort/price toolbar). --}}
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
                        @include('themes.posy._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
