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
        $sideOne     = $featured->skip(1)->first();
        $sideTwo     = $featured->skip(2)->first();

        // Merchant-supplied hero overrides the editorial product images.
        $csHero = $store->heroBanner();
        $heroImageUrl = $csHero['enabled'] && $csHero['image_path']
            ? \Illuminate\Support\Facades\Storage::url($csHero['image_path'])
            : ($heroProduct && $heroProduct->image_path
                ? \Illuminate\Support\Facades\Storage::url($heroProduct->image_path)
                : null);

        // Top 3 categories show as the lower category tiles. Pulled in priority
        // order — merchant controls via sort_order in the Categories admin.
        $topCategories = ($categories ?? collect())
            ->where('parent_id', null)
            ->sortBy('sort_order')
            ->take(3)
            ->values();
    @endphp

    <style>
        /* ===== HERO ===== */
        .hero {
            display: grid;
            grid-template-columns: 1.25fr 1fr;
            gap: 18px;
            padding: 18px 0 0;
        }
        .hero .main {
            position: relative;
            height: 74vh;
            min-height: 520px;
            overflow: hidden;
        }
        /* Gradient overlay so the caption reads cleanly against ANY photo —
           the original template's mix-blend-mode trick only worked on quiet,
           single-subject lookbook shots. */
        .hero .main::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(0, 0, 0, .55) 0%,
                rgba(0, 0, 0, .28) 35%,
                rgba(0, 0, 0, 0) 65%
            );
            pointer-events: none;
            z-index: 1;
        }
        .hero .main .cap {
            position: absolute;
            left: 48px;
            right: 48px;
            bottom: 46px;
            color: #fff;
            z-index: 2;
            text-shadow: 0 2px 24px rgba(0, 0, 0, .35);
        }
        .hero .main .cap h1 {
            font-family: var(--display);
            font-size: clamp(54px, 7vw, 104px);
            line-height: .9;
            font-weight: 500;
            color: #fff;
        }
        .hero .main .cap .sub {
            letter-spacing: .2em;
            text-transform: uppercase;
            font-size: 12px;
            margin-bottom: 16px;
            color: rgba(255, 255, 255, .9);
        }
        .hero .side {
            display: grid;
            grid-template-rows: 1fr 1fr;
            gap: 18px;
        }
        .hero-cta {
            padding: 30px 0 0;
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        /* editorial */
        .editorial {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            margin-top: 100px;
            align-items: stretch;
        }
        .editorial .img { min-height: 560px; }
        .editorial .txt {
            background: var(--ink);
            color: var(--paper);
            padding: 72px 64px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .editorial .txt .k {
            letter-spacing: .2em;
            text-transform: uppercase;
            font-size: 11px;
            color: #b3aa9a;
        }
        .editorial .txt h3 {
            font-family: var(--display);
            font-size: clamp(34px, 4vw, 56px);
            font-weight: 500;
            margin: 18px 0 18px;
            line-height: 1.02;
        }
        .editorial .txt p {
            color: #cfc7b8;
            max-width: 42ch;
            margin-bottom: 30px;
        }
        .editorial .btn.outline {
            color: var(--paper);
            border-color: var(--paper);
            align-self: flex-start;
        }
        .editorial .btn.outline:hover { background: var(--paper); color: var(--ink); }

        /* category tiles */
        .cats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-top: 100px;
        }
        .cat {
            position: relative;
            height: 440px;
            cursor: pointer;
            overflow: hidden;
        }
        .cat .img {
            position: absolute;
            inset: 0;
            transition: transform .55s cubic-bezier(.19,.7,.16,1);
        }
        .cat::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(0, 0, 0, .55) 0%,
                rgba(0, 0, 0, .15) 50%,
                rgba(0, 0, 0, 0) 80%
            );
            pointer-events: none;
            z-index: 1;
            transition: opacity .35s ease;
        }
        .cat:hover::after { opacity: .85; }
        .cat:hover .img { transform: scale(1.04); }
        .cat .lab {
            position: absolute;
            left: 26px;
            right: 26px;
            bottom: 26px;
            color: #fff;
            font-family: var(--display);
            font-size: 30px;
            z-index: 2;
            text-shadow: 0 2px 16px rgba(0, 0, 0, .35);
        }

        /* newsletter */
        .news {
            text-align: center;
            margin: 110px 0;
            padding: 0 20px;
        }
        .news h3 {
            font-family: var(--display);
            font-size: clamp(32px, 4vw, 52px);
            font-weight: 500;
        }
        .news p {
            color: var(--muted);
            margin: 14px 0 28px;
        }
        .news form {
            display: flex;
            max-width: 460px;
            margin: 0 auto;
            border-bottom: 1px solid var(--ink);
        }
        .news input {
            flex: 1;
            border: none;
            background: none;
            padding: 14px 4px;
            font-family: inherit;
            font-size: 15px;
            color: var(--ink);
        }
        .news input:focus { outline: none; }
        .news button {
            background: none;
            border: none;
            letter-spacing: .14em;
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 600;
            color: var(--ink);
        }

        @media (max-width: 1000px) {
            .editorial { grid-template-columns: 1fr; }
            .editorial .img { min-height: 340px; }
            .editorial .txt { padding: 56px 32px; }
            .cats { grid-template-columns: 1fr; }
            .hero { grid-template-columns: 1fr; }
            .hero .side { display: none; }
            .hero .main { height: 64vh; }
        }
    </style>

    <main>
        <div class="wrap">
            @if (! $isFiltered)
                <section class="hero rv">
                    <div class="main ph">
                        @if ($heroImageUrl)
                            <img src="{{ $heroImageUrl }}" alt="{{ $csHero['title'] ?? $tenant->name }}">
                        @else
                            <span>{{ __('site.storefront.product.no_image') }}</span>
                        @endif
                        <div class="cap">
                            <div class="sub">{{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</div>
                            <h1>{{ $csHero['subtitle'] !== '' ? $csHero['subtitle'] : __('site.storefront.hero.headline', ['tenant' => $tenant->name]) }}</h1>
                        </div>
                    </div>
                    <div class="side">
                        <div class="ph">
                            @if ($sideOne && $sideOne->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($sideOne->image_path) }}" alt="{{ $sideOne->name }}">
                            @else
                                <span>editorial</span>
                            @endif
                        </div>
                        <div class="ph">
                            @if ($sideTwo && $sideTwo->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($sideTwo->image_path) }}" alt="{{ $sideTwo->name }}">
                            @else
                                <span>editorial</span>
                            @endif
                        </div>
                    </div>
                </section>

                <div class="hero-cta rv">
                    <a class="btn" href="#shop">
                        {{ $csHero['cta_label'] !== '' ? $csHero['cta_label'] : __('site.storefront.hero.cta_primary') }}
                    </a>
                    <a class="btn outline" href="#featured">{{ __('site.storefront.hero.cta_secondary') }}</a>
                </div>

                @if ($featured->isNotEmpty())
                    <div class="sec-head rv" id="featured">
                        <h2>{{ __('site.storefront.featured.h2') }}</h2>
                        <a href="#shop">{{ __('site.storefront.featured.browse_all') }} →</a>
                    </div>
                    <div class="pgrid">
                        @foreach ($featured->take(4) as $i => $product)
                            <a class="pcard rv" href="/products/{{ $product->slug }}">
                                <div class="img ph">
                                    @if ($product->image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
                                    @else
                                        <span>{{ $product->name }}</span>
                                    @endif
                                    @if ($i === 0)
                                        <div class="tag">{{ __('site.storefront.featured.badge') }}</div>
                                    @endif
                                </div>
                                <div class="nm">{{ $product->name }}</div>
                                <div class="pr">@money($product->price_cents)</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        @if (! $isFiltered)
            <section class="editorial rv">
                <div class="img ph">
                    @if ($featured->skip(3)->first() && $featured->skip(3)->first()->image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($featured->skip(3)->first()->image_path) }}" alt="">
                    @else
                        <span>editorial photo</span>
                    @endif
                </div>
                <div class="txt">
                    <div class="k">{{ __('site.storefront.featured.eyebrow') }}</div>
                    <h3>{{ __('site.storefront.promo.h2_prefix', ['tenant' => $tenant->name]) }}</h3>
                    <p>{{ __('site.storefront.promo.p') }}</p>
                    <a class="btn outline" href="#shop">{{ __('site.storefront.promo.btn') }}</a>
                </div>
            </section>

            @if ($topCategories->isNotEmpty())
                <div class="wrap">
                    <div class="cats">
                        @foreach ($topCategories as $cat)
                            <a class="cat rv" href="/categories/{{ $cat->slug }}">
                                <div class="img ph">
                                    @if ($cat->image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($cat->image_path) }}" alt="{{ $cat->name }}">
                                    @else
                                        <span>{{ $cat->name }}</span>
                                    @endif
                                </div>
                                <div class="lab">{{ $cat->name }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        <div class="wrap">
            @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
                <div class="sec-head rv">
                    <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
                </div>
                @include('storefront.partials.collection-strips')
            @endif

            <div class="sec-head rv" id="shop">
                <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
            </div>

            @if ($products->isEmpty())
                <div style="text-align:center; padding: 80px 20px; color: var(--muted); border: 1px solid var(--line);">
                    <p style="font-size: 13px; letter-spacing: .14em; text-transform: uppercase;">{{ __('site.storefront.no_products') }}</p>
                </div>
            @else
                <div class="pgrid">
                    @foreach ($products as $product)
                        <a class="pcard rv" href="/products/{{ $product->slug }}">
                            <div class="img ph">
                                @if ($product->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
                                @else
                                    <span>{{ $product->name }}</span>
                                @endif
                            </div>
                            <div class="nm">{{ $product->name }}</div>
                            <div class="pr">@money($product->price_cents)</div>
                        </a>
                    @endforeach
                </div>

                @include('storefront.partials.pagination')
            @endif

            @if (! $isFiltered)
                <section class="news rv">
                    <h3>{{ __('site.storefront.footer.subscribe') }}</h3>
                    <p>{{ __('site.storefront.footer.tagline') }}</p>
                    <form data-subscribed-label="{{ __('site.storefront.footer.subscribed') }}"
                          onsubmit="event.preventDefault(); this.querySelector('input').value=''; this.querySelector('button').textContent=this.dataset.subscribedLabel;">
                        <input type="email" placeholder="{{ __('site.storefront.footer.newsletter_placeholder') }}" required>
                        <button type="submit">{{ __('site.storefront.footer.subscribe') }}</button>
                    </form>
                </section>
            @endif
        </div>
    </main>
@endsection
