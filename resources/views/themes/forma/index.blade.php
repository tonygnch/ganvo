@extends('themes.forma.layout')

@section('content')
    @php
        $isFiltered = ($filters['q'] ?? null)
            || ($filters['category'] ?? null)
            || ($filters['min_price'] ?? null) !== null
            || ($filters['max_price'] ?? null) !== null
            || ($filters['in_stock'] ?? false)
            || (($filters['sort'] ?? 'newest') !== 'newest')
            || $products->currentPage() > 1;

        $csHero = $store->heroBanner();
        $heroProduct = $products->first();
        $heroImageUrl = $csHero['enabled'] && $csHero['image_path']
            ? \Illuminate\Support\Facades\Storage::url($csHero['image_path'])
            : null;
        $heroProductImg = $heroProduct && $heroProduct->image_path
            ? \Illuminate\Support\Facades\Storage::url($heroProduct->image_path)
            : ($heroImageUrl ?: null);
        // The accessory grid: everything after the lead product.
        $accessories = $products->skip(1)->take(8)->values();
    @endphp

    <style>
        /* ===== CONFIGURATOR HERO — single product stage + spec panel ===== */
        .config { display: grid; grid-template-columns: 1.15fr .85fr; gap: 0; min-height: 84vh; border-bottom: 1px solid var(--line); }
        /* The stage is an engineering drawing: a faint cobalt graph grid
           (fine 28px pitch under a 112px major pitch), washed out towards the
           centre so the object sits in clean air. */
        .config .stage { position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden;
            background:
                radial-gradient(115% 85% at 50% 32%, rgba(255, 255, 255, .92), rgba(255, 255, 255, 0) 64%),
                linear-gradient(color-mix(in srgb, var(--accent) 13%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in srgb, var(--accent) 13%, transparent) 1px, transparent 1px),
                linear-gradient(color-mix(in srgb, var(--accent) 6%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in srgb, var(--accent) 6%, transparent) 1px, transparent 1px),
                radial-gradient(130% 120% at 50% 30%, #fff, var(--soft));
            background-size: auto, 112px 112px, 112px 112px, 28px 28px, 28px 28px, auto; }
        /* merchant knob: blueprint grid off — the stage becomes clean air */
        .config .stage.no-grid { background: radial-gradient(130% 120% at 50% 30%, #fff, var(--soft)); background-size: auto; }
        .band .shot.no-grid::after { display: none; }
        .config .stage > .xmark { z-index: 3; }
        .config .stage .spin { position: absolute; width: 330px; height: 330px; border: 1px dashed color-mix(in srgb, var(--accent) 34%, var(--line2)); border-radius: 50%; animation: spin 30s linear infinite; }
        .config .stage .spin::before { content: ""; position: absolute; top: -5px; left: 50%; width: 9px; height: 9px; border-radius: 50%; background: var(--accent); transform: translateX(-50%); }
        @keyframes spin { to { transform: rotate(360deg); } }
        /* The product "object": a machined cobalt bottle, or the product photo
           if present. Cylindrical shading — dark rim, bright core band, deep
           falloff — with a capped crown and a blurred specular strip. */
        .config .stage .object { position: relative; z-index: 2; width: 170px; height: 420px; border-radius: 86px 86px 26px 26px; overflow: hidden;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .16), rgba(255, 255, 255, 0) 26%, rgba(6, 8, 14, .18) 94%),
                linear-gradient(90deg,
                    color-mix(in srgb, var(--pcolor) 68%, #06080e) 0%,
                    color-mix(in srgb, var(--pcolor) 88%, #fff) 24%,
                    color-mix(in srgb, var(--pcolor) 48%, #fff) 34%,
                    var(--pcolor) 56%,
                    color-mix(in srgb, var(--pcolor) 76%, #06080e) 84%,
                    color-mix(in srgb, var(--pcolor) 54%, #06080e) 100%);
            box-shadow: inset 0 -2px 0 rgba(255, 255, 255, .1), 0 50px 90px -40px color-mix(in srgb, var(--pcolor) 60%, transparent); }
        /* cap — a darker machined crown clipped by the dome radius */
        .config .stage .object::before { content: ""; position: absolute; z-index: 2; top: 0; left: 0; right: 0; height: 38px;
            background: linear-gradient(90deg, color-mix(in srgb, var(--pcolor) 52%, #000), color-mix(in srgb, var(--pcolor) 78%, #000) 30%, color-mix(in srgb, var(--pcolor) 44%, #000));
            box-shadow: 0 1px 0 rgba(255, 255, 255, .18); }
        /* specular highlight — tall soft strip on the lit side */
        .config .stage .object::after { content: ""; position: absolute; z-index: 1; left: 24%; top: 10%; width: 11%; height: 58%; border-radius: 99px; background: linear-gradient(180deg, rgba(255, 255, 255, .85), rgba(255, 255, 255, .05)); filter: blur(3px); }
        .config .stage .object img { position: relative; z-index: 3; width: 100%; height: 100%; object-fit: cover; }
        .config .stage .object.has-img::after, .config .stage .object.has-img::before { display: none; }
        /* soft contact shadow under the object — breathes against the float */
        .config .stage .obj-shadow { position: absolute; z-index: 1; left: 50%; top: calc(50% + 196px); width: 200px; height: 36px; transform: translateX(-50%); border-radius: 50%; filter: blur(7px);
            background: radial-gradient(50% 50% at 50% 50%, color-mix(in srgb, var(--pcolor) 34%, rgba(20, 22, 28, .3)), transparent 72%); }
        /* motion: the bottle settles on load, then floats — barely */
        @keyframes objSettle { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes objFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-9px); } }
        @keyframes shadowIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes shadowFloat { 0%, 100% { transform: translateX(-50%) scaleX(1); opacity: 1; } 50% { transform: translateX(-50%) scaleX(.9); opacity: .72; } }
        .config .stage .object { animation: objSettle .9s cubic-bezier(.16, .84, .3, 1) both, objFloat 7.5s ease-in-out 2.4s infinite; }
        .config .stage .obj-shadow { animation: shadowIn .9s ease both, shadowFloat 7.5s ease-in-out 2.4s infinite; }
        /* dimension lines annotating the object — H right of it, Ø beneath */
        .config .stage .dim-v { height: 420px; left: calc(50% + 130px); top: 50%; transform: translateY(-50%); }
        .config .stage .dim-h { width: 170px; left: 50%; top: calc(50% + 244px); transform: translateX(-50%); }
        .config .stage .fig { position: absolute; left: 30px; bottom: 28px; z-index: 3; }
        .config .stage .badge { position: absolute; z-index: 3; left: 30px; top: 30px; font-family: var(--mono); font-size: 11px; color: var(--muted); border: 1px solid var(--line2); background: rgba(255, 255, 255, .6); backdrop-filter: blur(4px); padding: 6px 12px; border-radius: 99px; }
        .config .stage .price-fly { position: absolute; z-index: 3; right: 30px; bottom: 30px; background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 14px 18px; box-shadow: 0 18px 40px -20px rgba(20, 22, 28, .3); }
        .config .stage .price-fly .mono { color: var(--muted); }
        .config .stage .price-fly .v { font-family: var(--display); font-weight: 800; font-size: 22px; font-variant-numeric: tabular-nums; }
        /* stage chrome fades up after the object lands */
        @keyframes chromeIn { from { opacity: 0; } to { opacity: 1; } }
        .config .stage .badge, .config .stage .price-fly, .config .stage .fig { animation: chromeIn .6s ease .45s both; }
        .config .stage .dim, .config .stage > .xmark { animation: chromeIn .7s ease .7s both; }

        .config .panel { padding: 54px 56px; display: flex; flex-direction: column; justify-content: center; background: var(--card); }
        .config .panel .k { font-family: var(--mono); font-size: 12px; letter-spacing: .04em; color: var(--accent); }
        .config .panel h1 { font-family: var(--display); font-weight: 800; font-size: clamp(36px, 4.4vw, 60px); line-height: .98; letter-spacing: -.03em; margin: 12px 0 10px; }
        .config .panel .lede { color: var(--muted); max-width: 44ch; margin-bottom: 8px; }
        /* one orchestrated page-load reveal: panel children step up in turn */
        @keyframes panIn { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }
        .config .panel > * { animation: panIn .65s cubic-bezier(.2, .8, .2, 1) both; }
        .config .panel > *:nth-child(1) { animation-delay: .08s; }
        .config .panel > *:nth-child(2) { animation-delay: .16s; }
        .config .panel > *:nth-child(3) { animation-delay: .24s; }
        .config .panel > *:nth-child(4) { animation-delay: .34s; }
        @media (prefers-reduced-motion: reduce) {
            .config .stage .object, .config .stage .obj-shadow, .config .stage .badge, .config .stage .price-fly,
            .config .stage .fig, .config .stage .dim, .config .stage > .xmark, .config .panel > * { animation: none !important; }
        }
        /* numbered configurator steps — the spec-sheet build flow */
        .config .step { border-top: 1px solid var(--line); padding: 20px 0; }
        .config .step .lab { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .config .step .lab .n { font-family: var(--mono); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
        .config .step .lab .val { font-weight: 600; font-size: 14px; font-family: var(--display); }
        .config .step .lab .val .pr { font-family: var(--display); font-weight: 800; }
        .config .panel .vp-host { padding: 0; margin: 0; border: none; }
        .config .addbar { display: flex; gap: 12px; align-items: center; margin-top: 4px; }

        /* spec strip — 4-up animated readout */
        .specrow { display: grid; grid-template-columns: repeat(4, 1fr); border-bottom: 1px solid var(--line); }
        .specrow .s { padding: 34px 28px; border-right: 1px solid var(--line); text-align: center; transition: background-color .13s ease; }
        .specrow .s:hover { background: color-mix(in srgb, var(--accent) 3%, transparent); }
        .specrow .s:last-child { border-right: none; }
        .specrow .s .v { font-family: var(--display); font-weight: 800; font-size: clamp(26px, 3vw, 42px); letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
        .specrow .s .l { font-family: var(--mono); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-top: 8px; }

        /* ===== SPEC TABLE — the tech-sheet, presented like hardware =====
           Replaces the generic 3-up icon strip. A two-column technical
           datasheet: mono spec keys on the left, values on the right. */
        .sheet { display: grid; grid-template-columns: 1fr 1.2fr; gap: 0; border: 1px solid var(--line); border-radius: 22px; overflow: hidden; background: var(--card); }
        .sheet .lead { padding: 48px 44px; border-right: 1px solid var(--line); display: flex; flex-direction: column; justify-content: center; background: linear-gradient(180deg, var(--card), color-mix(in srgb, var(--accent) 4%, var(--card))); }
        .sheet .lead .k { font-family: var(--mono); font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--accent); }
        .sheet .lead h3 { font-family: var(--display); font-weight: 800; font-size: clamp(26px, 3vw, 38px); letter-spacing: -.02em; line-height: 1.04; margin: 14px 0 12px; }
        .sheet .lead p { color: var(--muted); font-size: 14.5px; max-width: 34ch; }
        .sheet .lead .doc { margin-top: 26px; font-family: var(--mono); font-size: 10.5px; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); }
        .specsheet { display: flex; flex-direction: column; }
        .specsheet .row { display: grid; grid-template-columns: 200px 1fr; gap: 18px; align-items: baseline; padding: 20px 44px; border-bottom: 1px solid var(--line); transition: background-color .12s ease; }
        .specsheet .row:hover { background: color-mix(in srgb, var(--accent) 4%, transparent); }
        .specsheet .row:last-child { border-bottom: none; }
        .specsheet .row .rk { font-family: var(--mono); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
        .specsheet .row .rk::before { content: "// "; color: var(--accent); }
        .specsheet .row .rv { font-family: var(--display); font-weight: 600; font-size: 15.5px; color: var(--ink); font-variant-numeric: tabular-nums; }
        @media (max-width: 540px) { .specsheet .row { grid-template-columns: 1fr; gap: 5px; padding: 16px 24px; } .sheet .lead { padding: 36px 24px; } }

        /* band — layered cobalt, not a flat slab: a hot corner glow over a
           deep falloff, with the drafting grid pinned to the shot */
        .band { color: #fff; border-radius: 24px; padding: 72px 60px; margin: 100px 0; display: grid; grid-template-columns: 1.1fr .9fr; gap: 40px; align-items: center; overflow: hidden; position: relative;
            background: radial-gradient(130% 170% at 88% -30%, color-mix(in srgb, var(--accent) 62%, #fff) 0%, var(--accent) 46%, color-mix(in srgb, var(--accent) 74%, #060a1c) 100%); }
        .band .bk { font-family: var(--mono); font-size: 11px; letter-spacing: .1em; text-transform: uppercase; opacity: .82; margin-bottom: 14px; }
        .band h3 { font-family: var(--display); font-weight: 800; font-size: clamp(30px, 4vw, 52px); letter-spacing: -.02em; line-height: 1.02; }
        .band p { opacity: .86; max-width: 40ch; margin-top: 14px; }
        .band .shot { height: 300px; border-radius: 18px; background: rgba(255, 255, 255, .12); border: 1px solid rgba(255, 255, 255, .25); position: relative; overflow: hidden; }
        .band .shot::after { content: ""; position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,.14) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.14) 1px, transparent 1px); background-size: 28px 28px; }
        .band .shot .obj { position: absolute; z-index: 2; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 90px; height: 220px; border-radius: 46px 46px 14px 14px; background: linear-gradient(90deg, rgba(255,255,255,.5), rgba(255,255,255,.95) 30%, rgba(255,255,255,.65) 60%, rgba(255,255,255,.4)); box-shadow: 0 30px 60px -30px rgba(0,0,0,.4); }
        .band .shot .fig { position: absolute; z-index: 3; left: 16px; bottom: 13px; color: rgba(255, 255, 255, .75); }
        .band .shot .xmark::before, .band .shot .xmark::after { background: rgba(255, 255, 255, .5); }

        /* category pills — machined snap, 120ms */
        .pills { display: flex; gap: 10px; flex-wrap: wrap; margin: 30px 0 38px; }
        .pills .pill { border: 1px solid var(--line); background: var(--card); border-radius: 99px; padding: 10px 18px; font-size: 13px; font-weight: 600; color: var(--ink); transition: background-color .12s ease, color .12s ease, border-color .12s ease, transform .12s ease; }
        .pills .pill.on, .pills .pill:hover { background: var(--ink); border-color: var(--ink); color: #fff; }
        .pills .pill:active { transform: translateY(1px); }
        @media (prefers-reduced-motion: reduce) { .pills .pill:active { transform: none; } }

        .home-empty { text-align: center; padding: 70px 24px; font-family: var(--mono); font-size: 14px; color: var(--muted); }

        /* Collection rails — Forma restyle of the shared .cs-* partial. */
        .wrap .cs-strip { position: relative; margin: 64px 0 0; padding: 0; }
        .wrap .cs-banner { display: none; }
        .wrap .cs-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin: 0 0 26px; flex-wrap: wrap; }
        .wrap .cs-head h2 { font-family: var(--display); font-weight: 800; font-size: clamp(26px, 3vw, 40px); letter-spacing: -.02em; line-height: 1.05; }
        .wrap .cs-head p { color: var(--muted); font-size: 14px; max-width: 50ch; margin-top: 4px; }
        .wrap .cs-view-all { font-family: var(--mono); font-size: 12px; color: var(--accent); border: 1px solid var(--line2); border-radius: 99px; padding: 9px 18px; background: var(--card); white-space: nowrap; transition: border-color .2s ease; }
        .wrap .cs-view-all:hover { border-color: var(--accent); }
        .wrap .cs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 22px; }
        .wrap .cs-card { display: flex; flex-direction: column; color: inherit; transition: transform .2s ease; }
        .wrap .cs-card:hover { transform: translateY(-4px); }
        .wrap .cs-img { height: 230px; border-radius: 16px; overflow: hidden; background: var(--soft); margin-bottom: 14px; }
        .wrap .cs-card:nth-child(even) .cs-img { background: radial-gradient(120% 120% at 50% 25%, color-mix(in srgb, var(--accent) 22%, #fff), color-mix(in srgb, var(--accent) 70%, var(--ink))); }
        .wrap .cs-img img { width: 100%; height: 100%; object-fit: cover; }
        .wrap .cs-meta { display: flex; flex-direction: column; gap: 3px; padding: 0; }
        .wrap .cs-name { font-family: var(--display); font-weight: 600; font-size: 16px; line-height: 1.2; }
        .wrap .cs-price { font-family: var(--display); font-weight: 600; font-size: 16px; font-variant-numeric: tabular-nums; color: var(--ink); }
        @media (prefers-reduced-motion: reduce) { .wrap .cs-card, .wrap .cs-card:hover { transform: none; } }

        @media (max-width: 1000px) {
            .config { grid-template-columns: 1fr; }
            .config .stage { min-height: 480px; }
            .config .stage .object { height: 320px; width: 132px; border-radius: 66px 66px 22px 22px; }
            .config .stage .obj-shadow { top: calc(50% + 150px); width: 160px; }
            .config .stage .dim-v { height: 320px; left: calc(50% + 104px); }
            .config .stage .dim-h { width: 132px; top: calc(50% + 192px); }
            .sheet { grid-template-columns: 1fr; }
            .sheet .lead { border-right: none; border-bottom: 1px solid var(--line); }
            .band { grid-template-columns: 1fr; }
            .specrow { grid-template-columns: 1fr 1fr; }
            .specrow .s:nth-child(2) { border-right: none; }
            .specrow .s:nth-child(-n+2) { border-bottom: 1px solid var(--line); }
        }
        @media (max-width: 540px) {
            .specrow { grid-template-columns: 1fr; }
            .specrow .s { border-right: none; border-bottom: 1px solid var(--line); }
            .specrow .s:last-child { border-bottom: none; }
        }
        @media (max-width: 680px) {
            .config .panel { padding: 40px 24px; }
            .wrap .cs-grid { grid-template-columns: repeat(2, 1fr); }
            .band { padding: 48px 30px; }
        }
    </style>

    <main>
        @if (! $isFiltered && $heroProduct)
            {{-- ===================== SINGLE-PRODUCT CONFIGURATOR ===================== --}}
            <section class="config">
                <div class="stage {{ $theme->on('blueprint_grid') ? '' : 'no-grid' }}">
                    {{-- registration marks — print-shop alignment crosses --}}
                    @if ($theme->on('crosshairs'))
                        <i class="xmark" style="top: 18px; left: 18px;" aria-hidden="true"></i>
                        <i class="xmark" style="top: 18px; right: 18px;" aria-hidden="true"></i>
                        <i class="xmark" style="bottom: 18px; left: 50%; margin-left: -8px;" aria-hidden="true"></i>
                    @endif
                    <div class="spin" aria-hidden="true"></div>
                    <div class="badge">// {{ $heroProduct->name }}</div>
                    <div class="obj-shadow" aria-hidden="true"></div>
                    <div class="object {{ $heroProductImg ? 'has-img' : '' }}" aria-hidden="true">
                        @if ($heroProductImg)<img src="{{ $heroProductImg }}" alt="">@endif
                    </div>
                    {{-- dimension lines — the engineering-drawing signature --}}
                    @if ($theme->on('dim_lines'))
                        <div class="dim dim-v" aria-hidden="true"><span>H&nbsp;260&nbsp;MM</span></div>
                        <div class="dim dim-h" aria-hidden="true"><span>Ø&nbsp;73&nbsp;MM</span></div>
                    @endif
                    @if ($theme->on('fig_caption'))
                        <div class="fig" aria-hidden="true"><b>{{ $theme->label('fig_caption') }} 01</b> — {{ $heroProduct->name }}</div>
                    @endif
                    <div class="price-fly">
                        <div class="mono">{{ __('site.storefront.shop_all.eyebrow') }}</div>
                        <div class="v">@money($heroProduct->price_cents)</div>
                    </div>
                </div>
                <div class="panel">
                    <div class="k">// {{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</div>
                    <h1>@if ($csHero['subtitle'] !== ''){{ $csHero['subtitle'] }}@else{{ $heroProduct->name }}@endif</h1>
                    <p class="lede">{{ $heroProduct->description ?: __('site.storefront.hero.sub') }}</p>

                    <form method="post" action="/cart/add/{{ $heroProduct->slug }}">
                        @csrf
                        {{-- Step 01 — the build spec (variant picker when present) --}}
                        @if ($heroProduct->hasVariants())
                            <div class="step">
                                <div class="lab"><span class="n">01 / {{ __('site.storefront.product.choose_variant') }}</span></div>
                                <div class="vp-host">
                                    @include('storefront.partials.variant-picker', ['product' => $heroProduct])
                                </div>
                            </div>
                        @endif
                        {{-- Step 02 — unit price readout --}}
                        <div class="step">
                            <div class="lab">
                                <span class="n">{{ $heroProduct->hasVariants() ? '02' : '01' }} / {{ __('site.storefront.shop_all.eyebrow') }}</span>
                                <span class="val"><span class="pr" data-vp-price>@money($heroProduct->price_cents)</span></span>
                            </div>
                        </div>
                        <div class="addbar">
                            <a class="btn outline" href="/products/{{ $heroProduct->slug }}">{{ __('site.storefront.hero.cta_secondary') }}</a>
                            <button type="submit" class="btn lg" style="flex:1" data-vp-submit @if ($heroProduct->hasVariants()) disabled @endif>
                                {{ __('site.storefront.product.add_to_cart') }} — <span data-vp-submit-price>@money($heroProduct->price_cents)</span>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <div class="wrap">
                {{-- 4-up instrument readout — animated counts where numeric --}}
                @if ($theme->on('spec_row'))
                <div class="specrow reveal">
                    <div class="s"><div class="v" data-count="24" data-suffix="h">0</div><div class="l">{{ __('site.storefront.value_props.shipping_title') }}</div></div>
                    <div class="s"><div class="v" data-count="30" data-suffix="d">0</div><div class="l">{{ __('site.storefront.value_props.returns_title') }}</div></div>
                    <div class="s"><div class="v">{{ $theme->copy('spec_material') }}</div><div class="l">{{ __('site.storefront.value_props.checkout_title') }}</div></div>
                    <div class="s"><div class="v" data-count="{{ max(1, $products->total()) }}">0</div><div class="l">{{ __('site.storefront.shop_all.h2') }}</div></div>
                </div>
                @endif

                {{-- SPEC TABLE — the technical datasheet for the hero product.
                     Presented like hardware: mono keys, display values. --}}
                @if ($theme->on('spec_sheet'))
                <div class="sec-head reveal">
                    <span class="kicker">// {{ __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</span>
                    <h2>{!! __('site.storefront.hero.headline', ['tenant' => '<em>' . e($tenant->name) . '</em>']) !!}</h2>
                </div>
                <div class="sheet reveal">
                    <div class="lead">
                        <div class="k">// {{ $heroProduct->name }}</div>
                        <h3>{{ $tenant->name }}</h3>
                        <p>{{ $heroProduct->description ?: __('site.storefront.hero.sub') }}</p>
                        <div class="doc">SPEC · REV {{ date('y') }}.{{ $heroProduct->id }}</div>
                    </div>
                    <div class="specsheet">
                        <div class="row"><div class="rk">{{ __('site.storefront.shop_all.eyebrow') }}</div><div class="rv" data-vp-price>@money($heroProduct->price_cents)</div></div>
                        <div class="row"><div class="rk">{{ __('site.storefront.value_props.shipping_title') }}</div><div class="rv">{{ __('site.storefront.value_props.shipping_sub') }}</div></div>
                        <div class="row"><div class="rk">{{ __('site.storefront.value_props.returns_title') }}</div><div class="rv">{{ __('site.storefront.value_props.returns_sub') }}</div></div>
                        <div class="row"><div class="rk">{{ __('site.storefront.value_props.checkout_title') }}</div><div class="rv">{{ __('site.storefront.value_props.checkout_sub') }}</div></div>
                    </div>
                </div>
                @endif

                @if ($theme->on('cobalt_band'))
                <section class="band reveal">
                    <div>
                        <div class="bk">// {{ __('site.storefront.shop_all.eyebrow') }}</div>
                        <h3>{{ $tenant->name }}</h3>
                        <p>{{ $theme->copy('band_body') }}</p>
                    </div>
                    <div class="shot {{ $theme->on('blueprint_grid') ? '' : 'no-grid' }}" aria-hidden="true">
                        @if ($theme->on('crosshairs'))<i class="xmark" style="top: 12px; right: 12px;"></i>@endif
                        <div class="obj"></div>
                        @if ($theme->on('fig_caption'))<div class="fig"><b>{{ $theme->label('fig_caption') }} 02</b></div>@endif
                    </div>
                </section>
                @endif
            </div>
        @endif

        <div class="wrap">
            {{-- Curated collections (only when the merchant features them) --}}
            @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
                @include('storefront.partials.collection-strips')
            @endif

            {{-- Shop all — the catalogue / accessories --}}
            <div class="sec-head reveal" id="shop">
                <span class="kicker">// {{ __('site.storefront.shop_all.eyebrow') }}</span>
                <h2>{{ $theme->copy('shop_heading') }}</h2>
            </div>

            {{-- Category pills (replaces the generic search/sort/price toolbar). --}}
            @if ($categories->isNotEmpty())
                <div class="pills reveal">
                    <a href="/" class="pill {{ ! ($filters['category'] ?? null) ? 'on' : '' }}">{{ __('site.storefront.controls.category_all') }}</a>
                    @foreach ($categories as $cat)
                        <a href="/?category={{ $cat->slug }}" class="pill {{ ($filters['category'] ?? null) === $cat->slug ? 'on' : '' }}">{{ $cat->name }}</a>
                    @endforeach
                </div>
            @endif

            @if ($products->isEmpty())
                <div class="home-empty reveal">{{ __('site.storefront.no_products') }}</div>
            @else
                @php
                    // On the unfiltered home, the lead product already headlines the
                    // configurator — show the remaining catalogue here. When filtered,
                    // show the full result set.
                    $gridProducts = (! $isFiltered && $heroProduct) ? $accessories : $products;
                @endphp
                @if ($gridProducts->isNotEmpty())
                    <div class="blooms">
                        @foreach ($gridProducts as $product)
                            @include('themes.forma._card', ['product' => $product, 'badge' => null])
                        @endforeach
                    </div>
                @endif
                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>

    @push('scripts')
        <script>
            // Spec-row count-up: animate [data-count] readouts once they
            // scroll into view (respects reduced-motion via the static path).
            (function () {
                var nums = [].slice.call(document.querySelectorAll('.specrow [data-count]'));
                if (! nums.length) return;
                var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                function fmt(n) { return n >= 1000 ? n.toLocaleString() : String(n); }
                function run(el) {
                    var to = parseInt(el.getAttribute('data-count'), 10) || 0;
                    var suf = el.getAttribute('data-suffix') || '';
                    if (reduce) { el.textContent = fmt(to) + suf; return; }
                    var t0 = null, dur = 1200;
                    function step(ts) {
                        if (! t0) t0 = ts;
                        var p = Math.min((ts - t0) / dur, 1);
                        el.textContent = fmt(Math.round(p * to)) + suf;
                        if (p < 1) requestAnimationFrame(step);
                    }
                    requestAnimationFrame(step);
                }
                if (! ('IntersectionObserver' in window)) { nums.forEach(run); return; }
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } });
                }, { threshold: 0.4 });
                nums.forEach(function (el) { io.observe(el); });
            })();
        </script>
    @endpush
@endsection
