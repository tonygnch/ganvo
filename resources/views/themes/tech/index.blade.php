@extends('themes.tech.layout')

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
        $heroImg = $heroProduct && $heroProduct->image_path ? \Illuminate\Support\Facades\Storage::url($heroProduct->image_path) : null;
        $trending = $featured->take(4);
        $arrivals = $featured->slice(4, 4);
        $csHero = $store->heroBanner();
        $topCategories = ($categories ?? collect())->where('parent_id', null)->sortBy('sort_order')->take(4)->values();
    @endphp

    <style>
        .hero {
            margin-top: 24px; border: 1px solid var(--line); border-radius: 16px; overflow: hidden;
            background: radial-gradient(120% 130% at 78% 8%, #1b2233 0, #0c0e14 60%); position: relative;
            display: grid; grid-template-columns: 1.05fr .95fr; min-height: 420px;
        }
        .hero .cap { padding: 64px; display: flex; flex-direction: column; justify-content: center; position: relative; z-index: 2; }
        .hero .cap h1 { font-family: var(--archivo); font-weight: 800; font-size: clamp(34px,4.2vw,56px); line-height: 1.0; letter-spacing: -.03em; margin: 14px 0; }
        .hero .cap p { color: var(--muted); font-size: 17px; max-width: 40ch; margin-bottom: 30px; }
        .hero .cap .row { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; }
        .hero .visual { position: relative; }
        .hero .visual .prod { position: absolute; inset: 30px 30px 0 0; border-radius: 12px 12px 0 0; overflow: hidden; background: linear-gradient(180deg,#262d3d,#0f121a); border: 1px solid var(--line); border-bottom: none; }
        .hero .visual .prod img { width: 100%; height: 100%; object-fit: cover; }
        .hero .visual .chip { position: absolute; left: 0; bottom: 40px; background: var(--surface); border: 1px solid var(--line); border-radius: 8px; padding: 14px 18px; font-family: var(--mono); font-size: 12px; }
        .hero .visual .chip b { color: var(--accent); }

        .strip { display: flex; justify-content: center; gap: 46px; flex-wrap: wrap; padding: 30px 0; color: var(--faint); font-family: var(--mono); font-size: 12px; letter-spacing: .05em; border-bottom: 1px solid var(--line); }

        .banner { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin: 64px 0; border: 1px solid var(--line); border-radius: 16px; overflow: hidden; }
        .banner .txt { padding: 56px; background: var(--surface); }
        .banner .txt .tag { margin-bottom: 14px; display: block; }
        .banner .txt h3 { font-family: var(--archivo); font-weight: 800; font-size: clamp(30px,3.6vw,46px); letter-spacing: -.02em; line-height: 1.02; margin-bottom: 16px; }
        .banner .txt p { color: var(--muted); max-width: 42ch; margin-bottom: 26px; }
        .banner .vis { position: relative; background: var(--surface2); overflow: hidden; min-height: 260px; }
        .banner .vis img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; }

        .news { border: 1px solid var(--line); border-radius: 16px; padding: 50px; margin: 80px 0; text-align: center; background: radial-gradient(80% 120% at 50% 0,#161a24,#0c0e13); }
        .news h3 { font-family: var(--archivo); font-weight: 800; font-size: clamp(26px,3.2vw,38px); letter-spacing: -.02em; }
        .news p { color: var(--muted); margin: 12px 0 24px; }
        .news form { display: flex; gap: 10px; max-width: 460px; margin: 0 auto; }
        .news input { flex: 1; background: var(--bg); border: 1px solid var(--line); border-radius: 8px; padding: 14px 16px; color: var(--txt); font-family: var(--mono); font-size: 13px; }
        .news input:focus { outline: none; border-color: var(--accent); }

        .cats { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin: 40px 0; }
        .cat { position: relative; height: 160px; border: 1px solid var(--line); border-radius: 12px; overflow: hidden; background: var(--surface2); display: flex; align-items: flex-end; padding: 16px; }
        .cat img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .55; }
        .cat .lab { position: relative; font-family: var(--archivo); font-weight: 800; font-size: 18px; z-index: 1; }

        .empty { border: 1px solid var(--line); border-radius: 12px; padding: 60px; text-align: center; color: var(--muted); font-family: var(--mono); font-size: 13px; }

        @media (max-width: 1000px) {
            .hero, .banner { grid-template-columns: 1fr; }
            .hero .visual { min-height: 240px; }
            .cats { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 720px) { .hero .cap { padding: 40px 28px; } }
    </style>

    <main>
        <div class="wrap">
            @if (! $isFiltered)
                <section class="hero">
                    <div class="cap">
                        <span class="tag rv">// {{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</span>
                        <h1 class="rv">{{ $csHero['subtitle'] !== '' ? $csHero['subtitle'] : __('site.storefront.hero.headline', ['tenant' => $tenant->name]) }}</h1>
                        <p class="rv">{{ __('site.storefront.hero.sub') }}</p>
                        <div class="row rv">
                            <a class="btn" href="#shop">{{ $csHero['cta_label'] !== '' ? $csHero['cta_label'] : __('site.storefront.hero.cta_primary') }}</a>
                            <a class="btn ghost" href="#featured">{{ __('site.storefront.hero.cta_secondary') }}</a>
                        </div>
                    </div>
                    <div class="visual rv">
                        <div class="prod ph">
                            @if ($heroImg)<img src="{{ $heroImg }}" alt="{{ $heroProduct->name }}">@else<span>product</span>@endif
                        </div>
                        @if ($heroProduct)
                            <div class="chip">{{ $heroProduct->name }} · <b>@money($heroProduct->price_cents)</b></div>
                        @endif
                    </div>
                </section>
            @endif
        </div>

        @if (! $isFiltered)
            <div class="strip">
                <span>// FAST-CHARGE</span><span>// USB-C</span><span>// BLUETOOTH 5.4</span><span>// IPX5</span><span>// 2Y WARRANTY</span>
            </div>
        @endif

        <div class="wrap">
            @if (! $isFiltered && $trending->isNotEmpty())
                <div class="sec-head rv" id="featured"><h2>{{ __('site.storefront.featured.h2') }}</h2><a href="#shop">{{ __('site.storefront.featured.browse_all') }} →</a></div>
                <div class="pgrid">
                    @foreach ($trending as $i => $product)
                        @include('themes.tech._card', ['product' => $product, 'badge' => $i === 0 ? __('site.storefront.featured.badge') : null])
                    @endforeach
                </div>
            @endif

            @if (! $isFiltered)
                <section class="banner">
                    <div class="txt">
                        <span class="tag">// {{ __('site.storefront.featured.eyebrow') }}</span>
                        <h3 class="rv">{{ __('site.storefront.promo.h2_prefix', ['tenant' => $tenant->name]) }}</h3>
                        <p class="rv">{{ __('site.storefront.promo.p') }}</p>
                        <a class="btn rv" href="#shop">{{ __('site.storefront.promo.btn') }}</a>
                    </div>
                    <div class="vis">
                        @if ($featured->skip(1)->first() && $featured->skip(1)->first()->image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($featured->skip(1)->first()->image_path) }}" alt="">
                        @endif
                    </div>
                </section>

                @if ($topCategories->isNotEmpty())
                    <div class="cats">
                        @foreach ($topCategories as $cat)
                            <a class="cat rv" href="/categories/{{ $cat->slug }}">
                                @if ($cat->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::url($cat->image_path) }}" alt="">@endif
                                <span class="lab">{{ $cat->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($arrivals->isNotEmpty())
                    <div class="sec-head rv"><h2>{{ __('site.storefront.featured.h2') }}</h2><a href="#shop">{{ __('site.storefront.featured.browse_all') }} →</a></div>
                    <div class="pgrid">
                        @foreach ($arrivals as $product)
                            @include('themes.tech._card', ['product' => $product, 'badge' => null])
                        @endforeach
                    </div>
                @endif
            @endif

            @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
                <section class="rv" style="margin-top:60px">@include('storefront.partials.collection-strips')</section>
            @endif

            <div class="sec-head rv" id="shop"><h2>{{ __('site.storefront.shop_all.h2') }}</h2></div>
            @include('storefront.partials.catalog-controls')
            @if ($products->isEmpty())
                <div class="empty">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="pgrid">
                    @foreach ($products as $product)
                        @include('themes.tech._card', ['product' => $product, 'badge' => null])
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
                        <button type="submit" class="btn">{{ __('site.storefront.footer.subscribe') }}</button>
                    </form>
                </section>
            @endif
        </div>
    </main>
@endsection
