@extends('themes.gallery.layout')

@section('content')
    @php
        $isFiltered = ($filters['q'] ?? null) || ($filters['category'] ?? null) || ($filters['min_price'] ?? null) !== null || ($filters['max_price'] ?? null) !== null || ($filters['in_stock'] ?? false) || (($filters['sort'] ?? 'newest') !== 'newest') || $products->currentPage() > 1;
        $featured = $products->take(8);
        $heroProduct = $featured->first();
        $heroImg = $heroProduct && $heroProduct->image_path ? \Illuminate\Support\Facades\Storage::url($heroProduct->image_path) : null;
        $floatP = $featured->skip(1)->first();
        $csHero = $store->heroBanner();
        $topCategories = ($categories ?? collect())->where('parent_id', null)->sortBy('sort_order')->take(2)->values();
        $storyImg = $featured->skip(2)->first() && $featured->skip(2)->first()->image_path ? \Illuminate\Support\Facades\Storage::url($featured->skip(2)->first()->image_path) : null;
    @endphp

    <style>
        .hero { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; padding: 40px 0 0; min-height: 54vh; }
        .hero .k { font-size: 13px; letter-spacing: .1em; text-transform: uppercase; color: var(--accent); font-weight: 600; }
        .hero h1 { font-family: var(--display); font-weight: 700; font-size: clamp(34px,4.2vw,58px); line-height: 1.04; letter-spacing: -.02em; margin: 14px 0 18px; }
        .hero p { font-size: 17px; color: var(--muted); max-width: 42ch; margin-bottom: 30px; }
        .hero .imgwrap { height: 52vh; min-height: 380px; max-height: 560px; position: relative; }
        .hero .imgwrap .main { position: absolute; inset: 0; border-radius: 18px; overflow: hidden; }
        .hero .imgwrap .main img { width: 100%; height: 100%; object-fit: cover; }
        .hero .imgwrap .float { position: absolute; left: -34px; bottom: 40px; width: 200px; background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: 0 18px 40px -20px rgba(52,48,42,.4); }
        .hero .imgwrap .float .mini { height: 90px; border-radius: 8px; margin-bottom: 10px; overflow: hidden; background: var(--soft); }
        .hero .imgwrap .float .mini img { width: 100%; height: 100%; object-fit: cover; }
        .hero .imgwrap .float .nm { font-size: 13px; font-weight: 600; } .hero .imgwrap .float .pr { font-size: 13px; color: var(--accent); }

        .marq2 { display: flex; justify-content: space-between; gap: 30px; flex-wrap: wrap; padding: 30px 0; margin-top: 30px; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); color: var(--muted); font-size: 14px; }
        .marq2 b { color: var(--ink); }

        .splits { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-top: 80px; }
        .split { position: relative; height: 380px; border-radius: 18px; overflow: hidden; background: var(--soft); display: flex; align-items: flex-end; padding: 32px; }
        .split img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .split .ov { position: relative; z-index: 1; }
        .split .ov h3 { font-family: var(--display); font-weight: 700; font-size: 30px; color: #fff; text-shadow: 0 2px 16px rgba(0,0,0,.4); }

        .story { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin: 72px 0; align-items: stretch; border-radius: 20px; overflow: hidden; }
        .story .img { min-height: 360px; background: var(--soft); } .story .img img { width: 100%; height: 100%; object-fit: cover; }
        .story .txt { background: var(--soft); padding: 64px 56px; display: flex; flex-direction: column; justify-content: center; }
        .story .k { font-size: 13px; letter-spacing: .1em; text-transform: uppercase; color: var(--accent); font-weight: 600; }
        .story h3 { font-family: var(--display); font-weight: 600; font-size: clamp(30px,3.8vw,46px); letter-spacing: -.02em; margin: 14px 0 16px; line-height: 1.04; }
        .story p { color: var(--muted); max-width: 42ch; margin-bottom: 26px; }

        .news { text-align: center; margin: 90px auto; max-width: 560px; }
        .news h3 { font-family: var(--display); font-weight: 600; font-size: clamp(28px,3.4vw,42px); letter-spacing: -.02em; }
        .news p { color: var(--muted); margin: 12px 0 24px; }
        .news form { display: flex; gap: 10px; }
        .news input { flex: 1; border: 1px solid var(--line); border-radius: 8px; background: var(--card); padding: 14px 18px; font-family: inherit; font-size: 15px; }
        .news input:focus { outline: none; border-color: var(--accent); }

        .empty { text-align: center; padding: 60px; color: var(--muted); }

        @media (max-width: 1000px) {
            .hero, .splits, .story { grid-template-columns: 1fr; }
            .hero .imgwrap { height: 40vh; min-height: 280px; } .hero .imgwrap .float { display: none; }
            .story .img { min-height: 300px; }
        }
    </style>

    <main>
        <div class="wrap">
            @if (! $isFiltered)
                <section class="hero">
                    <div>
                        <div class="k rv">{{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</div>
                        <h1 class="rv">{{ $csHero['subtitle'] !== '' ? $csHero['subtitle'] : __('site.storefront.hero.headline', ['tenant' => $tenant->name]) }}</h1>
                        <p class="rv">{{ __('site.storefront.hero.sub') }}</p>
                        <div class="rv" style="display:flex;gap:14px;flex-wrap:wrap">
                            <a class="btn" href="#shop">{{ $csHero['cta_label'] !== '' ? $csHero['cta_label'] : __('site.storefront.hero.cta_primary') }}</a>
                            <a class="btn outline" href="#featured">{{ __('site.storefront.hero.cta_secondary') }}</a>
                        </div>
                    </div>
                    <div class="imgwrap rv">
                        <div class="main ph">@if ($heroImg)<img src="{{ $heroImg }}" alt="">@else<span>{{ __('site.storefront.product.no_image') }}</span>@endif</div>
                        @if ($floatP && $theme->on('hero_float'))
                            <a class="float" href="/products/{{ $floatP->slug }}">
                                <div class="mini">@if ($floatP->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::url($floatP->image_path) }}" alt="">@endif</div>
                                <div class="nm">{{ $floatP->name }}</div><div class="pr">@money($floatP->price_cents)</div>
                            </a>
                        @endif
                    </div>
                </section>

                @if ($theme->on('value_strip'))
                <div class="marq2 rv">
                    <span><b>{{ __('site.storefront.value_props.shipping_title') }}</b> · {{ __('site.storefront.value_props.shipping_sub') }}</span>
                    <span><b>{{ __('site.storefront.value_props.returns_title') }}</b> · {{ __('site.storefront.value_props.returns_sub') }}</span>
                    <span><b>{{ __('site.storefront.value_props.checkout_title') }}</b> · {{ __('site.storefront.value_props.checkout_sub') }}</span>
                </div>
                @endif

                @if ($featured->isNotEmpty() && $theme->on('featured_grid'))
                    <div class="sec-head rv" id="featured"><h2>{{ __('site.storefront.featured.h2') }}</h2><a href="#shop">{{ __('site.storefront.featured.browse_all') }} →</a></div>
                    <div class="pgrid">@foreach ($featured->take(4) as $i => $product)@include('themes.gallery._card', ['badge' => $i === 0 && $theme->on('featured_badge') ? $theme->label('featured_badge') : null])@endforeach</div>
                @endif

                @if ($topCategories->count() >= 1 && $theme->on('category_splits'))
                    <div class="splits">
                        @foreach ($topCategories as $cat)
                            <a class="split rv" href="/categories/{{ $cat->slug }}">@if ($cat->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::url($cat->image_path) }}" alt="">@endif<div class="ov"><h3>{{ $cat->name }}</h3></div></a>
                        @endforeach
                    </div>
                @endif

                @if ($theme->on('story_band'))
                <section class="story">
                    <div class="img">@if ($storyImg)<img src="{{ $storyImg }}" alt="">@endif</div>
                    <div class="txt">
                        <div class="k rv">{{ __('site.storefront.featured.eyebrow') }}</div>
                        <h3 class="rv">{{ __('site.storefront.promo.h2_prefix', ['tenant' => $tenant->name]) }}</h3>
                        <p class="rv">{{ $theme->copy('story_body') }}</p>
                        <a class="btn outline rv" href="#shop" style="align-self:flex-start">{{ __('site.storefront.promo.btn') }}</a>
                    </div>
                </section>
                @endif
            @endif

            @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
                <section class="rv">@include('storefront.partials.collection-strips')</section>
            @endif

            <div class="sec-head rv" id="shop"><h2>{{ $theme->copy('shop_heading') }}</h2></div>
            @include('storefront.partials.catalog-controls')
            @if ($products->isEmpty())
                <div class="empty">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="pgrid">@foreach ($products as $product)@include('themes.gallery._card', ['badge' => null])@endforeach</div>
                @include('storefront.partials.pagination')
            @endif

            @if (! $isFiltered && $theme->on('newsletter'))
                <section class="news rv">
                    <h3>{{ __('site.storefront.footer.subscribe') }}</h3>
                    <p>{{ $theme->copy('news_body') }}</p>
                    <form data-subscribed-label="{{ __('site.storefront.footer.subscribed') }}" onsubmit="event.preventDefault(); this.querySelector('input').value=''; this.querySelector('button').textContent=this.dataset.subscribedLabel;">
                        <input type="email" placeholder="{{ __('site.storefront.footer.newsletter_placeholder') }}" required>
                        <button type="submit" class="btn">{{ __('site.storefront.footer.subscribe') }}</button>
                    </form>
                </section>
            @endif
        </div>
    </main>
@endsection
