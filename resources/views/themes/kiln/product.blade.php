@php
    $title = $product->name;
@endphp
@extends('themes.kiln.layout')

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
        /* Kiln PDP — sticky editorial gallery on the left, info column on the
           right. Square stone-gradient blocks, serif headline, ink price. */
        .pdp { display: grid; grid-template-columns: 1.2fr 1fr; gap: 70px; padding: 20px 0 0; align-items: start; }

        /* gallery — sticky hero + thumbnail row */
        .pgal { position: sticky; top: calc(var(--header-height) + 24px); }
        .pgal .main { height: 600px; overflow: hidden; position: relative; }
        .pgal .main[data-pdp-open] { cursor: zoom-in; }
        .pgal .main img { width: 100%; height: 100%; object-fit: cover; }
        .pgal .thumbs { display: flex; gap: 14px; margin-top: 16px; }
        .pgal .thumbs .pdp-thumb { flex: 1; height: 96px; cursor: pointer; opacity: .6; overflow: hidden; border: none; padding: 0; background: var(--soft); transition: opacity .2s ease, outline-color .2s ease; outline: 1px solid transparent; outline-offset: 0; }
        .pgal .thumbs .pdp-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .pgal .thumbs .pdp-thumb.on, .pgal .thumbs .pdp-thumb:hover { opacity: 1; }
        .pgal .thumbs .pdp-thumb.on { outline-color: var(--ink); }

        /* info column */
        .pinfo .crumb { font-family: var(--display); font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); margin-bottom: 14px; }
        .pinfo .crumb a:hover { color: var(--accent); }
        .pinfo .cat { font-family: var(--display); font-size: 11px; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); }
        .pinfo h1 { font-family: var(--serif); font-size: clamp(36px, 4.4vw, 56px); line-height: 1.04; margin: 8px 0 10px; font-weight: 400; letter-spacing: -.01em; }
        .pinfo h1 em { font-style: italic; color: var(--accent); }
        /* gallery-placard annotation under the title */
        .pinfo .placard { font-family: var(--serif); font-style: italic; font-size: 16px; color: var(--muted); display: flex; align-items: center; gap: 12px; }
        .pinfo .placard::before { content: ""; width: 30px; height: 1px; background: var(--accent); flex-shrink: 0; }
        .pinfo .price { font-family: var(--display); font-weight: 600; font-size: 22px; font-variant-numeric: tabular-nums; margin: 16px 0 22px; color: var(--ink); }
        .pinfo .stock { font-size: 13px; color: var(--muted); margin: -6px 0 18px; display: inline-flex; align-items: center; gap: 8px; }
        .pinfo .stock .dot { width: 8px; height: 8px; border-radius: 99px; background: var(--accent); }
        .pinfo p.desc { color: #5d5c50; margin-bottom: 24px; max-width: 46ch; }
        .pinfo ul.perks { list-style: none; border-top: 1px solid var(--ink); margin-bottom: 26px; }
        .pinfo ul.perks li { padding: 13px 0; border-bottom: 1px solid var(--line); font-size: 14px; display: flex; gap: 10px; }
        .pinfo ul.perks li::before { content: "—"; color: var(--accent); }

        .opt { margin-bottom: 24px; }
        .opt .ol { font-family: var(--display); font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin-bottom: 11px; }

        /* ===== Fullscreen image viewer (lightbox) — Kiln styling.
           Esc closes, ←/→ navigate, focus is trapped while open. ===== */
        .pdp-lightbox { position: fixed; inset: 0; z-index: 200; background: rgba(38, 36, 31, .88); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; padding: 6vh 6vw; animation: lbFade .22s ease; }
        .pdp-lightbox[hidden] { display: none; }
        @keyframes lbFade { from { opacity: 0; } to { opacity: 1; } }
        @keyframes lbPop { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: scale(1); } }
        .pdp-lightbox .lb-stage { position: relative; margin: 0; display: flex; flex-direction: column; align-items: center; gap: 16px; max-width: 92vw; animation: lbPop .26s cubic-bezier(.19, .7, .16, 1); }
        .pdp-lightbox #pdp-lightbox-image { max-width: 86vw; max-height: 80vh; object-fit: contain; background: var(--card); padding: 10px; box-shadow: 0 30px 70px -26px rgba(0, 0, 0, .65); }
        .pdp-lightbox .lb-count { font-family: var(--serif); font-style: italic; font-size: 18px; color: var(--bg); letter-spacing: .02em; }
        .pdp-lightbox .lb-close { position: absolute; top: 24px; right: 26px; width: 50px; height: 50px; border-radius: 2px; background: var(--card); color: var(--ink); border: none; box-shadow: 0 10px 24px -10px rgba(0, 0, 0, .6); font-size: 17px; line-height: 1; display: grid; place-items: center; transition: background-color .22s ease, color .22s ease; }
        .pdp-lightbox .lb-close:hover { background: var(--ink); color: var(--bg); }
        .pdp-lightbox .lb-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 56px; height: 56px; border-radius: 2px; background: var(--card); color: var(--ink); border: none; box-shadow: 0 10px 24px -10px rgba(0, 0, 0, .6); font-family: var(--display); font-size: 30px; line-height: 1; display: grid; place-items: center; transition: background-color .22s ease, color .22s ease; }
        .pdp-lightbox .lb-prev { left: 24px; }
        .pdp-lightbox .lb-next { right: 24px; }
        .pdp-lightbox .lb-nav:hover { background: var(--ink); color: var(--bg); }
        @media (prefers-reduced-motion: reduce) {
            .pdp-lightbox, .pdp-lightbox .lb-stage { animation: none; }
            .pdp-lightbox .lb-close:hover { transform: none; }
        }
        @media (max-width: 560px) {
            .pdp-lightbox { padding: 0; }
            .pdp-lightbox #pdp-lightbox-image { max-width: 94vw; max-height: 74vh; }
            .pdp-lightbox .lb-close { top: 14px; right: 14px; }
            .pdp-lightbox .lb-nav { width: 46px; height: 46px; font-size: 24px; }
            .pdp-lightbox .lb-prev { left: 10px; } .pdp-lightbox .lb-next { right: 10px; }
        }

        @media (max-width: 880px) {
            .pdp { grid-template-columns: 1fr; gap: 34px; }
            .pgal { position: static; }
            .pgal .main { height: 440px; }
        }
    </style>

    <main>
        <div class="wrap" style="padding-top: 24px;">
            <div class="pdp">
                <div class="pgal reveal">
                    <div class="main {{ $heroImage ? '' : 'ph' }}" @if ($heroImage) data-pdp-open @endif>
                        @if ($heroImage)
                            <img id="pdp-main-image" src="{{ $heroImage }}" alt="{{ $product->name }}">
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

                <div class="pinfo reveal s1">
                    <div class="crumb">
                        <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a>
                        @if ($primaryCategory) / <a href="/categories/{{ $primaryCategory->slug }}">{{ $primaryCategory->name }}</a>@endif
                        / <span>{{ $product->name }}</span>
                    </div>

                    @if ($primaryCategory)<div class="cat">{{ $primaryCategory->name }}</div>@endif
                    <h1>{{ $product->name }}</h1>
                    @if ($theme->on('placard'))<div class="placard">{{ $theme->label('placard') }}</div>@endif
                    <div class="price"><span data-vp-price>@money($product->price_cents)</span></div>

                    @if (! $product->hasVariants() && $product->stock_quantity > 0)
                        <div class="stock">
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

                    <ul class="perks">
                        <li>{{ __('site.storefront.value_props.shipping_sub') }}</li>
                        <li>{{ __('site.storefront.value_props.returns_sub') }}</li>
                        <li>{{ __('site.storefront.value_props.checkout_sub') }}</li>
                    </ul>

                    <form method="post" action="/cart/add/{{ $product->slug }}">
                        @csrf
                        @if ($product->hasVariants())
                            <div class="opt">
                                @include('storefront.partials.variant-picker')
                            </div>
                        @endif

                        <button type="submit" class="btn block" data-vp-submit @if ($product->hasVariants()) disabled @endif>
                            {{ __('site.storefront.product.add_to_cart') }} — <span data-vp-submit-price>@money($product->price_cents)</span>
                        </button>
                    </form>
                </div>
            </div>

            @if ($related->isNotEmpty())
                <div class="sec-head" style="margin-top: 70px;">
                    <div class="htext">
                        <span class="kicker">{{ __('site.storefront.kiln.related_eyebrow') }}</span>
                        <h2>{{ __('site.storefront.featured.browse_all') }}</h2>
                    </div>
                    <a class="more" href="/">{{ __('site.storefront.featured.browse_all') }} →</a>
                </div>
                <div class="blooms">
                    @foreach ($related as $rp)
                        @include('themes.kiln._card', ['product' => $rp, 'badge' => null])
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    @if ($heroImage)
        {{-- Fullscreen image viewer. Hidden until the main image is clicked.
             Keyboard: Esc closes, ←/→ navigate, focus is trapped while open
             and returned to the opener on close. --}}
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

    @push('scripts')
        <script>
            // Posy gallery: thumbnail → hero swap + click-to-zoom fullscreen
            // viewer. One controller so the active thumb, the hero image and the
            // open lightbox always stay on the same frame.
            (function () {
                var main = document.getElementById('pdp-main-image');
                if (! main) return;

                // Image set is rendered server-side so the viewer works even when
                // a product has a single image (no thumbnail strip).
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
                    document.querySelectorAll('.pgal .thumbs .pdp-thumb').forEach(function (t) {
                        t.classList.toggle('on', parseInt(t.dataset.pdpIndex, 10) === i);
                    });
                    if (lb && ! lb.hidden) syncLightbox();
                }
                document.querySelectorAll('.pgal .thumbs .pdp-thumb[data-pdp-index]').forEach(function (thumb) {
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
    @endpush
@endsection
