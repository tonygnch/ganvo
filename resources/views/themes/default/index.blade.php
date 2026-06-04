@extends('themes.default.layout')

@section('content')
    @php
        // Marketing chrome only on the unfiltered landing — once shoppers start
        // searching or paginating, drop everything except the catalog grid.
        $isFiltered = ($filters['q'] ?? null)
            || ($filters['category'] ?? null)
            || ($filters['min_price'] ?? null) !== null
            || ($filters['max_price'] ?? null) !== null
            || ($filters['in_stock'] ?? false)
            || (($filters['sort'] ?? 'newest') !== 'newest')
            || $products->currentPage() > 1;

        $featured = $products->take(8);
        $heroProduct = $featured->first();

        // Merchant-supplied hero overrides the editorial backdrop.
        $csHero = $store->heroBanner();
        $heroImageUrl = $csHero['enabled'] && $csHero['image_path']
            ? \Illuminate\Support\Facades\Storage::url($csHero['image_path'])
            : ($heroProduct && $heroProduct->image_path
                ? \Illuminate\Support\Facades\Storage::url($heroProduct->image_path)
                : null);

        // Lookbook rail = the next handful of products with imagery.
        $lookbook = $featured->skip(1)->take(5)->values();

        // Editorial film-block image = a later product shot.
        $filmProduct = $featured->skip(3)->first();
        $filmImageUrl = $filmProduct && $filmProduct->image_path
            ? \Illuminate\Support\Facades\Storage::url($filmProduct->image_path)
            : null;
    @endphp

    <style>
        /* ===== HOME ===== */
        /* hero */
        .hero { position: relative; height: 78vh; min-height: 560px; max-height: 760px; overflow: hidden; background: var(--ink); }
        .hero .bg { position: absolute; inset: 0; }
        .hero .bg img { width: 100%; height: 100%; object-fit: cover; }
        .hero .scrim { position: absolute; inset: 0; background: linear-gradient(to top, rgba(8,8,8,.62), rgba(8,8,8,.15) 55%, transparent); }
        .hero .inner { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: flex-end; padding: 0 36px 7vh; max-width: 1320px; margin: 0 auto; left: 0; right: 0; color: var(--paper); }
        .hero .eyebrow { display: flex; gap: 14px; align-items: center; margin-bottom: 22px; }
        .hero .eyebrow .ln { width: 54px; height: 1px; background: var(--accent); }
        .hero h1 { font-family: var(--display); font-weight: 800; text-transform: uppercase; font-size: clamp(40px, 7vw, 118px); line-height: .86; letter-spacing: -.02em; }
        .hero h1 .rot { color: var(--accent); }
        .hero .sub { display: flex; gap: 26px; align-items: flex-end; justify-content: space-between; margin-top: 30px; flex-wrap: wrap; }
        .hero .sub p { max-width: 40ch; font-size: 15px; opacity: .85; }
        .hero .sub .cta { display: flex; gap: 12px; flex-wrap: wrap; }

        /* brand marquee */
        .brandmarq { border-top: 1px solid var(--ink); border-bottom: 1px solid var(--ink); overflow: hidden; background: var(--paper); }
        .brandmarq .track { display: flex; align-items: center; gap: 46px; white-space: nowrap; animation: bm 28s linear infinite; padding: 14px 0; }
        .brandmarq .track span { font-family: var(--display); font-weight: 800; text-transform: uppercase; font-size: clamp(34px, 6vw, 84px); letter-spacing: -.02em; }
        .brandmarq .track .o { -webkit-text-stroke: 1.5px var(--ink); color: transparent; }
        .brandmarq .track .star { color: var(--accent); font-size: clamp(22px, 4vw, 48px); }
        @keyframes bm { to { transform: translateX(-50%); } }

        /* lookbook rail */
        .rail-head { display: flex; align-items: flex-end; justify-content: space-between; padding-top: 64px; padding-bottom: 24px; }
        .rail-head h2 { font-family: var(--display); font-weight: 700; text-transform: uppercase; font-size: clamp(24px, 3.4vw, 42px); letter-spacing: -.01em; }
        .rail-head .hint { font-size: 11px; letter-spacing: .16em; text-transform: uppercase; color: var(--muted); }
        .rail { display: flex; gap: 18px; overflow-x: auto; padding: 0 36px 18px; scrollbar-width: none; }
        .rail::-webkit-scrollbar { display: none; }
        .look { flex: 0 0 clamp(260px, 34vw, 460px); }
        .look .img { height: 56vh; min-height: 380px; max-height: 520px; margin-bottom: 14px; overflow: hidden; }
        .look .img img { width: 100%; height: 100%; object-fit: cover; transition: transform .8s cubic-bezier(.19,.7,.16,1); }
        .look:hover .img img { transform: scale(1.05); }
        .look .cap { display: flex; justify-content: space-between; border-top: 1px solid var(--rule); padding-top: 10px; gap: 12px; }
        .look .cap .t { font-family: var(--serif); font-size: 20px; }
        .look .cap .n { font-family: var(--body); font-size: 13px; color: var(--muted); white-space: nowrap; }

        /* product index */
        .idx-head { display: flex; align-items: baseline; justify-content: space-between; border-bottom: 1px solid var(--ink); padding-bottom: 14px; margin: 80px 0 30px; gap: 16px; flex-wrap: wrap; }
        .idx-head h2 { font-family: var(--display); font-weight: 700; text-transform: uppercase; font-size: clamp(22px, 3vw, 36px); letter-spacing: -.01em; }
        .idx-head a { font-size: 11px; letter-spacing: .16em; text-transform: uppercase; color: var(--muted); }
        .idx-head a:hover { color: var(--ink); }

        /* editorial dark split */
        .filmblock { position: relative; margin: 100px 0; background: var(--ink); color: var(--paper); display: grid; grid-template-columns: 1fr 1fr; align-items: stretch; overflow: hidden; }
        .filmblock .art { min-height: 480px; }
        .filmblock .art img { width: 100%; height: 100%; object-fit: cover; }
        .filmblock .txt { padding: 72px 60px; display: flex; flex-direction: column; justify-content: center; }
        .filmblock .txt .kicker { color: var(--accent); }
        .filmblock .txt h3 { font-family: var(--serif); font-size: clamp(30px, 4.2vw, 56px); line-height: 1.02; margin: 18px 0 18px; }
        .filmblock .txt p { opacity: .82; max-width: 42ch; margin-bottom: 26px; }
        .filmblock .btn.ghost { color: var(--paper); border-color: var(--paper); }
        .filmblock .btn.ghost:hover { background: var(--paper); color: var(--ink); }

        /* colophon / newsletter */
        .colophon { display: grid; grid-template-columns: 1.2fr 1fr; gap: 50px; margin: 90px 0; align-items: end; }
        .colophon h3 { font-family: var(--serif); font-size: clamp(30px, 4vw, 52px); line-height: 1; }
        .colophon .sub { display: flex; border-bottom: 1px solid var(--ink); margin-top: 22px; }
        .colophon input { flex: 1; border: none; background: none; padding: 14px 2px; font-family: inherit; font-size: 15px; color: var(--ink); }
        .colophon input:focus { outline: none; }
        .colophon button { background: none; border: none; font-size: 11px; letter-spacing: .18em; text-transform: uppercase; font-weight: 600; color: var(--ink); }

        .home-empty { text-align: center; padding: 80px 20px; color: var(--muted); border: 1px solid var(--ink); }
        .home-empty p { font-size: 13px; letter-spacing: .14em; text-transform: uppercase; }

        @media (max-width: 1080px) {
            .filmblock { grid-template-columns: 1fr; }
            .filmblock .art { min-height: 320px; }
            .colophon { grid-template-columns: 1fr; gap: 24px; }
        }
        @media (max-width: 680px) {
            .hero { height: 70vh; min-height: 460px; }
            .rail { padding: 0 20px 18px; }
            .filmblock .txt { padding: 48px 28px; }
        }
    </style>

    <main>
        @if (! $isFiltered)
            {{-- ===== HERO ===== --}}
            <section class="hero">
                <div class="bg ph dark">
                    @if ($heroImageUrl)
                        <img src="{{ $heroImageUrl }}" alt="{{ $csHero['title'] !== '' ? $csHero['title'] : $tenant->name }}">
                    @endif
                </div>
                <div class="scrim"></div>
                <div class="inner">
                    <div class="eyebrow">
                        <span class="ln"></span>
                        <span class="kicker">{{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</span>
                    </div>
                    <h1>{!! $csHero['subtitle'] !== '' ? e($csHero['subtitle']) : __('site.storefront.hero.headline', ['tenant' => e($tenant->name)]) !!}</h1>
                    <div class="sub">
                        <p>{{ __('site.storefront.hero.sub') }}</p>
                        <div class="cta">
                            <a class="btn red" href="#shop">{{ $csHero['cta_label'] !== '' ? $csHero['cta_label'] : __('site.storefront.hero.cta_primary') }} <span class="arc">→</span></a>
                            <a class="btn ghost" href="#featured" style="color:var(--paper)">{{ __('site.storefront.hero.cta_secondary') }}</a>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===== BRAND MARQUEE ===== --}}
            <div class="brandmarq"><div class="track" id="bmTrack"></div></div>

            {{-- ===== LOOKBOOK RAIL ===== --}}
            @if ($lookbook->isNotEmpty())
                <div class="rail-head wrap">
                    <h2>{{ __('site.storefront.lookbook.title') }}</h2>
                    <span class="hint">{{ __('site.storefront.lookbook.hint') }}</span>
                </div>
                <div class="rail" id="rail">
                    @foreach ($lookbook as $i => $lp)
                        <a class="look" href="/products/{{ $lp->slug }}">
                            <div class="img ph dark">
                                @if ($lp->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($lp->image_path) }}" alt="{{ $lp->name }}">
                                @else
                                    <span>{{ $lp->name }}</span>
                                @endif
                            </div>
                            <div class="cap">
                                <span class="t ital">{{ $lp->name }}</span>
                                <span class="n">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- ===== NEW IN GRID ===== --}}
            @if ($featured->isNotEmpty())
                <div class="wrap">
                    <div class="idx-head rv" id="featured">
                        <h2>{{ __('site.storefront.featured.h2') }}</h2>
                        <a href="#shop">{{ __('site.storefront.featured.browse_all') }} →</a>
                    </div>
                    <div class="pgrid">
                        @foreach ($featured->take(4) as $i => $product)
                            @include('themes.default._card', ['product' => $product, 'badge' => $i === 0 ? __('site.storefront.featured.badge') : null])
                        @endforeach
                    </div>
                </div>

                {{-- ===== EDITORIAL FILM BLOCK ===== --}}
                <section class="filmblock rv">
                    <div class="art ph dark">
                        @if ($filmImageUrl)
                            <img src="{{ $filmImageUrl }}" alt="">
                        @else
                            <span>editorial</span>
                        @endif
                    </div>
                    <div class="txt">
                        <div class="kicker">{{ __('site.storefront.featured.eyebrow') }}</div>
                        <h3>{{ __('site.storefront.promo.h2_prefix', ['tenant' => $tenant->name]) }}</h3>
                        <p>{{ __('site.storefront.promo.p') }}</p>
                        <a class="btn ghost" href="#shop">{{ __('site.storefront.promo.btn') }}</a>
                    </div>
                </section>
            @endif
        @endif

        {{-- ===== COLLECTION STRIPS (featured) ===== --}}
        <div class="wrap">
            @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
                <div class="idx-head rv">
                    <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
                </div>
                @include('storefront.partials.collection-strips')
            @endif

            {{-- ===== SHOP ALL (filterable catalog) ===== --}}
            <div class="idx-head rv" id="shop">
                <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
            </div>

            @include('storefront.partials.catalog-controls')

            @if ($products->isEmpty())
                <div class="home-empty rv">
                    <p>{{ __('site.storefront.no_products') }}</p>
                </div>
            @else
                <div class="pgrid">
                    @foreach ($products as $product)
                        @include('themes.default._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>

                @include('storefront.partials.pagination')
            @endif

            {{-- ===== NEWSLETTER COLOPHON ===== --}}
            @if (! $isFiltered)
                <section class="colophon rv">
                    <div>
                        <div class="kicker" style="color:var(--accent)">{{ __('site.storefront.featured.eyebrow') }}</div>
                        <h3>{{ __('site.storefront.footer.subscribe') }}</h3>
                    </div>
                    <div>
                        <p style="color:var(--muted)">{{ __('site.storefront.footer.tagline') }}</p>
                        <form class="sub" data-subscribed-label="{{ __('site.storefront.footer.subscribed') }}"
                              onsubmit="event.preventDefault(); this.querySelector('input').value=''; this.querySelector('button').textContent=this.dataset.subscribedLabel;">
                            <input type="email" placeholder="{{ __('site.storefront.footer.newsletter_placeholder') }}" required>
                            <button type="submit">{{ __('site.storefront.footer.subscribe') }} →</button>
                        </form>
                    </div>
                </section>
            @endif
        </div>
    </main>

    @if (! $isFiltered)
        <script>
            // Brand marquee — duplicated content for a seamless loop.
            (function () {
                var track = document.getElementById('bmTrack');
                if (! track) return;
                var name = @json($tenant->name);
                var unit = '<span>' + name + '</span><span class="star">✶</span>' +
                           '<span class="o">' + @json(__('site.storefront.hero.eyebrow', ['year' => date('Y')])) + '</span><span class="star">✶</span>';
                track.innerHTML = unit.repeat(4);
            })();
        </script>
    @endif
@endsection
