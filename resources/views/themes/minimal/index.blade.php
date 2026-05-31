@extends('themes.minimal.layout')

@section('content')
    @php
        $isFiltered = ($filters['q'] ?? null) || ($filters['category'] ?? null) || ($filters['min_price'] ?? null) !== null || ($filters['max_price'] ?? null) !== null || ($filters['in_stock'] ?? false) || (($filters['sort'] ?? 'newest') !== 'newest') || $products->currentPage() > 1;
        $featured = $products->take(8);
        $heroProduct = $featured->first();
        $heroImg = $heroProduct && $heroProduct->image_path ? \Illuminate\Support\Facades\Storage::url($heroProduct->image_path) : null;
        $csHero = $store->heroBanner();
        $topCategories = ($categories ?? collect())->where('parent_id', null)->sortBy('sort_order')->take(3)->values();
        $ritualImg = $featured->skip(1)->first() && $featured->skip(1)->first()->image_path ? \Illuminate\Support\Facades\Storage::url($featured->skip(1)->first()->image_path) : null;
    @endphp

    <style>
        .hero { margin-top: 14px; border-radius: 34px; background: linear-gradient(120deg,#f6dccd,#f9eae0 55%,#f1d2c3); position: relative; overflow: hidden; padding: 56px 56px; min-height: 360px; display: flex; align-items: center; }
        .hero .blob { position: absolute; border-radius: 50%; filter: blur(2px); }
        .hero .b1 { width: 520px; height: 520px; background: rgba(231,170,142,.5); right: -120px; top: -120px; }
        .hero .b2 { width: 300px; height: 300px; background: rgba(255,255,255,.5); right: 160px; bottom: -90px; }
        .hero .cap { position: relative; z-index: 2; max-width: 560px; }
        .hero .k { letter-spacing: .18em; text-transform: uppercase; font-size: 12px; color: var(--accent); font-weight: 700; }
        .hero h1 { font-family: var(--display); font-size: clamp(34px,4.4vw,56px); line-height: 1.05; margin: 14px 0 16px; color: #5a3f35; }
        .hero p { font-size: 17px; color: #7a5e54; max-width: 42ch; margin-bottom: 30px; }
        .hero .pimg { position: absolute; right: 64px; bottom: 0; width: 250px; height: 320px; z-index: 2; border-radius: 24px 24px 0 0; overflow: hidden; }
        .hero .pimg img { width: 100%; height: 100%; object-fit: cover; }

        .trust { display: flex; justify-content: center; gap: 50px; flex-wrap: wrap; padding: 34px 0; color: var(--muted); font-size: 13px; letter-spacing: .04em; }
        .trust b { color: var(--ink); font-weight: 600; }

        .ritual { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; margin: 72px 0; background: var(--blush); border-radius: 34px; padding: 48px; }
        .ritual .img { height: 330px; border-radius: 24px; overflow: hidden; }
        .ritual .img img { width: 100%; height: 100%; object-fit: cover; }
        .ritual h3 { font-family: var(--display); font-size: clamp(32px,4vw,48px); line-height: 1.05; margin-bottom: 18px; }
        .ritual p { color: #7a5e54; margin-bottom: 24px; }

        .cats { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; margin: 60px 0; }
        .cat { position: relative; height: 280px; border-radius: 24px; overflow: hidden; background: var(--blush); display: flex; align-items: flex-end; padding: 22px; }
        .cat img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .cat .lab { position: relative; z-index: 1; font-family: var(--display); font-size: 24px; color: #fff; text-shadow: 0 2px 16px rgba(0,0,0,.35); }

        .news { text-align: center; margin: 60px auto 100px; max-width: 560px; background: var(--card); border-radius: 30px; padding: 54px 40px; }
        .news h3 { font-family: var(--display); font-size: 34px; }
        .news p { color: var(--muted); margin: 12px 0 26px; }
        .news form { display: flex; gap: 10px; }
        .news input { flex: 1; border: 1.5px solid var(--line); border-radius: 99px; background: var(--bg); padding: 14px 20px; font-family: inherit; font-size: 15px; }
        .news input:focus { outline: none; border-color: var(--accent); }

        .empty { text-align: center; padding: 60px; color: var(--muted); }

        @media (max-width: 1000px) { .ritual { grid-template-columns: 1fr; } .ritual .img { height: 260px; } .hero .pimg { display: none; } .hero { padding: 44px 28px; min-height: 280px; } .cats { grid-template-columns: 1fr; } }
    </style>

    <main>
        <div class="wrap">
            @if (! $isFiltered)
                <section class="hero">
                    <div class="blob b1"></div><div class="blob b2"></div>
                    <div class="cap">
                        <div class="k rv">{{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</div>
                        <h1 class="rv">{{ $csHero['subtitle'] !== '' ? $csHero['subtitle'] : __('site.storefront.hero.headline', ['tenant' => $tenant->name]) }}</h1>
                        <p class="rv">{{ __('site.storefront.hero.sub') }}</p>
                        <div class="rv" style="display:flex;gap:14px;flex-wrap:wrap">
                            <a class="btn" href="#shop">{{ $csHero['cta_label'] !== '' ? $csHero['cta_label'] : __('site.storefront.hero.cta_primary') }}</a>
                            @if ($heroProduct)<a class="btn outline" href="/products/{{ $heroProduct->slug }}">{{ __('site.storefront.hero.cta_secondary') }} →</a>@endif
                        </div>
                    </div>
                    @if ($heroImg)<div class="pimg rv"><img src="{{ $heroImg }}" alt=""></div>@endif
                </section>

                <div class="trust rv">
                    <span><b>{{ __('site.storefront.value_props.shipping_title') }}</b></span>
                    <span><b>{{ __('site.storefront.value_props.returns_title') }}</b></span>
                    <span><b>{{ __('site.storefront.value_props.checkout_title') }}</b></span>
                </div>

                @if ($featured->isNotEmpty())
                    <div class="sec-head rv" id="featured"><div class="k">{{ __('site.storefront.featured.eyebrow') }}</div><h2>{{ __('site.storefront.featured.h2') }}</h2></div>
                    <div class="pgrid">@foreach ($featured->take(4) as $product)@include('themes.minimal._card')@endforeach</div>
                @endif

                <section class="ritual">
                    <div class="img ph">@if ($ritualImg)<img src="{{ $ritualImg }}" alt="">@else<span>ritual</span>@endif</div>
                    <div>
                        <h3 class="rv">{{ __('site.storefront.promo.h2_prefix', ['tenant' => $tenant->name]) }}</h3>
                        <p class="rv">{{ __('site.storefront.promo.p') }}</p>
                        <a class="btn rv" href="#shop">{{ __('site.storefront.promo.btn') }}</a>
                    </div>
                </section>

                @if ($topCategories->isNotEmpty())
                    <div class="cats">
                        @foreach ($topCategories as $cat)
                            <a class="cat rv" href="/categories/{{ $cat->slug }}">@if ($cat->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::url($cat->image_path) }}" alt="">@endif<span class="lab">{{ $cat->name }}</span></a>
                        @endforeach
                    </div>
                @endif
            @endif

            @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
                <section class="rv">@include('storefront.partials.collection-strips')</section>
            @endif

            <div class="sec-head rv" id="shop"><div class="k">{{ __('site.storefront.shop_all.eyebrow') }}</div><h2>{{ __('site.storefront.shop_all.h2') }}</h2></div>
            @include('storefront.partials.catalog-controls')
            @if ($products->isEmpty())
                <div class="empty">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="pgrid">@foreach ($products as $product)@include('themes.minimal._card')@endforeach</div>
                @include('storefront.partials.pagination')
            @endif

            @if (! $isFiltered)
                <section class="news rv">
                    <h3>{{ __('site.storefront.footer.subscribe') }}</h3>
                    <p>{{ __('site.storefront.footer.tagline') }}</p>
                    <form data-subscribed-label="{{ __('site.storefront.footer.subscribed') }}" onsubmit="event.preventDefault(); this.querySelector('input').value=''; this.querySelector('button').textContent=this.dataset.subscribedLabel;">
                        <input type="email" placeholder="{{ __('site.storefront.footer.newsletter_placeholder') }}" required>
                        <button type="submit" class="btn">{{ __('site.storefront.footer.subscribe') }}</button>
                    </form>
                </section>
            @endif
        </div>
    </main>
@endsection
