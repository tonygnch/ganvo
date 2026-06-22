@php
    $title = $product->name;
@endphp
@extends('themes.default.layout')

@section('content')
    @php
        // Gallery images: primary first, then extras. Capped at 4 so the
        // split layout doesn't overflow.
        $images = $product->allImages()->take(4);
        $heroImage = $images->first()['url'] ?? null;

        // Related products: same category, exclude self, take 4. Falls back
        // to other tenant products when the category attach is empty.
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

        $primaryCategory = $primaryCategoryId
            ? \App\Models\Category::find($primaryCategoryId)
            : null;
    @endphp

    <style>
        /* ===== PRODUCT ===== */
        .pdp { display: grid; grid-template-columns: 1.35fr 1fr; gap: 56px; padding: 36px 0 0; align-items: start; }

        /* gallery: big hero + thumb strip */
        .gallery { display: grid; grid-template-columns: 74px 1fr; gap: 14px; }
        .gallery .thumbs { display: flex; flex-direction: column; gap: 12px; }
        .gallery .thumbs .pdp-thumb {
            height: 92px; cursor: pointer; opacity: .55; background: var(--soft);
            position: relative; overflow: hidden; border: none; padding: 0;
            transition: opacity .15s ease, outline-color .15s ease;
        }
        .gallery .thumbs .pdp-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .gallery .thumbs .pdp-thumb.on { opacity: 1; outline: 1px solid var(--ink); }
        .gallery .thumbs .pdp-thumb:hover { opacity: .85; }
        .gallery .hero-img { height: 640px; background: var(--soft); overflow: hidden; position: relative; }
        .gallery .hero-img img { width: 100%; height: 100%; object-fit: cover; transition: opacity .35s var(--ease-soft), transform 1.2s var(--ease-out); }
        .gallery .hero-img:hover img { transform: scale(1.04); }

        /* info column */
        .pinfo { position: sticky; top: 96px; }
        .pinfo .crumb { font-size: 10px; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); margin-bottom: 16px; }
        .pinfo .crumb a:hover { color: var(--ink); }
        .pinfo .kicker { color: var(--accent); }
        .pinfo h1 { font-family: var(--display); font-weight: 800; text-transform: uppercase; font-size: clamp(28px, 3.6vw, 48px); line-height: .95; margin: 12px 0 10px; letter-spacing: -.02em; }
        .pinfo .price { font-family: var(--serif); font-size: 26px; margin-bottom: 6px; }
        .pinfo .stock { font-size: 12px; color: var(--muted); margin-bottom: 22px; display: inline-flex; align-items: center; gap: 8px; }
        .pinfo .stock .dot { width: 7px; height: 7px; border-radius: 50%; background: #59823c; }
        .pinfo .stock.low .dot { background: var(--accent); }
        .pinfo p.desc { color: #3c382f; margin-bottom: 24px; max-width: 44ch; }

        .opt { margin-bottom: 22px; }
        .opt .ol { font-size: 10px; letter-spacing: .16em; text-transform: uppercase; color: var(--muted); margin-bottom: 11px; }

        .add-row { display: flex; gap: 10px; align-items: stretch; margin-top: 8px; }
        .add-row .btn { flex: 1; }
        .wishlist-btn {
            background: transparent; color: var(--ink); border: 1px solid var(--ink);
            padding: 0 18px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
            transition: background-color .2s ease, color .2s ease;
        }
        .wishlist-btn:hover { background: var(--ink); color: var(--paper); }
        .wishlist-btn svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.6; }

        /* accordion */
        .acc { margin-top: 30px; border-top: 1px solid var(--ink); }
        .acc details { border-bottom: 1px solid var(--rule); }
        .acc summary { padding: 16px 0; font-size: 11px; letter-spacing: .14em; text-transform: uppercase; display: flex; justify-content: space-between; list-style: none; cursor: pointer; transition: color .25s var(--ease-soft); }
        .acc summary:hover { color: var(--accent); }
        .acc summary::-webkit-details-marker { display: none; }
        .acc summary .marker::after { content: "+"; }
        .acc details[open] summary .marker::after { content: "−"; }
        .acc .b { padding: 0 0 16px; color: #3c382f; font-size: 14px; line-height: 1.6; }

        @media (max-width: 1080px) {
            .pdp { grid-template-columns: 1fr; gap: 40px; }
            .pinfo { position: static; }
        }
        @media (max-width: 680px) {
            .gallery { grid-template-columns: 1fr; }
            .gallery .thumbs { flex-direction: row; }
            .gallery .thumbs .pdp-thumb { height: 64px; flex: 1; }
            .gallery .hero-img { height: 440px; }
        }
    </style>

    <main>
        <div class="wrap">
            <div class="ed-head" style="border:none; padding-bottom:0">
                <div class="crumb">
                    <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a>
                    @if ($primaryCategory)
                        / <a href="/categories/{{ $primaryCategory->slug }}">{{ $primaryCategory->name }}</a>
                    @endif
                    / <span>{{ $product->name }}</span>
                </div>
            </div>

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
                    <div class="kicker">{{ $primaryCategory->name ?? __('site.storefront.featured.badge') }}</div>
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
                            <button type="submit" class="btn red block" data-vp-submit>
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
                        @include('themes.default._card', ['product' => $rp, 'badge' => null])
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    <script>
        // Click-to-swap thumbnails. Vanilla, no library.
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
