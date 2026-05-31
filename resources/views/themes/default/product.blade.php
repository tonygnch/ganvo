@php
    $title = $product->name;
@endphp
@extends('themes.default.layout')

@section('content')
    @php
        // Gallery images: primary first, then extras. Capped at 4 thumbs
        // so the side-rail layout doesn't overflow.
        $images = $product->allImages()->take(4);
        $heroImage = $images->first()['url'] ?? null;

        // Related products: same category, exclude self, take 4.
        // Falls back to other products in the tenant when category attach is empty.
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

        // Primary category for the breadcrumb. Resolved via the pivot since
        // the StorefrontController doesn't eager-load it.
        $primaryCategory = $primaryCategoryId
            ? \App\Models\Category::find($primaryCategoryId)
            : null;
    @endphp

    <style>
        /* ===== PRODUCT ===== */
        .pdp {
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            gap: 60px;
            padding: 40px 0 0;
        }
        .gallery {
            display: grid;
            grid-template-columns: 74px 1fr;
            gap: 16px;
        }
        .gallery .thumbs {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .gallery .thumbs .pdp-thumb {
            height: 88px;
            cursor: pointer;
            opacity: .6;
            background: var(--soft);
            position: relative;
            overflow: hidden;
            border: none;
            padding: 0;
            transition: opacity .15s ease, outline-color .15s ease;
        }
        .gallery .thumbs .pdp-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .gallery .thumbs .pdp-thumb.on { opacity: 1; outline: 1px solid var(--ink); }
        .gallery .thumbs .pdp-thumb:hover { opacity: .9; }
        .gallery .hero-img {
            height: 640px;
            background: var(--soft);
            overflow: hidden;
            position: relative;
        }
        .gallery .hero-img img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: opacity .25s ease;
        }
        .pinfo { padding-top: 8px; }
        .pinfo .crumb {
            font-size: 11px;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 18px;
        }
        .pinfo .crumb a:hover { color: var(--ink); }
        .pinfo h1 {
            font-family: var(--display);
            font-size: clamp(34px, 4vw, 50px);
            font-weight: 500;
            line-height: 1.04;
        }
        .pinfo .price {
            font-size: 22px;
            margin: 14px 0 8px;
            font-family: var(--display);
        }
        .pinfo .stock {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 26px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .pinfo .stock .dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #59823c;
        }
        .pinfo .stock.low .dot { background: #b06a4a; }
        .pinfo p.desc {
            color: #4f4a40;
            margin-bottom: 28px;
            max-width: 46ch;
        }
        .opt { margin-bottom: 24px; }
        .opt .ol {
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 11px;
        }

        /* Variant picker restyle — the partial renders .vp-* selectors; we
           override them into the Atelier "size button" aesthetic without
           rewriting the partial. */
        .vp .vp-label { display: none; }
        .vp .vp-options {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .vp .vp-option {
            cursor: pointer;
            position: relative;
            display: inline-block;
        }
        .vp .vp-option input { position: absolute; opacity: 0; pointer-events: none; }
        .vp .vp-option-body {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 48px;
            height: 46px;
            padding: 0 12px;
            border: 1px solid var(--line);
            background: none;
            color: var(--ink);
            font-size: 13px;
            font-family: var(--body);
            transition: border-color .15s ease, background-color .15s ease, color .15s ease;
        }
        .vp .vp-option:hover .vp-option-body { border-color: var(--ink); }
        .vp .vp-option input:checked + .vp-option-body {
            border-color: var(--ink);
            background: var(--ink);
            color: var(--paper);
        }
        .vp .vp-option.vp-out .vp-option-body {
            opacity: .35;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        /* The partial renders a price + stock meta line inline; Atelier wants
           clean size pills, so collapse those. The main price still updates
           via the data-vp-price hook in product info above. */
        .vp .vp-option-price, .vp .vp-option-meta { display: none; }

        .add-row {
            display: flex;
            gap: 10px;
            align-items: stretch;
            margin-top: 8px;
        }
        .add-row .btn { flex: 1; }
        .wishlist-btn {
            background: transparent;
            color: var(--ink);
            border: 1px solid var(--ink);
            padding: 0 18px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color .2s ease, color .2s ease;
        }
        .wishlist-btn:hover { background: var(--ink); color: var(--paper); }
        .wishlist-btn svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.6; }

        .accordion {
            margin-top: 34px;
            border-top: 1px solid var(--line);
        }
        .accordion details {
            border-bottom: 1px solid var(--line);
        }
        .accordion summary {
            padding: 18px 0;
            font-size: 13px;
            letter-spacing: .1em;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
            list-style: none;
            cursor: pointer;
            color: var(--ink);
        }
        .accordion summary::-webkit-details-marker { display: none; }
        .accordion summary .marker::after { content: "+"; }
        .accordion details[open] summary .marker::after { content: "−"; }
        .accordion .ac-body {
            padding: 0 0 20px;
            color: #4f4a40;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 1000px) {
            .pdp { grid-template-columns: 1fr; gap: 40px; }
        }
        @media (max-width: 720px) {
            .gallery { grid-template-columns: 1fr; }
            .gallery .thumbs { flex-direction: row; }
            .gallery .thumbs .pdp-thumb { height: 64px; flex: 1; }
            .gallery .hero-img { height: 420px; }
        }
    </style>

    <main>
        <div class="wrap">
            <div class="pdp">
                <div class="gallery rv">
                    <div class="thumbs">
                        @foreach ($images as $i => $img)
                            <button type="button"
                                    class="pdp-thumb {{ $i === 0 ? 'on' : '' }}"
                                    data-pdp-src="{{ $img['url'] }}"
                                    data-pdp-alt="{{ $img['alt'] }}"
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

                <div class="pinfo rv">
                    <div class="crumb">
                        <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a>
                        @if ($primaryCategory)
                            / <a href="/categories/{{ $primaryCategory->slug }}">{{ $primaryCategory->name }}</a>
                        @endif
                        / <span>{{ $product->name }}</span>
                    </div>
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
                            <button type="submit" class="btn" data-vp-submit>
                                {{ __('site.storefront.product.add_to_cart') }} — <span data-vp-submit-price>@money($product->price_cents)</span>
                            </button>
                            <button type="button" class="wishlist-btn" aria-label="{{ __('site.storefront.product.wishlist') }}" title="{{ __('site.storefront.product.wishlist') }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9z"/></svg>
                            </button>
                        </div>
                    </form>

                    <div class="accordion">
                        <details open>
                            <summary>{{ __('site.storefront.product.perks.shipping') }}<span class="marker"></span></summary>
                            <div class="ac-body">{{ __('site.storefront.value_props.shipping_sub') }}</div>
                        </details>
                        <details>
                            <summary>{{ __('site.storefront.product.perks.returns') }}<span class="marker"></span></summary>
                            <div class="ac-body">{{ __('site.storefront.value_props.returns_sub') }}</div>
                        </details>
                        <details>
                            <summary>{{ __('site.storefront.product.perks.fast') }}<span class="marker"></span></summary>
                            <div class="ac-body">{{ __('site.storefront.value_props.checkout_sub') }}</div>
                        </details>
                    </div>
                </div>
            </div>

            @if ($related->isNotEmpty())
                <div class="sec-head rv">
                    <h2>{{ __('site.storefront.featured.h2') }}</h2>
                    <a href="/">{{ __('site.storefront.featured.browse_all') }} →</a>
                </div>
                <div class="pgrid">
                    @foreach ($related as $rp)
                        <a class="pcard rv" href="/products/{{ $rp->slug }}">
                            <div class="img ph">
                                @if ($rp->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($rp->image_path) }}" alt="{{ $rp->name }}">
                                @else
                                    <span>{{ $rp->name }}</span>
                                @endif
                            </div>
                            <div class="nm">{{ $rp->name }}</div>
                            <div class="pr">@money($rp->price_cents)</div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    <script>
        // Click-to-swap on thumbnails. Vanilla, no library.
        (function () {
            var main = document.getElementById('pdp-main-image');
            if (! main) return;
            document.querySelectorAll('.pdp-thumb[data-pdp-src]').forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    main.style.opacity = '0';
                    requestAnimationFrame(function () {
                        main.src = thumb.dataset.pdpSrc;
                        main.alt = thumb.dataset.pdpAlt;
                        main.style.opacity = '1';
                    });
                    document.querySelectorAll('.pdp-thumb').forEach(function (t) {
                        t.classList.toggle('on', t === thumb);
                    });
                });
            });
        })();
    </script>
@endsection
