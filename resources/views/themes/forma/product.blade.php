@php
    $title = $product->name;
@endphp
@extends('themes.forma.layout')

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
        /* Forma PDP — sticky product stage on the left, spec info column on the
           right. Cobalt object stage, squared chips, mono spec labels. */
        .pdp { display: grid; grid-template-columns: 1.1fr 1fr; gap: 54px; padding: 34px 0 0; align-items: start; }

        /* gallery — sticky hero stage + thumbnail row. The stage carries the
           same blueprint graph grid as the home configurator. */
        .pgal { position: sticky; top: calc(var(--header-height) + 22px); }
        .pgal .main { height: 560px; border-radius: 24px; overflow: hidden; position: relative; display: grid; place-items: center;
            background:
                radial-gradient(115% 85% at 50% 32%, rgba(255, 255, 255, .92), rgba(255, 255, 255, 0) 64%),
                linear-gradient(color-mix(in srgb, var(--accent) 13%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in srgb, var(--accent) 13%, transparent) 1px, transparent 1px),
                linear-gradient(color-mix(in srgb, var(--accent) 6%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in srgb, var(--accent) 6%, transparent) 1px, transparent 1px),
                radial-gradient(130% 120% at 50% 30%, #fff, var(--soft));
            background-size: auto, 112px 112px, 112px 112px, 28px 28px, 28px 28px, auto; }
        .pgal .main[data-pdp-open] { cursor: zoom-in; }
        .pgal .main img { width: 100%; height: 100%; object-fit: cover; }
        .pgal .main .fig { position: absolute; left: 22px; bottom: 18px; z-index: 3; background: color-mix(in srgb, var(--bg) 72%, transparent); backdrop-filter: blur(3px); padding: 4px 9px; border-radius: 7px; }
        /* placeholder object when no photo — the machined cobalt bottle */
        .pgal .main.bloomph, .pgal .main.ph { background-image:
                radial-gradient(115% 85% at 50% 32%, rgba(255, 255, 255, .92), rgba(255, 255, 255, 0) 64%),
                linear-gradient(color-mix(in srgb, var(--accent) 13%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in srgb, var(--accent) 13%, transparent) 1px, transparent 1px),
                linear-gradient(color-mix(in srgb, var(--accent) 6%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in srgb, var(--accent) 6%, transparent) 1px, transparent 1px),
                radial-gradient(130% 120% at 50% 30%, #fff, var(--soft)); }
        .pgal .main .obj { position: relative; z-index: 2; width: 120px; height: 340px; border-radius: 60px 60px 18px 18px; overflow: hidden;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .16), rgba(255, 255, 255, 0) 26%, rgba(6, 8, 14, .18) 94%),
                linear-gradient(90deg,
                    color-mix(in srgb, var(--pcolor) 68%, #06080e) 0%,
                    color-mix(in srgb, var(--pcolor) 88%, #fff) 24%,
                    color-mix(in srgb, var(--pcolor) 48%, #fff) 34%,
                    var(--pcolor) 56%,
                    color-mix(in srgb, var(--pcolor) 76%, #06080e) 84%,
                    color-mix(in srgb, var(--pcolor) 54%, #06080e) 100%);
            box-shadow: 0 40px 70px -36px color-mix(in srgb, var(--pcolor) 60%, transparent); }
        .pgal .main .obj::before { content: ""; position: absolute; z-index: 2; top: 0; left: 0; right: 0; height: 28px;
            background: linear-gradient(90deg, color-mix(in srgb, var(--pcolor) 52%, #000), color-mix(in srgb, var(--pcolor) 78%, #000) 30%, color-mix(in srgb, var(--pcolor) 44%, #000));
            box-shadow: 0 1px 0 rgba(255, 255, 255, .18); }
        .pgal .main .obj::after { content: ""; position: absolute; z-index: 1; left: 24%; top: 10%; width: 11%; height: 58%; border-radius: 99px; background: linear-gradient(180deg, rgba(255, 255, 255, .85), rgba(255, 255, 255, .05)); filter: blur(2px); }
        @keyframes pdpSettle { from { opacity: 0; transform: translateY(22px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pdpFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-7px); } }
        .pgal .main .obj { animation: pdpSettle .8s cubic-bezier(.16, .84, .3, 1) both, pdpFloat 7.5s ease-in-out 2s infinite; }
        /* dimension lines around the placeholder bottle */
        .pgal .main .dim-v { height: 340px; left: calc(50% + 92px); top: 50%; transform: translateY(-50%); }
        .pgal .main .dim-h { width: 120px; left: 50%; top: calc(50% + 194px); transform: translateX(-50%); }
        @keyframes pdpChromeIn { from { opacity: 0; } to { opacity: 1; } }
        .pgal .main .dim, .pgal .main .xmark, .pgal .main .fig { animation: pdpChromeIn .6s ease .5s both; }
        @media (prefers-reduced-motion: reduce) { .pgal .main .obj, .pgal .main .dim, .pgal .main .xmark, .pgal .main .fig { animation: none !important; } }
        .pgal .thumbs { display: flex; gap: 12px; margin-top: 14px; }
        .pgal .thumbs .pdp-thumb { flex: 1; height: 90px; border-radius: 12px; cursor: pointer; opacity: .6; overflow: hidden; border: none; padding: 0; background: var(--soft); transition: opacity .2s ease, outline-color .2s ease; outline: 2px solid transparent; outline-offset: 2px; }
        .pgal .thumbs .pdp-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .pgal .thumbs .pdp-thumb.on, .pgal .thumbs .pdp-thumb:hover { opacity: 1; }
        .pgal .thumbs .pdp-thumb.on { outline-color: var(--accent); }

        /* info column */
        .pinfo .crumb { font-family: var(--mono); font-size: 12px; color: var(--muted); margin-bottom: 14px; }
        .pinfo .crumb a:hover { color: var(--accent); }
        .pinfo .cat { font-family: var(--mono); font-size: 12px; color: var(--accent); }
        .pinfo h1 { font-family: var(--display); font-weight: 800; font-size: clamp(36px, 4.4vw, 54px); line-height: 1.02; letter-spacing: -.02em; margin: 8px 0 8px; }
        .pinfo h1 em { font-style: normal; color: var(--accent); }
        .pinfo .price { font-family: var(--display); font-weight: 700; font-size: 30px; font-variant-numeric: tabular-nums; margin: 16px 0; }
        .pinfo .stock { font-family: var(--mono); font-size: 12px; color: var(--muted); margin: -6px 0 18px; display: inline-flex; align-items: center; gap: 8px; }
        .pinfo .stock .dot { width: 8px; height: 8px; border-radius: 99px; background: var(--accent); animation: pip 2.4s ease-out infinite; }
        @media (prefers-reduced-motion: reduce) { .pinfo .stock .dot { animation: none; } }
        .pinfo p.desc { color: var(--muted); margin-bottom: 22px; max-width: 46ch; }
        .pinfo ul.perks { list-style: none; margin-bottom: 24px; }
        .pinfo ul.perks li { padding: 11px 0; border-bottom: 1px solid var(--line); font-size: 14px; display: flex; gap: 10px; }
        .pinfo ul.perks li::before { content: "◆"; color: var(--accent); font-size: 10px; line-height: 1.6; }

        .opt { margin-bottom: 20px; }
        .opt .ol { font-family: var(--mono); font-size: 11px; color: var(--muted); margin-bottom: 10px; }

        /* ===== Fullscreen image viewer (lightbox) — Forma styling.
           Esc closes, ←/→ navigate, focus is trapped while open. ===== */
        .pdp-lightbox { position: fixed; inset: 0; z-index: 200; background: rgba(20, 22, 28, .88); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; padding: 6vh 6vw; animation: lbFade .22s ease; }
        .pdp-lightbox[hidden] { display: none; }
        @keyframes lbFade { from { opacity: 0; } to { opacity: 1; } }
        @keyframes lbPop { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: scale(1); } }
        .pdp-lightbox .lb-stage { position: relative; margin: 0; display: flex; flex-direction: column; align-items: center; gap: 16px; max-width: 92vw; animation: lbPop .26s cubic-bezier(.2, .8, .2, 1); }
        .pdp-lightbox #pdp-lightbox-image { max-width: 86vw; max-height: 80vh; object-fit: contain; border-radius: 12px; background: var(--card); padding: 10px; box-shadow: 0 30px 70px -26px rgba(0, 0, 0, .65); }
        .pdp-lightbox .lb-count { font-family: var(--mono); font-size: 13px; color: #fff; letter-spacing: .04em; }
        .pdp-lightbox .lb-close { position: absolute; top: 24px; right: 26px; width: 50px; height: 50px; border-radius: 12px; background: var(--card); color: var(--ink); border: none; box-shadow: 0 10px 24px -10px rgba(0, 0, 0, .6); font-size: 17px; line-height: 1; display: grid; place-items: center; transition: background-color .22s ease, color .22s ease; }
        .pdp-lightbox .lb-close:hover { background: var(--accent); color: #fff; }
        .pdp-lightbox .lb-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 56px; height: 56px; border-radius: 12px; background: var(--card); color: var(--ink); border: none; box-shadow: 0 10px 24px -10px rgba(0, 0, 0, .6); font-family: var(--display); font-size: 30px; line-height: 1; display: grid; place-items: center; transition: background-color .22s ease, color .22s ease; }
        .pdp-lightbox .lb-prev { left: 24px; }
        .pdp-lightbox .lb-next { right: 24px; }
        .pdp-lightbox .lb-nav:hover { background: var(--accent); color: #fff; }
        @media (prefers-reduced-motion: reduce) {
            .pdp-lightbox, .pdp-lightbox .lb-stage { animation: none; }
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
                <div class="pgal">
                    <div class="main {{ $heroImage ? '' : 'bloomph' }}" @if ($heroImage) data-pdp-open @endif>
                        @if ($heroImage)
                            <img id="pdp-main-image" src="{{ $heroImage }}" alt="{{ $product->name }}">
                        @else
                            <i class="xmark" style="top: 16px; left: 16px;" aria-hidden="true"></i>
                            <i class="xmark" style="top: 16px; right: 16px;" aria-hidden="true"></i>
                            <div class="obj" aria-hidden="true"></div>
                            <div class="dim dim-v" aria-hidden="true"><span>H&nbsp;260&nbsp;MM</span></div>
                            <div class="dim dim-h" aria-hidden="true"><span>Ø&nbsp;73&nbsp;MM</span></div>
                        @endif
                        <div class="fig" aria-hidden="true"><b>FIG. 01</b> — {{ $product->name }}</div>
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
                    <span class="kicker">// {{ __('site.storefront.forma.related_eyebrow') }}</span>
                    <h2>{{ __('site.storefront.featured.browse_all') }}</h2>
                    <a class="more" href="/">{{ __('site.storefront.featured.browse_all') }} →</a>
                </div>
                <div class="blooms">
                    @foreach ($related as $rp)
                        @include('themes.forma._card', ['product' => $rp, 'badge' => null])
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
