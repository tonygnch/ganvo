@extends('themes.kiln.layout')

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
        $heroImageUrl = $csHero['enabled'] && $csHero['image_path']
            ? \Illuminate\Support\Facades\Storage::url($csHero['image_path'])
            : null;
        // Two pieces fill the asymmetric hero panels (large + tall stacked).
        $panels = $products->take(2)->values();
        $panelA = $panels[0] ?? null;
        $panelB = $panels[1] ?? null;
        $panelAUrl = $heroImageUrl
            ?: ($panelA && $panelA->image_path ? \Illuminate\Support\Facades\Storage::url($panelA->image_path) : null);
        $panelBUrl = $panelB && $panelB->image_path ? \Illuminate\Support\Facades\Storage::url($panelB->image_path) : null;
    @endphp

    <style>
        /* ===================================================================
           Kiln HOME — quiet studio/gallery editorial. The signature is its
           rhythm: an ASYMMETRIC split hero with large stone panels, a quiet
           stats band, a LEFT-aligned catalogue, a centred maker statement,
           a two-column process split with numbered steps, then a hairline
           newsletter. Deliberately NOT a centred big-serif hero + icon strip.
           =================================================================== */

        /* hero — off-centre type column beside stacked stone panels */
        .khero { display: grid; grid-template-columns: 1.05fr 1.15fr; gap: 60px; align-items: center; padding: 70px 0 30px; position: relative; }
        /* signature: a large, ghosted "thrown rings" motif turning behind the type */
        .khero::before { content: ""; position: absolute; width: 460px; height: 460px; left: -180px; bottom: -70px; border-radius: 50%; pointer-events: none; background: repeating-radial-gradient(circle, transparent 0 21px, color-mix(in srgb, var(--ink) 10%, transparent) 21px 22px); -webkit-mask-image: radial-gradient(circle at 50% 50%, #000 18%, transparent 70%); mask-image: radial-gradient(circle at 50% 50%, #000 18%, transparent 70%); }
        .khero .lede { max-width: 30ch; position: relative; }
        .khero .kicker { display: inline-block; color: var(--accent); margin-bottom: 22px; }
        .khero h1 { font-family: var(--serif); font-weight: 400; font-size: clamp(46px, 5.6vw, 84px); line-height: 1; letter-spacing: -.015em; margin-bottom: 24px; }
        .khero h1 em { font-style: italic; color: var(--accent); }
        .khero p { color: var(--muted); font-size: 17px; max-width: 40ch; margin-bottom: 30px; }
        .khero .cta { display: flex; gap: 14px; flex-wrap: wrap; }
        .khero .panels { display: grid; grid-template-columns: 1.25fr .85fr; grid-template-rows: 1fr 1fr; gap: 16px; height: 78vh; min-height: 520px; max-height: 720px; }
        .khero .panels .pn { position: relative; overflow: hidden; }
        .khero .panels .pn img { width: 100%; height: 100%; object-fit: cover; }
        .khero .panels .pa { grid-row: 1 / span 2; }
        .khero .panels .pb { grid-row: 1 / span 1; }
        .khero .panels .pc { grid-row: 2 / span 1; display: flex; flex-direction: column; justify-content: flex-end; padding: 22px; background: var(--soft); box-shadow: inset 0 0 0 1px var(--line); }
        .khero .panels .pc .q { font-family: var(--serif); font-style: italic; font-size: clamp(18px, 1.6vw, 24px); line-height: 1.35; }
        .khero .panels .pc .rings-mark { width: 40px; height: 40px; margin-bottom: auto; }
        .khero .panels .lab { position: absolute; left: 16px; bottom: 16px; font-family: var(--display); font-size: 10px; letter-spacing: .14em; text-transform: uppercase; color: #fff; mix-blend-mode: difference; }

        /* one orchestrated load reveal: the panels drift in as three weighted
           beats. The .reveal container only gates the IntersectionObserver —
           the children carry the motion. */
        .khero .panels.reveal { opacity: 1; transform: none; transition: none; }
        .khero .panels .pn { opacity: 0; transform: translateY(34px); transition: opacity 1.1s ease, transform 1.3s cubic-bezier(.19, .7, .16, 1); }
        .khero .panels.in .pn { opacity: 1; transform: none; }
        .khero .panels.in .pb { transition-delay: .18s; }
        .khero .panels.in .pc { transition-delay: .36s; }
        @media (prefers-reduced-motion: reduce) {
            .khero .panels .pn { opacity: 1 !important; transform: none !important; transition: none !important; }
        }
        @media (max-width: 1000px) { .khero::before { display: none; } }

        /* stats band — quiet horizontal rule of facts, placard-annotated */
        .kmeta { display: flex; justify-content: space-between; gap: 40px; flex-wrap: wrap; padding: 30px 0; margin-top: 46px; border-top: 1px solid var(--ink); border-bottom: 1px solid var(--line); }
        .kmeta span { display: flex; flex-direction: column; gap: 2px; }
        .kmeta b { color: var(--ink); font-family: var(--serif); font-weight: 400; font-size: 20px; }
        .kmeta i { font-family: var(--serif); font-style: italic; color: var(--muted); font-size: 14px; letter-spacing: .01em; }

        .home-empty { padding: 70px 24px; font-family: var(--serif); font-style: italic; font-size: 22px; color: var(--muted); }

        /* category pills — quiet outlined tags, left-aligned under the catalogue head */
        .pills { display: flex; gap: 8px; flex-wrap: wrap; margin: 0 0 40px; }
        .pills .pill { border: 1px solid var(--line); background: none; padding: 9px 18px; font-family: var(--display); font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); transition: border-color .2s ease, color .2s ease; }
        .pills .pill.on, .pills .pill:hover { border-color: var(--ink); color: var(--ink); }

        /* maker statement — centred literary aside, set like a gallery placard */
        .kmaker { max-width: 760px; margin: 120px auto; text-align: center; }
        .kmaker .rings-mark { margin: 0 auto 22px; }
        .kmaker .kicker { color: var(--accent); display: inline-block; }
        .kmaker p { font-family: var(--serif); font-style: italic; font-size: clamp(24px, 3vw, 34px); line-height: 1.4; margin-top: 20px; }
        .kmaker .sign { margin-top: 24px; font-family: var(--display); font-size: 12px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); display: flex; align-items: center; justify-content: center; gap: 16px; }
        .kmaker .sign::before, .kmaker .sign::after { content: ""; width: 44px; height: 1px; background: var(--line2); }

        /* process — asymmetric two-column split: large stone image + numbered steps */
        .kprocess { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin: 110px 0; align-items: stretch; }
        .kprocess .img { min-height: 540px; }
        .kprocess .txt { padding: 0 0 0 64px; display: flex; flex-direction: column; justify-content: center; }
        .kprocess h3 { font-family: var(--serif); font-weight: 400; font-size: clamp(30px, 3.8vw, 50px); letter-spacing: -.01em; margin: 14px 0 16px; line-height: 1.04; }
        .kprocess h3 em { font-style: italic; color: var(--accent); }
        .kprocess > .txt > p { color: var(--muted); max-width: 42ch; margin-bottom: 18px; }
        .kprocess .steps { margin: 20px 0 28px; }
        .kprocess .st { display: grid; grid-template-columns: 40px 1fr; gap: 14px; padding: 14px 0; border-top: 1px solid var(--line); }
        .kprocess .st .n { font-family: var(--serif); font-style: italic; font-size: 20px; color: var(--accent); }
        .kprocess .st b { display: block; font-size: 14px; letter-spacing: .06em; text-transform: uppercase; font-weight: 600; margin-bottom: 2px; }
        .kprocess .st p { font-size: 13px; color: var(--muted); margin: 0; }

        /* newsletter — hairline underline form */
        .knews { max-width: 520px; margin: 110px auto 0; text-align: center; }
        .knews h3 { font-family: var(--serif); font-weight: 400; font-size: clamp(26px, 3.2vw, 42px); }
        .knews h3 em { font-style: italic; color: var(--accent); }
        .knews p { color: var(--muted); margin: 12px 0 26px; }
        .knews form { display: flex; border-bottom: 1px solid var(--ink); max-width: 420px; margin: 0 auto; transition: border-color .3s ease; }
        .knews form:focus-within { border-color: var(--accent); }
        .knews input { flex: 1; border: none; background: none; padding: 14px 4px; font-family: inherit; font-size: 15px; color: var(--ink); }
        .knews input::placeholder { font-family: var(--serif); font-style: italic; color: var(--muted); }
        .knews input:focus { outline: none; }
        .knews button { background: none; border: none; font-family: var(--display); font-size: 11px; letter-spacing: .14em; text-transform: uppercase; font-weight: 600; color: var(--ink); transition: color .25s ease, letter-spacing .25s ease; }
        .knews button:hover { color: var(--accent); letter-spacing: .2em; }
        @media (prefers-reduced-motion: reduce) { .knews button { transition: none; } }

        /* Collection rails — Kiln restyle of the shared .cs-* partial: soft stone
           panels with flat gallery cards. `.wrap `-prefixed to beat the partial. */
        .wrap .cs-strip { position: relative; margin: 64px 0 0; padding: 40px 36px; border-radius: 4px; background: var(--soft); overflow: hidden; }
        .wrap .cs-banner { display: none; }
        .wrap .cs-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin: 0 0 26px; flex-wrap: wrap; border-bottom: 1px solid var(--line2); padding-bottom: 18px; }
        .wrap .cs-head h2 { font-family: var(--serif); font-weight: 400; font-size: clamp(26px, 3vw, 40px); line-height: 1.05; letter-spacing: -.01em; }
        .wrap .cs-head p { color: var(--muted); font-size: 14px; max-width: 50ch; margin-top: 4px; }
        .wrap .cs-view-all { font-family: var(--display); font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); border: 1px solid var(--line2); padding: 10px 20px; background: none; white-space: nowrap; transition: border-color .2s ease, color .2s ease; }
        .wrap .cs-view-all:hover { border-color: var(--ink); color: var(--ink); }
        .wrap .cs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); gap: 22px; }
        .wrap .cs-card { display: flex; flex-direction: column; color: inherit; }
        .wrap .cs-img { aspect-ratio: 1 / 1; overflow: hidden; background: var(--stone2); margin-bottom: 12px; transition: transform .5s cubic-bezier(.19, .7, .16, 1); }
        .wrap .cs-card:nth-child(even) .cs-img { background: var(--stone); }
        .wrap .cs-card:hover .cs-img { transform: translateY(-6px); }
        .wrap .cs-img img { width: 100%; height: 100%; object-fit: cover; }
        .wrap .cs-meta { display: flex; flex-direction: column; gap: 3px; text-align: left; padding: 0; }
        .wrap .cs-name { font-family: var(--serif); font-weight: 400; font-size: 17px; line-height: 1.2; }
        .wrap .cs-price { font-family: var(--display); font-weight: 600; font-size: 14px; font-variant-numeric: tabular-nums; color: var(--ink); }
        @media (prefers-reduced-motion: reduce) { .wrap .cs-card:hover .cs-img { transform: none; } }

        @media (max-width: 1000px) {
            .khero { grid-template-columns: 1fr; gap: 36px; }
            .khero .panels { height: auto; min-height: 0; max-height: none; grid-template-rows: 320px 220px; }
            .kprocess { grid-template-columns: 1fr; }
            .kprocess .txt { padding: 40px 0 0; }
            .kprocess .img { min-height: 320px; }
        }
        @media (max-width: 540px) {
            .khero .panels { grid-template-columns: 1fr; grid-template-rows: 280px 200px 200px; }
            .khero .panels .pa { grid-row: auto; }
            .khero .panels .pb { grid-row: auto; }
            .khero .panels .pc { grid-row: auto; }
            .kmeta { gap: 22px; }
            .wrap .cs-strip { padding: 28px 22px; }
            .wrap .cs-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>

    <main>
        @if (! $isFiltered)
            {{-- ===== Asymmetric editorial hero: off-centre type + stone panels ===== --}}
            <div class="wrap">
                <section class="khero">
                    <div class="lede">
                        <span class="kicker reveal">{{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.kiln.hero_eyebrow') }}</span>
                        <h1 class="reveal s1">@if ($csHero['subtitle'] !== ''){{ $csHero['subtitle'] }}@else{!! __('site.storefront.hero.headline', ['tenant' => '<em>' . e($tenant->name) . '</em>']) !!}@endif</h1>
                        <p class="reveal s2">{{ __('site.storefront.kiln.hero_lede') }}</p>
                        <div class="cta reveal s2">
                            <a class="btn" href="#shop">{{ $csHero['cta_label'] !== '' ? $csHero['cta_label'] : __('site.storefront.hero.cta_primary') }}</a>
                            <a class="btn outline" href="#shop">{{ __('site.storefront.hero.cta_secondary') }}</a>
                        </div>
                    </div>
                    <div class="panels reveal s1" aria-hidden="true">
                        <div class="pn pa {{ $panelAUrl ? '' : 'ph' }}">
                            @if ($panelAUrl)<img src="{{ $panelAUrl }}" alt="">@endif
                            @if ($panelA)<span class="lab">01 · {{ $panelA->name }}</span>@endif
                        </div>
                        <div class="pn pb {{ $panelBUrl ? '' : 'bloomph' }}">
                            @if ($panelBUrl)<img src="{{ $panelBUrl }}" alt="">@endif
                            @if ($panelB)<span class="lab">02 · {{ $panelB->name }}</span>@endif
                        </div>
                        <div class="pn pc">
                            <div class="rings-mark"></div>
                            <div class="q">{{ __('site.storefront.kiln.maker_sign') }}</div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ===== Quiet stats band ===== --}}
            <div class="wrap">
                <div class="kmeta reveal">
                    <span><b>{{ __('site.storefront.kiln.meta_1_b') }}</b><i>{{ __('site.storefront.kiln.meta_1_t') }}</i></span>
                    <span><b>{{ __('site.storefront.kiln.meta_2_b') }}</b><i>{{ __('site.storefront.kiln.meta_2_t') }}</i></span>
                    <span><b>{{ __('site.storefront.kiln.meta_3_b') }}</b><i>{{ __('site.storefront.kiln.meta_3_t') }}</i></span>
                    <span><b>{{ __('site.storefront.kiln.meta_4_b') }}</b><i>{{ __('site.storefront.kiln.meta_4_t') }}</i></span>
                </div>
            </div>
        @endif

        <div class="wrap">
            {{-- Curated collections (only when the merchant features them) --}}
            @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
                @include('storefront.partials.collection-strips')
            @endif

            {{-- ===== Catalogue — LEFT-aligned editorial head with view-all ===== --}}
            <div class="sec-head reveal" id="shop">
                <div class="htext">
                    <span class="kicker">{{ __('site.storefront.kiln.index_label') }}</span>
                    <h2>{!! __('site.storefront.kiln.recent_h2') !!}</h2>
                </div>
                <a class="more" href="#shop">{{ __('site.storefront.kiln.recent_more') }}</a>
            </div>

            {{-- Category pills (replaces the generic search/sort/price toolbar) --}}
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
                <div class="blooms">
                    @foreach ($products as $product)
                        @include('themes.kiln._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif

            @if (! $isFiltered)
                {{-- ===== Maker statement — centred literary aside ===== --}}
                <section class="kmaker reveal">
                    <div class="rings-mark" aria-hidden="true"></div>
                    <span class="kicker">{{ __('site.storefront.kiln.maker_kicker') }}</span>
                    <p>{{ __('site.storefront.kiln.maker_quote') }}</p>
                    <div class="sign">{{ __('site.storefront.kiln.maker_sign') }}</div>
                </section>

                {{-- ===== Process — asymmetric image + numbered steps ===== --}}
                <section class="kprocess">
                    <div class="img ph reveal"></div>
                    <div class="txt reveal s1">
                        <span class="kicker">{{ __('site.storefront.kiln.process_kicker') }}</span>
                        <h3>{{ __('site.storefront.kiln.process_h3') }}<br><em>{{ __('site.storefront.kiln.process_h3_em') }}</em> {{ __('site.storefront.kiln.process_h3_tail') }}</h3>
                        <p>{{ __('site.storefront.kiln.process_p') }}</p>
                        <div class="steps">
                            <div class="st"><span class="n">i</span><div><b>{{ __('site.storefront.kiln.step_1_b') }}</b><p>{{ __('site.storefront.kiln.step_1_p') }}</p></div></div>
                            <div class="st"><span class="n">ii</span><div><b>{{ __('site.storefront.kiln.step_2_b') }}</b><p>{{ __('site.storefront.kiln.step_2_p') }}</p></div></div>
                            <div class="st"><span class="n">iii</span><div><b>{{ __('site.storefront.kiln.step_3_b') }}</b><p>{{ __('site.storefront.kiln.step_3_p') }}</p></div></div>
                        </div>
                        <a class="btn outline" href="#shop">{{ __('site.storefront.kiln.process_cta') }}</a>
                    </div>
                </section>

                {{-- ===== Newsletter — hairline underline form ===== --}}
                <section class="knews reveal">
                    <h3>{{ __('site.storefront.kiln.news_h3') }} <em>{{ __('site.storefront.kiln.news_h3_em') }}</em></h3>
                    <p>{{ __('site.storefront.kiln.news_p') }}</p>
                    <form onsubmit="return false">
                        <input type="email" placeholder="{{ __('site.storefront.footer.newsletter_placeholder') }}" aria-label="{{ __('site.storefront.footer.newsletter_placeholder') }}">
                        <button type="submit">{{ __('site.storefront.footer.subscribe') }}</button>
                    </form>
                </section>
            @endif
        </div>
    </main>
@endsection
