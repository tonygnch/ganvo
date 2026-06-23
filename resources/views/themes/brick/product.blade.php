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
        /* align-items: start so the gallery does NOT stretch to the (taller)
           info column — that was ballooning the product image to ~600px and
           pushing the page off-screen. The image now keeps a fixed size and
           the gallery sticks in view while the details scroll. */
        .pdp { display: grid; grid-template-columns: 1fr 1fr; gap: 0; align-items: start; border: 2.5px solid var(--ink); box-shadow: var(--pop-lg); margin-top: 32px; background: var(--paper); }

        /* gallery — sticky + top-aligned; hero image on top, thumbnail row below.
           The full-height divider moves to .pinfo (border-left) since the gallery
           is now shorter than the info column. */
        .gallery { display: flex; flex-direction: column; align-self: start; position: sticky; top: calc(var(--header-height) + 18px); }
        .gallery .hero-img { height: 480px; background: var(--soft); overflow: hidden; position: relative; }
        .gallery .hero-img img { width: 100%; height: 100%; object-fit: cover; }
        /* Fullscreen affordance — brutalist chip in the image's top-right. */
        .gallery .pdp-zoom { position: absolute; top: 12px; right: 12px; z-index: 2; width: 40px; height: 40px; background: var(--paper); border: 2.5px solid var(--ink); box-shadow: var(--pop-sm); display: grid; place-items: center; transition: transform .12s ease, box-shadow .12s ease, background-color .12s ease; }
        .gallery .pdp-zoom:hover { background: var(--accent); transform: translate(-1px, -1px); box-shadow: var(--pop); }
        .gallery .pdp-zoom:active { transform: translate(2px, 2px); box-shadow: 0 0 0 var(--shadow); }
        .gallery .pdp-zoom svg { width: 18px; height: 18px; fill: none; stroke: var(--ink); stroke-width: 2.5; stroke-linecap: square; }
        /* Thumbnail strip — horizontal row beneath the hero. */
        .gallery .thumbs { display: flex; flex-direction: row; border-top: 2.5px solid var(--ink); }
        .gallery .thumbs .pdp-thumb { flex: 1; height: 84px; cursor: pointer; background: var(--soft); position: relative; overflow: hidden; border: none; border-right: 2.5px solid var(--ink); padding: 0; }
        .gallery .thumbs .pdp-thumb:last-child { border-right: none; }
        .gallery .thumbs .pdp-thumb img { width: 100%; height: 100%; object-fit: cover; opacity: .6; transition: opacity .12s ease; }
        .gallery .thumbs .pdp-thumb.on img, .gallery .thumbs .pdp-thumb:hover img { opacity: 1; }
        .gallery .thumbs .pdp-thumb.on { background: var(--accent); }
        .gallery .thumbs .pdp-thumb:active { transform: translate(1px, 1px); }
        @media (prefers-reduced-motion: reduce) { .gallery .thumbs .pdp-thumb:active, .gallery .pdp-zoom { transform: none; } }

        /* ===== Fullscreen image viewer (lightbox) ===== */
        .pdp-lightbox { position: fixed; inset: 0; z-index: 200; background: rgba(10, 10, 10, .92); display: flex; align-items: center; justify-content: center; padding: 6vh 6vw; animation: lbIn .18s ease; }
        .pdp-lightbox[hidden] { display: none; }
        @keyframes lbIn { from { opacity: 0; } to { opacity: 1; } }
        @media (prefers-reduced-motion: reduce) { .pdp-lightbox { animation: none; } }
        .pdp-lightbox .lb-stage { position: relative; margin: 0; display: flex; flex-direction: column; align-items: center; gap: 14px; max-width: 92vw; }
        .pdp-lightbox #pdp-lightbox-image { max-width: 88vw; max-height: 80vh; object-fit: contain; border: 2.5px solid var(--ink); box-shadow: var(--pop-lg); background: var(--soft); }
        .pdp-lightbox .lb-count { font-family: var(--display); font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--paper); }
        .pdp-lightbox .lb-close { position: absolute; top: 22px; right: 24px; width: 48px; height: 48px; background: var(--accent); color: var(--ink); border: 2.5px solid var(--ink); box-shadow: var(--pop-sm); font-family: var(--display); font-size: 20px; font-weight: 800; display: grid; place-items: center; transition: transform .12s ease, box-shadow .12s ease; }
        .pdp-lightbox .lb-close:hover { transform: translate(-1px, -1px); box-shadow: var(--pop); }
        .pdp-lightbox .lb-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 54px; height: 54px; background: var(--paper); color: var(--ink); border: 2.5px solid var(--ink); box-shadow: var(--pop-sm); font-family: var(--display); font-size: 30px; font-weight: 800; line-height: 1; display: grid; place-items: center; transition: background-color .12s ease, transform .12s ease, box-shadow .12s ease; }
        .pdp-lightbox .lb-prev { left: 24px; }
        .pdp-lightbox .lb-next { right: 24px; }
        .pdp-lightbox .lb-nav:hover { background: var(--accent); transform: translateY(-50%) translate(-1px, -1px); box-shadow: var(--pop); }
        @media (prefers-reduced-motion: reduce) { .pdp-lightbox .lb-close, .pdp-lightbox .lb-nav { transition: none; } }
        @media (max-width: 540px) {
            .pdp-lightbox { padding: 0; }
            .pdp-lightbox #pdp-lightbox-image { max-width: 96vw; max-height: 74vh; }
            .pdp-lightbox .lb-nav { width: 44px; height: 44px; font-size: 24px; }
            .pdp-lightbox .lb-prev { left: 10px; } .pdp-lightbox .lb-next { right: 10px; }
        }

        /* info — carries the full-height divider between the two columns */
        .pinfo { padding: 36px 34px; border-left: 2.5px solid var(--ink); }
        .pinfo .crumb { font-family: var(--display); font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); margin-bottom: 16px; }
        .pinfo .crumb a { border-bottom: 2px solid transparent; transition: border-color .12s ease; }
        .pinfo .crumb a:hover { color: var(--ink); border-bottom-color: var(--accent); }
        .pinfo .cat { display: inline-flex; background: var(--ink); color: var(--accent); font-family: var(--display); font-weight: 800; font-size: 11px; letter-spacing: .05em; text-transform: uppercase; padding: 5px 10px; margin-bottom: 14px; }
        .pinfo h1 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(24px, 2.6vw, 36px); line-height: .95; letter-spacing: -.02em; margin-bottom: 16px; }
        .pinfo .price { display: inline-flex; background: var(--accent); border: 2.5px solid var(--ink); box-shadow: var(--pop-sm); font-family: var(--display); font-weight: 800; font-size: 22px; padding: 8px 16px; margin-bottom: 18px; }
        .pinfo .stock { font-family: var(--display); font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 22px; display: flex; align-items: center; gap: 8px; }
        .pinfo .stock .dot { width: 9px; height: 9px; background: #1a7a1a; border: 2px solid var(--ink); }
        .pinfo .stock.low .dot { background: var(--accent); }
        .pinfo p.desc { color: var(--text-muted); margin-bottom: 24px; max-width: 44ch; }

        .opt { margin-bottom: 22px; }
        .opt .ol { font-family: var(--display); font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 11px; }

        .add-row { display: flex; gap: 12px; align-items: stretch; margin-top: 8px; }
        .add-row .btn { flex: 1; }
        .wishlist-btn { background: var(--paper); border: 2.5px solid var(--ink); box-shadow: var(--pop-sm); padding: 0 16px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; transition: transform .12s ease, box-shadow .12s ease, background-color .12s ease; }
        .wishlist-btn:hover { background: var(--accent); transform: translate(-1px,-1px); box-shadow: var(--pop); }
        .wishlist-btn svg { width: 20px; height: 20px; fill: none; stroke: var(--ink); stroke-width: 2; }

        .acc { margin-top: 28px; border-top: 2.5px solid var(--ink); }
        .acc details { border-bottom: 2.5px solid var(--ink); }
        .acc summary { padding: 16px 0; font-family: var(--display); font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; display: flex; justify-content: space-between; list-style: none; cursor: pointer; transition: color .15s ease; }
        .acc summary:hover { color: var(--muted); }
        .acc summary:focus-visible { outline: 3px solid var(--ink); outline-offset: 2px; }
        .acc summary::-webkit-details-marker { display: none; }
        .acc summary .marker::after { content: "[+]"; }
        .acc details[open] summary .marker::after { content: "[–]"; }
        .acc .b { padding: 0 0 16px; color: var(--text-muted); font-size: 14px; line-height: 1.6; animation: accIn .2s ease; }
        @keyframes accIn { from { opacity: 0; } to { opacity: 1; } }
        @media (prefers-reduced-motion: reduce) { .acc .b { animation: none; } }

        @media (max-width: 980px) {
            .pdp { grid-template-columns: 1fr; }
            /* Stacked: unstick the gallery and put the divider back on its bottom edge. */
            .gallery { position: static; border-bottom: 2.5px solid var(--ink); }
            .pinfo { border-left: none; }
            .gallery .hero-img { height: 420px; }
        }
        @media (max-width: 540px) {
            .gallery .hero-img { height: 360px; }
            .gallery .thumbs .pdp-thumb { height: 68px; }
            /* Stack add-to-cart + wishlist so neither gets squeezed below 44px. */
            .add-row { flex-direction: column; }
            .add-row .btn { flex: 1; }
            .wishlist-btn { width: 100%; }
        }
    </style>

    <main>
        <div class="wrap">
            <div class="pdp">
                <div class="gallery">
                    <div class="hero-img">
                        @if ($heroImage)
                            <img id="pdp-main-image" src="{{ $heroImage }}" alt="{{ $product->name }}" data-pdp-open>
                            <button type="button" class="pdp-zoom" data-pdp-open
                                    aria-label="{{ __('site.storefront.product.view_fullscreen') }}"
                                    title="{{ __('site.storefront.product.view_fullscreen') }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                            </button>
                        @else
                            <div class="ph" style="width:100%;height:100%;"><span>{{ $product->name }}</span></div>
                        @endif
                    </div>
                    @if ($images->count() > 1)
                        <div class="thumbs">
                            @foreach ($images as $i => $img)
                                <button type="button" class="pdp-thumb {{ $i === 0 ? 'on' : '' }}"
                                        data-pdp-src="{{ $img['url'] }}" data-pdp-alt="{{ $img['alt'] }}" data-pdp-index="{{ $i }}"
                                        aria-label="{{ __('site.storefront.product.view_image', ['n' => $i + 1, 'total' => $images->count()]) }}">
                                    <img src="{{ $img['url'] }}" alt="" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
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
                            <button type="submit" class="btn accent block" data-vp-submit @if ($product->hasVariants()) disabled @endif>
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

    @if ($heroImage)
        {{-- Fullscreen image viewer. Hidden until the merchant image / zoom
             button is clicked. Keyboard: Esc closes, ←/→ navigate, focus is
             trapped inside while open and returned to the opener on close. --}}
        <div class="pdp-lightbox" id="pdpLightbox" role="dialog" aria-modal="true"
             aria-label="{{ __('site.storefront.product.gallery_label') }}" hidden>
            <button type="button" class="lb-close" data-pdp-close aria-label="{{ __('site.storefront.product.close') }}">✕</button>
            @if ($images->count() > 1)
                <button type="button" class="lb-nav lb-prev" data-pdp-prev aria-label="{{ __('site.storefront.product.prev_image') }}">‹</button>
                <button type="button" class="lb-nav lb-next" data-pdp-next aria-label="{{ __('site.storefront.product.next_image') }}">›</button>
            @endif
            <figure class="lb-stage">
                <img id="pdp-lightbox-image" src="" alt="{{ $product->name }}">
                @if ($images->count() > 1)<figcaption class="lb-count" id="pdpLbCount"></figcaption>@endif
            </figure>
        </div>
    @endif

    <script>
        (function () {
            var main = document.getElementById('pdp-main-image');
            if (! main) return;

            // Image set rendered server-side so the viewer works even when a
            // product has a single image (no thumbnail strip).
            var images = @json($images->map(fn ($i) => ['src' => $i['url'], 'alt' => $i['alt']])->values());
            var current = 0;
            var lb = document.getElementById('pdpLightbox');
            var lbImg = document.getElementById('pdp-lightbox-image');
            var lbCount = document.getElementById('pdpLbCount');
            var lastFocus = null;

            function setMain(i) {
                if (! images[i]) return;
                current = i;
                main.src = images[i].src;
                main.alt = images[i].alt || '';
                document.querySelectorAll('.pdp-thumb').forEach(function (t) {
                    t.classList.toggle('on', parseInt(t.dataset.pdpIndex, 10) === i);
                });
                if (lb && ! lb.hidden) syncLightbox();
            }
            document.querySelectorAll('.pdp-thumb[data-pdp-index]').forEach(function (thumb) {
                thumb.addEventListener('click', function () { setMain(parseInt(thumb.dataset.pdpIndex, 10)); });
            });

            if (! lb || ! lbImg) return;

            function syncLightbox() {
                lbImg.src = images[current].src;
                lbImg.alt = images[current].alt || '';
                if (lbCount) lbCount.textContent = (current + 1) + ' / ' + images.length;
            }
            function open() {
                lastFocus = document.activeElement;
                syncLightbox();
                lb.hidden = false;
                document.body.style.overflow = 'hidden';
                var c = lb.querySelector('[data-pdp-close]');
                if (c) c.focus();
            }
            function close() {
                lb.hidden = true;
                document.body.style.overflow = '';
                if (lastFocus && lastFocus.focus) lastFocus.focus();
            }
            function step(d) { setMain((current + d + images.length) % images.length); }

            document.querySelectorAll('[data-pdp-open]').forEach(function (el) {
                el.style.cursor = 'zoom-in';
                el.addEventListener('click', open);
            });
            lb.querySelectorAll('[data-pdp-close]').forEach(function (el) { el.addEventListener('click', close); });
            var prev = lb.querySelector('[data-pdp-prev]'); if (prev) prev.addEventListener('click', function () { step(-1); });
            var next = lb.querySelector('[data-pdp-next]'); if (next) next.addEventListener('click', function () { step(1); });
            lb.addEventListener('click', function (e) { if (e.target === lb || e.target.classList.contains('lb-stage')) close(); });

            document.addEventListener('keydown', function (e) {
                if (lb.hidden) return;
                if (e.key === 'Escape') { close(); }
                else if (e.key === 'ArrowLeft') { step(-1); }
                else if (e.key === 'ArrowRight') { step(1); }
                else if (e.key === 'Tab') {
                    var f = Array.prototype.slice.call(lb.querySelectorAll('button'));
                    if (! f.length) return;
                    var first = f[0], last = f[f.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (! e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            });
        })();
    </script>
@endsection
