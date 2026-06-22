@php
    $title = $product->name;
@endphp
@extends('themes.brick.layout')

@section('content')
    @php
        $images = $product->allImages()->take(4);
        $heroImage = $images->first()['url'] ?? null;

        $primaryCategoryId = \Illuminate\Support\Facades\DB::table('category_product')
            ->where('product_id', $product->id)
            ->value('category_id');
        $relatedQ = \App\Models\Product::query()
            ->where('tenant_id', $product->tenant_id)
            ->where('is_active', true)
            ->where('id', '!=', $product->id);
        if ($primaryCategoryId) {
            $relatedQ->whereHas('categories', fn ($q) => $q->where('categories.id', $primaryCategoryId));
        }
        $related = $relatedQ->limit(4)->get();
        if ($related->isEmpty()) {
            $related = \App\Models\Product::query()
                ->where('tenant_id', $product->tenant_id)
                ->where('is_active', true)
                ->where('id', '!=', $product->id)
                ->limit(4)
                ->get();
        }

        $primaryCategory = $primaryCategoryId ? \App\Models\Category::find($primaryCategoryId) : null;
    @endphp

    <style>
        .pdp { display: grid; grid-template-columns: 1.25fr 1fr; gap: 0; border: 2.5px solid var(--ink); box-shadow: var(--pop-lg); margin-top: 32px; background: var(--paper); }

        /* gallery */
        .gallery { border-right: 2.5px solid var(--ink); display: grid; grid-template-columns: 80px 1fr; }
        .gallery .thumbs { display: flex; flex-direction: column; border-right: 2.5px solid var(--ink); }
        .gallery .thumbs .pdp-thumb { height: 92px; cursor: pointer; background: var(--soft); position: relative; overflow: hidden; border: none; border-bottom: 2.5px solid var(--ink); padding: 0; }
        .gallery .thumbs .pdp-thumb img { width: 100%; height: 100%; object-fit: cover; opacity: .6; transition: opacity .12s ease; }
        .gallery .thumbs .pdp-thumb.on img, .gallery .thumbs .pdp-thumb:hover img { opacity: 1; }
        .gallery .thumbs .pdp-thumb.on { background: var(--accent); }
        .gallery .hero-img { min-height: 560px; background: var(--soft); overflow: hidden; position: relative; }
        .gallery .hero-img img { width: 100%; height: 100%; object-fit: cover; }

        /* info */
        .pinfo { padding: 36px 34px; }
        .pinfo .crumb { font-family: var(--display); font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); margin-bottom: 16px; }
        .pinfo .crumb a:hover { color: var(--ink); }
        .pinfo .cat { display: inline-flex; background: var(--ink); color: var(--accent); font-family: var(--display); font-weight: 800; font-size: 11px; letter-spacing: .05em; text-transform: uppercase; padding: 5px 10px; margin-bottom: 14px; }
        .pinfo h1 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(28px, 3.6vw, 48px); line-height: .92; letter-spacing: -.02em; margin-bottom: 16px; }
        .pinfo .price { display: inline-flex; background: var(--accent); border: 2.5px solid var(--ink); box-shadow: var(--pop-sm); font-family: var(--display); font-weight: 800; font-size: 22px; padding: 8px 16px; margin-bottom: 18px; }
        .pinfo .stock { font-family: var(--display); font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 22px; display: flex; align-items: center; gap: 8px; }
        .pinfo .stock .dot { width: 9px; height: 9px; background: #1a7a1a; border: 2px solid var(--ink); }
        .pinfo .stock.low .dot { background: var(--accent); }
        .pinfo p.desc { color: var(--text-muted); margin-bottom: 24px; max-width: 44ch; }

        .opt { margin-bottom: 22px; }
        .opt .ol { font-family: var(--display); font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 11px; }

        .add-row { display: flex; gap: 12px; align-items: stretch; margin-top: 8px; }
        .add-row .btn { flex: 1; }
        .wishlist-btn { background: var(--paper); border: 2.5px solid var(--ink); box-shadow: var(--pop-sm); padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; transition: transform .12s ease, box-shadow .12s ease, background-color .12s ease; }
        .wishlist-btn:hover { background: var(--accent); transform: translate(-1px,-1px); box-shadow: var(--pop); }
        .wishlist-btn svg { width: 20px; height: 20px; fill: none; stroke: var(--ink); stroke-width: 2; }

        .acc { margin-top: 28px; border-top: 2.5px solid var(--ink); }
        .acc details { border-bottom: 2.5px solid var(--ink); }
        .acc summary { padding: 16px 0; font-family: var(--display); font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; display: flex; justify-content: space-between; list-style: none; cursor: pointer; }
        .acc summary::-webkit-details-marker { display: none; }
        .acc summary .marker::after { content: "[+]"; }
        .acc details[open] summary .marker::after { content: "[–]"; }
        .acc .b { padding: 0 0 16px; color: var(--text-muted); font-size: 14px; line-height: 1.6; }

        @media (max-width: 980px) {
            .pdp { grid-template-columns: 1fr; }
            .gallery { border-right: none; border-bottom: 2.5px solid var(--ink); }
            .gallery .hero-img { min-height: 420px; }
        }
        @media (max-width: 540px) {
            .gallery { grid-template-columns: 1fr; }
            .gallery .thumbs { flex-direction: row; border-right: none; border-bottom: 2.5px solid var(--ink); }
            .gallery .thumbs .pdp-thumb { flex: 1; height: 70px; border-bottom: none; border-right: 2.5px solid var(--ink); }
        }
    </style>

    <main>
        <div class="wrap">
            <div class="pdp">
                <div class="gallery">
                    <div class="thumbs">
                        @foreach ($images as $i => $img)
                            <button type="button" class="pdp-thumb {{ $i === 0 ? 'on' : '' }}"
                                    data-pdp-src="{{ $img['url'] }}" data-pdp-alt="{{ $img['alt'] }}"
                                    aria-label="View image {{ $i + 1 }} of {{ $images->count() }}">
                                <img src="{{ $img['url'] }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                    <div class="hero-img">
                        @if ($heroImage)
                            <img id="pdp-main-image" src="{{ $heroImage }}" alt="{{ $product->name }}">
                        @else
                            <div class="ph" style="width:100%;height:100%;"><span>{{ $product->name }}</span></div>
                        @endif
                    </div>
                </div>

                <div class="pinfo">
                    <div class="crumb">
                        <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a>
                        @if ($primaryCategory) / <a href="/categories/{{ $primaryCategory->slug }}">{{ $primaryCategory->name }}</a>@endif
                        / <span>{{ $product->name }}</span>
                    </div>
                    @if ($primaryCategory)<div class="cat">{{ $primaryCategory->name }}</div>@endif
                    <h1>{{ $product->name }}</h1>
                    <div class="price"><span data-vp-price>@money($product->price_cents)</span></div>

                    @if (! $product->hasVariants() && $product->stock_quantity > 0)
                        <div class="stock {{ $product->stock_quantity < 10 ? 'low' : '' }}">
                            <span class="dot"></span>
                            @if ($product->stock_quantity < 10)
                                {{ __('site.storefront.product.in_stock_low', ['count' => $product->stock_quantity]) }}
                            @else
                                {{ __('site.storefront.product.in_stock_full') }}
                            @endif
                        </div>
                    @endif

                    @if ($product->description)
                        <p class="desc">{{ $product->description }}</p>
                    @endif

                    <form method="post" action="/cart/add/{{ $product->slug }}">
                        @csrf
                        @if ($product->hasVariants())
                            <div class="opt">
                                <div class="ol">{{ __('site.storefront.product.choose_variant') }}</div>
                                @include('storefront.partials.variant-picker')
                            </div>
                        @endif

                        <div class="add-row">
                            <button type="submit" class="btn accent block" data-vp-submit>
                                {{ __('site.storefront.product.add_to_cart') }} — <span data-vp-submit-price>@money($product->price_cents)</span>
                            </button>
                            <button type="button" class="wishlist-btn" aria-label="{{ __('site.storefront.product.wishlist') }}" title="{{ __('site.storefront.product.wishlist') }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9z"/></svg>
                            </button>
                        </div>
                    </form>

                    <div class="acc">
                        <details open>
                            <summary>{{ __('site.storefront.product.perks.shipping') }}<span class="marker"></span></summary>
                            <div class="b">{{ __('site.storefront.value_props.shipping_sub') }}</div>
                        </details>
                        <details>
                            <summary>{{ __('site.storefront.product.perks.returns') }}<span class="marker"></span></summary>
                            <div class="b">{{ __('site.storefront.value_props.returns_sub') }}</div>
                        </details>
                        <details>
                            <summary>{{ __('site.storefront.product.perks.fast') }}<span class="marker"></span></summary>
                            <div class="b">{{ __('site.storefront.value_props.checkout_sub') }}</div>
                        </details>
                    </div>
                </div>
            </div>

            @if ($related->isNotEmpty())
                <div class="sec-head rv">
                    <h2>{{ __('site.storefront.product.wear_it_with') }}</h2>
                    <a href="/">{{ __('site.storefront.featured.browse_all') }} →</a>
                </div>
                <div class="pgrid">
                    @foreach ($related as $rp)
                        @include('themes.brick._card', ['product' => $rp, 'badge' => null])
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    <script>
        (function () {
            var main = document.getElementById('pdp-main-image');
            if (! main) return;
            document.querySelectorAll('.pdp-thumb[data-pdp-src]').forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    main.src = thumb.dataset.pdpSrc;
                    main.alt = thumb.dataset.pdpAlt;
                    document.querySelectorAll('.pdp-thumb').forEach(function (t) { t.classList.toggle('on', t === thumb); });
                });
            });
        })();
    </script>
@endsection
