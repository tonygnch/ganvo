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

        $featured = $products->take(8);
        $heroProduct = $featured->first();

        $csHero = $store->heroBanner();
        $heroImageUrl = $csHero['enabled'] && $csHero['image_path']
            ? \Illuminate\Support\Facades\Storage::url($csHero['image_path'])
            : ($heroProduct && $heroProduct->image_path
                ? \Illuminate\Support\Facades\Storage::url($heroProduct->image_path)
                : null);

        $dropProduct = $featured->skip(1)->first();
        $dropImageUrl = $dropProduct && $dropProduct->image_path
            ? \Illuminate\Support\Facades\Storage::url($dropProduct->image_path)
            : null;
    @endphp

    <style>
        /* ===== HERO — split block, big type + product image ===== */
        .hero { display: grid; grid-template-columns: 1.1fr .9fr; gap: 0; border: 2.5px solid var(--ink); box-shadow: var(--pop-lg); margin: 28px 0 0; background: var(--paper); }
        .hero .copy { padding: 48px 44px; display: flex; flex-direction: column; justify-content: center; border-right: 2.5px solid var(--ink); }
        .hero .eyebrow { display: inline-flex; align-self: flex-start; background: var(--ink); color: var(--accent); font-family: var(--display); font-weight: 800; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; padding: 6px 12px; margin-bottom: 22px; }
        .hero h1 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(40px, 6vw, 92px); line-height: .9; letter-spacing: -.03em; }
        .hero h1 .hl { background: var(--accent); padding: 0 .12em; box-decoration-break: clone; -webkit-box-decoration-break: clone; }
        .hero p { font-size: 16px; max-width: 42ch; margin: 24px 0 30px; color: var(--text-muted); }
        .hero .cta { display: flex; gap: 14px; flex-wrap: wrap; }
        .hero .vis { position: relative; min-height: 460px; background: var(--soft); }
        .hero .vis img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; }
        .hero .vis .pricetag { position: absolute; right: 18px; bottom: 18px; background: var(--accent); border: 2.5px solid var(--ink); box-shadow: var(--pop); padding: 10px 14px; font-family: var(--display); font-weight: 800; font-size: 14px; text-transform: uppercase; }

        /* marquee strip of selling points */
        .ticker { display: flex; flex-wrap: wrap; gap: 0; border: 2.5px solid var(--ink); border-top: none; }
        .ticker .t { flex: 1 1 0; min-width: 180px; padding: 16px 20px; border-right: 2.5px solid var(--ink); font-family: var(--display); font-weight: 700; font-size: 12px; text-transform: uppercase; display: flex; align-items: center; gap: 10px; }
        .ticker .t:last-child { border-right: none; }
        .ticker .t .num { background: var(--accent); border: 2.5px solid var(--ink); width: 26px; height: 26px; display: grid; place-items: center; font-size: 12px; flex-shrink: 0; }

        /* drop banner — big offset block */
        .drop { display: grid; grid-template-columns: 1fr 1fr; margin: 72px 0; border: 2.5px solid var(--ink); box-shadow: var(--pop-lg); background: var(--ink); color: var(--paper); }
        .drop .vis { position: relative; min-height: 380px; border-right: 2.5px solid var(--ink); background: var(--soft2); }
        .drop .vis img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; }
        .drop .txt { padding: 52px 44px; display: flex; flex-direction: column; justify-content: center; }
        .drop .txt .k { font-family: var(--display); font-weight: 800; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: var(--accent); margin-bottom: 16px; }
        .drop .txt h3 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(30px, 4vw, 56px); line-height: .92; letter-spacing: -.02em; margin-bottom: 18px; }
        .drop .txt p { color: rgba(253,251,240,.8); max-width: 40ch; margin-bottom: 28px; }

        .home-empty { border: 2.5px solid var(--ink); box-shadow: var(--pop); padding: 60px 24px; text-align: center; font-family: var(--display); font-weight: 800; text-transform: uppercase; }

        @media (max-width: 900px) {
            .hero, .drop { grid-template-columns: 1fr; }
            .hero .copy { border-right: none; border-bottom: 2.5px solid var(--ink); }
            .hero .vis { min-height: 320px; }
            .drop .vis { border-right: none; border-bottom: 2.5px solid var(--ink); min-height: 280px; }
            .ticker .t { flex-basis: 50%; border-bottom: 2.5px solid var(--ink); }
        }
        @media (max-width: 540px) {
            /* Stop the 40px clamp floor from overflowing the narrow hero. */
            .hero h1 { font-size: clamp(28px, 7vw, 40px); }
            .hero .copy { padding: 32px 22px; }
            .drop .txt { padding: 32px 20px; }
            .drop .txt h3 { font-size: clamp(24px, 5vw, 30px); }
        }
    </style>

    <main>
        <div class="wrap">
            @if (! $isFiltered)
                {{-- ===== HERO ===== --}}
                <section class="hero">
                    <div class="copy">
                        <span class="eyebrow">{{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</span>
                        <h1>@if ($csHero['subtitle'] !== ''){{ $csHero['subtitle'] }}@else{!! __('site.storefront.hero.headline', ['tenant' => '<span class="hl">' . e($tenant->name) . '</span>']) !!}@endif</h1>
                        <p>{{ __('site.storefront.hero.sub') }}</p>
                        <div class="cta">
                            <a class="btn accent" href="#shop">{{ $csHero['cta_label'] !== '' ? $csHero['cta_label'] : __('site.storefront.hero.cta_primary') }} <span class="arc">→</span></a>
                            <a class="btn" href="#featured">{{ __('site.storefront.hero.cta_secondary') }}</a>
                        </div>
                    </div>
                    <div class="vis ph">
                        @if ($heroImageUrl)
                            <img src="{{ $heroImageUrl }}" alt="{{ $csHero['title'] !== '' ? $csHero['title'] : $tenant->name }}">
                        @else
                            <span>{{ $tenant->name }}</span>
                        @endif
                        @if ($heroProduct)
                            <span class="pricetag">@money($heroProduct->price_cents)</span>
                        @endif
                    </div>
                </section>

                {{-- ===== SELLING POINTS ===== --}}
                <div class="ticker">
                    <div class="t"><span class="num">01</span>{{ __('site.storefront.value_props.shipping_title') }}</div>
                    <div class="t"><span class="num">02</span>{{ __('site.storefront.value_props.returns_title') }}</div>
                    <div class="t"><span class="num">03</span>{{ __('site.storefront.value_props.checkout_title') }}</div>
                </div>

                {{-- ===== FEATURED GRID ===== --}}
                @if ($featured->isNotEmpty())
                    <div class="sec-head rv" id="featured">
                        <h2>{{ __('site.storefront.featured.h2') }}</h2>
                        <a href="#shop">{{ __('site.storefront.featured.browse_all') }} →</a>
                    </div>
                    <div class="pgrid">
                        @foreach ($featured->take(4) as $i => $product)
                            @include('themes.brick._card', ['product' => $product, 'badge' => $i === 0 ? __('site.storefront.featured.badge') : null])
                        @endforeach
                    </div>

                    {{-- ===== DROP BANNER ===== --}}
                    <section class="drop rv">
                        <div class="vis ph">
                            @if ($dropImageUrl)
                                <img src="{{ $dropImageUrl }}" alt="">
                            @else
                                <span>drop</span>
                            @endif
                        </div>
                        <div class="txt">
                            <div class="k">{{ __('site.storefront.featured.eyebrow') }}</div>
                            <h3>{{ __('site.storefront.promo.h2_prefix', ['tenant' => $tenant->name]) }}</h3>
                            <p>{{ __('site.storefront.promo.p') }}</p>
                            <a class="btn accent" href="#shop" style="align-self:flex-start">{{ __('site.storefront.promo.btn') }}</a>
                        </div>
                    </section>
                @endif
            @endif

            {{-- ===== COLLECTION STRIPS ===== --}}
            @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
                <div class="sec-head rv">
                    <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
                </div>
                @include('storefront.partials.collection-strips')
            @endif

            {{-- ===== SHOP ALL ===== --}}
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
