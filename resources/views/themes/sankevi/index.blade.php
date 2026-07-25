@extends('themes.sankevi.layout')

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
        // Hero photography: the merchant's own banner wins, then the theme's
        // image slot (which ships the dawn yard), then the lead product's
        // photo. The slab is the whole hero — it is never left empty.
        $heroProduct = $products->firstWhere('image_path') ?: $products->first();
        $heroImageUrl = $csHero['enabled'] && $csHero['image_path']
            ? \Illuminate\Support\Facades\Storage::url($csHero['image_path'])
            : ($theme->image('hero_image')
                ?: ($heroProduct && $heroProduct->image_path
                    ? \Illuminate\Support\Facades\Storage::url($heroProduct->image_path)
                    : null));
        $storyImageUrl = $theme->image('story_image');
        // The workshop photograph we ship carries a printed white mount, which
        // has no business on a bark ground — the default asset is zoomed past
        // its border. A merchant's own upload is shown exactly as uploaded.
        $storyIsShipped = $storyImageUrl && str_contains($storyImageUrl, '/images/demo/sankevi/workshop');
        $forestImageUrl = $theme->image('forest_image');
        $csSeal = $theme->on('brand_seal') ? $theme->image('seal_image') : null;
        $tradeEmail = $tenant->contact_email ?: null;
        // Species guide — four rows of copy, and the gauge each one fills:
        // the density of that timber relative to the heaviest we cut.
        $species = [1 => 48, 2 => 62, 3 => 100, 4 => 92];
    @endphp

    <style>
        /* ===== HERO — one photographic slab held to the right, the headline
           walking over its left edge on a scrim. Everything arrives on one
           clock: the photograph is uncovered while the type rises. ===== */
        .hero { position: relative; display: grid; grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr); align-items: center; padding: 64px 0 0; }
        /* the slab reaches back under the headline — the overlap is the point,
           so it cannot depend on how long the merchant's headline runs */
        .hero .frame { grid-area: 1 / 2 / 2 / -1; position: relative; margin-left: -15%; aspect-ratio: 16 / 10; overflow: hidden; background: var(--surface2); }
        .hero .frame img { width: 100%; height: 100%; object-fit: cover; }
        /* the photograph is dimmed only where the type crosses it */
        /* the scrim only has to carry the type: it is opaque where the headline
           crosses and gone by the middle of the frame, whatever the photo. */
        .hero .frame::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(21, 18, 13, .95) 0%, rgba(21, 18, 13, .8) 30%, rgba(21, 18, 13, .3) 58%, transparent 80%); }
        .hero .lead { grid-area: 1 / 1 / 2 / 2; position: relative; z-index: 3; width: 138%; padding-right: 30px; }
        .hero .lead .k { display: block; margin-bottom: 22px; }
        .hero .lead h1 { font-family: var(--display); font-weight: 500; font-size: clamp(46px, 7.4vw, 104px); line-height: .98; letter-spacing: -.022em; margin-bottom: 26px; }
        .hero .lead h1 em { font-style: italic; color: var(--accent); }
        .hero .lead p { color: var(--muted); font-size: 17px; max-width: 40ch; margin-bottom: 34px; }
        .hero .lead .cta { display: flex; gap: 14px; flex-wrap: wrap; }
        /* the spec plate — the yard's own note, hung off the slab's foot */
        /* the plate hangs off the slab's foot, held clear of the right edge so
           the planed corner behind it stays legible */
        .hero .plate { grid-area: 1 / 2 / 2 / -1; align-self: end; justify-self: end; position: relative; z-index: 4; transform: translateY(46px); margin-right: 52px; display: flex; align-items: center; gap: 20px; background: var(--surface); border: 1px solid var(--line2); padding: 18px 24px; max-width: min(430px, 90%); }
        .hero .plate .seal { width: 44px; height: 44px; object-fit: contain; flex-shrink: 0; }
        .hero .plate .tx { min-width: 0; }
        .hero .plate .tx b { display: block; font-family: var(--display); font-weight: 500; font-size: 19px; line-height: 1.2; }
        .hero .plate .tx span { display: block; margin-top: 4px; font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }

        /* ===== LEDGER — the facts band. Numbers first, all tabular. ===== */
        .ledger { display: grid; grid-template-columns: repeat(4, 1fr); gap: 26px; margin: 92px 0 0; padding: 30px 0; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
        .ledger .cell b { display: block; font-family: var(--display); font-weight: 500; font-size: 30px; line-height: 1; font-variant-numeric: tabular-nums; }
        .ledger .cell span { display: block; margin-top: 10px; font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }

        /* ===== SPECIES GUIDE — the vertical's own band: what each timber is
           for, and how hard it runs. The gauge fills as the band scrolls in. */
        .species { position: relative; margin: 20px 0 0; }
        .species .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .species .sp { position: relative; background: var(--surface); border: 1px solid var(--line); padding: 28px 24px 24px; transition: border-color .35s ease, background-color .35s ease; }
        .species .sp:hover { border-color: var(--line2); background: var(--surface2); }
        .species .sp .lat { font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--accent); }
        .species .sp h3 { font-family: var(--display); font-weight: 500; font-size: 27px; line-height: 1.1; margin: 12px 0 10px; }
        .species .sp p { color: var(--muted); font-size: 14.5px; line-height: 1.6; margin-bottom: 24px; min-height: 4.8em; }
        .species .sp .gauge { height: 3px; background: var(--line); overflow: hidden; }
        .species .sp .gauge i { display: block; height: 100%; width: var(--dens, 50%); background: var(--accent); transition: width 1.1s cubic-bezier(.19, .74, .16, 1) .15s; }
        .species.reveal:not(.in) .sp .gauge i { width: 0; }
        .species .sp .gl { display: flex; justify-content: space-between; margin-top: 9px; font-size: 10px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); }

        /* ===== FOREST — a full-bleed divider with the quote laid across it.
           Breaks the content column on purpose; the body clips the overflow. */
        .forest { position: relative; margin: 118px 0; margin-left: calc(50% - 50vw); width: 100vw; min-height: 74vh; display: grid; place-items: center; overflow: hidden; }
        .forest img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .forest::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(16, 14, 10, .78), rgba(16, 14, 10, .42) 45%, rgba(16, 14, 10, .88)); }
        .forest .q { position: relative; z-index: 2; text-align: center; padding: 60px 26px; max-width: 940px; }
        .forest .q blockquote { font-family: var(--display); font-style: italic; font-weight: 400; font-size: clamp(28px, 4.6vw, 62px); line-height: 1.14; letter-spacing: -.015em; color: var(--txt); }
        .forest .q figcaption { margin-top: 28px; font-size: 10.5px; font-weight: 500; letter-spacing: .28em; text-transform: uppercase; color: var(--accent); }

        /* ===== STORY — the workshop photograph with the manifesto overlapping
           its top corner. Asymmetric on purpose: nothing lines up twice. */
        .story { position: relative; display: grid; grid-template-columns: 1.05fr .95fr; align-items: center; gap: 0; margin: 0 0 30px; }
        .story .art { position: relative; aspect-ratio: 4 / 3; overflow: hidden; background: var(--surface2); }
        .story .art img { width: 100%; height: 100%; object-fit: cover; }
        /* crops the shipped photograph's printed mount away (see the view) */
        .story .art img.mounted { transform: scale(1.075); }
        .story .tx { position: relative; z-index: 2; margin-left: -14%; background: var(--surface); border: 1px solid var(--line); padding: 52px 48px; }
        .story .tx .k { display: block; margin-bottom: 18px; }
        .story .tx h3 { font-family: var(--display); font-weight: 500; font-size: clamp(30px, 3.6vw, 50px); line-height: 1.05; letter-spacing: -.015em; margin-bottom: 20px; }
        .story .tx h3 em { font-style: italic; color: var(--accent); }
        .story .tx p { color: var(--muted); max-width: 46ch; margin-bottom: 16px; font-size: 15.5px; }
        .story .tx .btn { margin-top: 14px; }

        /* ===== TRADE — the moss slab. The one saturated field on the page. */
        .trade { position: relative; overflow: hidden; background: var(--moss); color: #f4f0e4; padding: 62px 58px; margin: 110px 0; display: grid; grid-template-columns: 1.15fr .85fr; gap: 46px; align-items: center; }
        .trade::after { content: ""; position: absolute; inset: 0; pointer-events: none; background: repeating-linear-gradient(90deg, rgba(255, 255, 255, .035) 0 1px, transparent 1px 26px); }
        .trade .k { display: block; color: var(--accent); margin-bottom: 18px; }
        .trade h3 { font-family: var(--display); font-weight: 500; font-size: clamp(30px, 3.8vw, 52px); line-height: 1.04; letter-spacing: -.015em; margin-bottom: 18px; }
        .trade h3 em { font-style: italic; }
        .trade p { color: rgba(244, 240, 228, .82); max-width: 50ch; }
        .trade .side { position: relative; z-index: 2; display: flex; flex-direction: column; gap: 14px; align-items: flex-start; }
        .trade .side .row { display: flex; align-items: center; gap: 12px; font-size: 11px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: rgba(244, 240, 228, .82); }
        .trade .side .row::before { content: ""; width: 18px; height: 1px; background: var(--accent); }
        .trade .side .btn { margin-top: 14px; }

        /* ===== NEWSLETTER — the price list, set quietly. ===== */
        .news { max-width: 620px; margin: 104px auto; text-align: center; }
        .news h3 { font-family: var(--display); font-weight: 500; font-size: clamp(28px, 3.4vw, 46px); line-height: 1.06; letter-spacing: -.015em; }
        .news h3 em { font-style: italic; color: var(--accent); }
        .news p { color: var(--muted); margin: 16px 0 28px; }
        .news form { display: flex; gap: 12px; }
        .news input { flex: 1; min-width: 0; background: var(--surface); border: 1px solid var(--line2); padding: 15px 18px; color: var(--txt); font-family: var(--body); font-size: 14px; }
        .news input::placeholder { color: var(--faint); }
        .news input:focus { outline: none; border-color: var(--accent); }

        /* category pills — the sections of the yard */
        .pills { display: flex; gap: 10px; flex-wrap: wrap; margin: 0 0 38px; }
        .pills .pill { transition: color .25s ease, border-color .25s ease, background-color .25s ease; }
        .pills .pill.on, .pills .pill:hover { background: var(--accent); border-color: var(--accent); color: var(--on-accent); }

        .home-empty { padding: 90px 24px; text-align: center; font-family: var(--display); font-style: italic; font-size: 26px; color: var(--muted); }

        /* ===== Collection rails — Sankevi restyle of the shared .cs-* partial.
           `.wrap `-prefixed to beat the partial's own rules. */
        .wrap .cs-strip { margin: 76px 0 0; }
        .wrap .cs-banner { display: none; }
        .wrap .cs-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; flex-wrap: wrap; margin: 0 0 30px; padding-bottom: 18px; border-bottom: 1px solid var(--line); }
        .wrap .cs-head h2 { font-family: var(--display); font-weight: 500; font-size: clamp(28px, 3.2vw, 44px); line-height: 1.06; letter-spacing: -.015em; }
        .wrap .cs-head p { color: var(--muted); font-size: 14.5px; max-width: 52ch; margin-top: 8px; }
        .wrap .cs-view-all { font-size: 11px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); border-bottom: 1px solid var(--line2); padding-bottom: 4px; white-space: nowrap; transition: color .25s ease, border-color .25s ease; }
        .wrap .cs-view-all:hover { color: var(--accent); border-color: var(--accent); }
        .wrap .cs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 26px 20px; }
        .wrap .cs-card { display: flex; flex-direction: column; color: inherit; }
        .wrap .cs-img { aspect-ratio: 4 / 5; overflow: hidden; background: linear-gradient(150deg, var(--surface2), #191510); margin-bottom: 14px; clip-path: polygon(13px 0, 100% 0, 100% calc(100% - 13px), calc(100% - 13px) 100%, 0 100%, 0 13px); }
        body.no-cut .wrap .cs-img { clip-path: none; }
        .wrap .cs-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 1.1s cubic-bezier(.19, .74, .16, 1); }
        .wrap .cs-card:hover .cs-img img { transform: scale(1.045); }
        .wrap .cs-meta { display: flex; flex-direction: column; gap: 5px; padding: 0; }
        .wrap .cs-name { font-family: var(--display); font-weight: 500; font-size: 19px; line-height: 1.15; }
        .wrap .cs-price { font-family: var(--display); font-weight: 500; font-size: 17px; font-variant-numeric: tabular-nums; color: var(--muted); }

        @media (prefers-reduced-motion: reduce) {
            .wrap .cs-card:hover .cs-img img { transform: none; }
            .species .sp .gauge i, .species.reveal:not(.in) .sp .gauge i { width: var(--dens, 50%); transition: none; }
        }
        @media (max-width: 1100px) {
            .hero .lead { width: 128%; }
            .story .tx { margin-left: -10%; padding: 42px 34px; }
        }
        @media (max-width: 900px) {
            /* On a phone the overlap becomes vertical: the photograph takes the
               full width, the headline bites into its bottom edge, and the
               scrim is a floor rather than a wall. */
            .hero { grid-template-columns: 1fr; padding-top: 30px; }
            .hero .frame { grid-area: 1 / 1 / 2 / -1; margin-left: 0; aspect-ratio: 4 / 3; }
            .hero .frame::after { background: linear-gradient(180deg, transparent 34%, rgba(21, 18, 13, .88) 84%, var(--bg)); }
            .hero .lead { grid-area: 2 / 1 / 3 / -1; width: 100%; margin-top: -62px; padding-right: 0; }
            .hero .plate { grid-area: 3 / 1 / 4 / -1; justify-self: stretch; max-width: none; transform: none; margin: 26px 0 0; }
            .ledger { grid-template-columns: repeat(2, 1fr); gap: 24px 20px; margin-top: 60px; }
            .species .grid { grid-template-columns: repeat(2, 1fr); }
            .species .sp p { min-height: 0; }
            .story { grid-template-columns: 1fr; }
            .story .tx { margin-left: 0; margin-top: -46px; width: 92%; }
            .trade { grid-template-columns: 1fr; padding: 42px 30px; margin: 80px 0; }
            .forest { min-height: 56vh; margin: 86px 0; }
        }
        @media (max-width: 560px) {
            .species .grid { grid-template-columns: 1fr; }
            .story .tx { width: 100%; padding: 34px 24px; }
            .news form { flex-direction: column; }
            .hero .plate { padding: 16px 18px; gap: 15px; }
        }
    </style>

    <main>
        @if (! $isFiltered)
            <div class="wrap">
                <section class="hero">
                    @if ($theme->on('gutter_index'))
                        <span class="gx" aria-hidden="true"><b>{{ $theme->label('gutter_index') }} 01</b> {{ __('site.storefront.sankevi.gx_home') }}</span>
                    @endif

                    <div class="frame cut cut-lg wipe" style="--d: .1s;">
                        @if ($heroImageUrl)
                            <img src="{{ $heroImageUrl }}" alt="" data-gv-parallax="0.06">
                        @endif
                    </div>

                    <div class="lead">
                        <span class="kicker k rise" style="--d: .35s;">{{ $csHero['title'] !== '' ? $csHero['title'] : __('site.storefront.sankevi.hero_kicker') }}</span>
                        <h1 class="rise" style="--d: .45s;">
                            @if ($csHero['subtitle'] !== '')
                                {{ $csHero['subtitle'] }}
                            @else
                                {!! __('site.storefront.sankevi.hero_h1_html') !!}
                            @endif
                        </h1>
                        <p class="rise" style="--d: .58s;">{{ __('site.storefront.sankevi.hero_sub') }}</p>
                        <div class="cta rise" style="--d: .68s;">
                            <a class="btn" href="#shop">{{ $csHero['cta_label'] !== '' ? $csHero['cta_label'] : __('site.storefront.hero.cta_primary') }}</a>
                            @if ($store->aboutPage()['enabled'])
                                <a class="btn outline" href="/about">{{ __('site.storefront.sankevi.story_cta') }}</a>
                            @else
                                <a class="btn outline" href="#species">{{ __('site.storefront.sankevi.species_eyebrow') }}</a>
                            @endif
                        </div>
                    </div>

                    <aside class="plate rise" style="--d: .82s;">
                        @if ($csSeal)<img class="seal" src="{{ $csSeal }}" alt="" aria-hidden="true">@endif
                        <div class="tx">
                            <b>{{ $heroProduct->name ?? $tenant->name }}</b>
                            <span>{{ $theme->copy('hero_note') }}</span>
                        </div>
                    </aside>
                </section>

                @if ($theme->on('ledger_strip'))
                    <section class="ledger rise" style="--d: .95s;">
                        <div class="cell">
                            <b data-gv-counter="{{ $products->total() }}">{{ $products->total() }}</b>
                            <span>{{ __('site.storefront.sankevi.ledger_stock') }}</span>
                        </div>
                        <div class="cell">
                            <b>{{ __('site.storefront.sankevi.ledger_1_v') }}</b>
                            <span>{{ __('site.storefront.sankevi.ledger_1_k') }}</span>
                        </div>
                        <div class="cell">
                            <b>{{ __('site.storefront.sankevi.ledger_2_v') }}</b>
                            <span>{{ __('site.storefront.sankevi.ledger_2_k') }}</span>
                        </div>
                        <div class="cell">
                            <b>{{ __('site.storefront.sankevi.ledger_3_v') }}</b>
                            <span>{{ __('site.storefront.sankevi.ledger_3_k') }}</span>
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

            {{-- The catalogue --}}
            <div class="sec-head" data-gv-reveal id="shop">
                <div>
                    <span class="kicker">{{ __('site.storefront.shop_all.eyebrow') }}</span>
                    <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
                </div>
                <span class="kicker" style="padding-bottom: 4px;">{{ trans_choice('site.storefront.controls.result_count', $products->total(), ['count' => $products->total()]) }}</span>
            </div>

            @if ($categories->isNotEmpty())
                <div class="pills" data-gv-reveal data-gv-delay="0.08">
                    <a href="/" class="pill {{ ! ($filters['category'] ?? null) ? 'on' : '' }}">{{ __('site.storefront.controls.category_all') }}</a>
                    @foreach ($categories as $cat)
                        <a href="/?category={{ $cat->slug }}" class="pill {{ ($filters['category'] ?? null) === $cat->slug ? 'on' : '' }}">{{ $cat->name }}</a>
                    @endforeach
                </div>
            @endif

            @if ($products->isEmpty())
                <div class="home-empty reveal">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="shelf {{ $theme->on('sheet_marks') ? '' : 'no-sheet' }}" style="--sheet-label: '{{ str_replace(['\\', '\''], '', $theme->label('sheet_marks')) }} '">
                    @foreach ($products as $product)
                        @include('themes.sankevi._card', ['product' => $product, 'badge' => null, 'gvDelay' => ($loop->index % 4) * 0.09])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif

            @if (! $isFiltered)
                {{-- Species guide — the materials merchant's own band. --}}
                @if ($theme->on('species_band'))
                    <section class="species reveal" id="species">
                        @if ($theme->on('gutter_index'))
                            <span class="gx" aria-hidden="true" style="top: 120px;"><b>{{ $theme->label('gutter_index') }} 02</b> {{ __('site.storefront.sankevi.species_eyebrow') }}</span>
                        @endif
                        <div class="sec-head">
                            <div>
                                <span class="kicker">{{ __('site.storefront.sankevi.species_eyebrow') }}</span>
                                <h2>{!! __('site.storefront.sankevi.species_h2_html') !!}</h2>
                            </div>
                        </div>
                        <div class="grid">
                            @foreach ($species as $n => $density)
                                <article class="sp cut">
                                    <div class="lat">{{ __('site.storefront.sankevi.sp' . $n . '_lat') }}</div>
                                    <h3>{{ __('site.storefront.sankevi.sp' . $n . '_name') }}</h3>
                                    <p>{{ __('site.storefront.sankevi.sp' . $n . '_use') }}</p>
                                    <div class="gauge" style="--dens: {{ $density }}%;" role="img"
                                         aria-label="{{ __('site.storefront.sankevi.density_label') }}: {{ __('site.storefront.sankevi.sp' . $n . '_dens') }}"><i></i></div>
                                    <div class="gl">
                                        <span>{{ __('site.storefront.sankevi.density_label') }}</span>
                                        <span>{{ __('site.storefront.sankevi.sp' . $n . '_dens') }}</span>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endif
        </div>

        @if (! $isFiltered)
            {{-- The forest itself — full bleed, the quote laid over it. --}}
            @if ($theme->on('forest_band') && $forestImageUrl)
                <figure class="forest reveal">
                    <img src="{{ $forestImageUrl }}" alt="" loading="lazy" data-gv-parallax="0.1">
                    <div class="q">
                        <blockquote>{{ $theme->copy('forest_quote') }}</blockquote>
                        <figcaption>{{ __('site.storefront.sankevi.forest_caption') }}</figcaption>
                    </div>
                </figure>
            @endif

            <div class="wrap">
                {{-- The workshop — photograph and manifesto, overlapping. --}}
                @if ($theme->on('story_band'))
                    <section class="story reveal">
                        @if ($theme->on('gutter_index'))
                            <span class="gx" aria-hidden="true"><b>{{ $theme->label('gutter_index') }} 03</b> {{ __('site.storefront.sankevi.story_eyebrow') }}</span>
                        @endif
                        <div class="art cut cut-lg">
                            @if ($storyImageUrl)
                                <img class="{{ $storyIsShipped ? 'mounted' : '' }}" src="{{ $storyImageUrl }}" alt="" loading="lazy">
                            @endif
                        </div>
                        <div class="tx">
                            <span class="kicker k">{{ __('site.storefront.sankevi.story_eyebrow') }}</span>
                            <h3>{!! __('site.storefront.sankevi.story_h2_html') !!}</h3>
                            <p>{{ $theme->copy('story_body') }}</p>
                            @if ($store->aboutPage()['enabled'])
                                <a class="btn outline" href="/about">{{ __('site.storefront.sankevi.story_cta') }}</a>
                            @endif
                        </div>
                    </section>
                @endif

                {{-- Trade & sites — the moss slab. --}}
                @if ($theme->on('trade_band'))
                    <section class="trade cut cut-lg reveal">
                        <div>
                            <span class="kicker k">{{ __('site.storefront.sankevi.trade_eyebrow') }}</span>
                            <h3>{{ __('site.storefront.sankevi.trade_h2') }}</h3>
                            <p>{{ $theme->copy('trade_body') }}</p>
                        </div>
                        <div class="side">
                            <span class="row">{{ __('site.storefront.sankevi.trade_row1') }}</span>
                            <span class="row">{{ __('site.storefront.sankevi.trade_row2') }}</span>
                            <span class="row">{{ __('site.storefront.sankevi.trade_row3') }}</span>
                            @if ($tradeEmail)
                                <a class="btn" href="mailto:{{ $tradeEmail }}">{{ __('site.storefront.sankevi.trade_cta') }}</a>
                            @else
                                <a class="btn" href="#shop">{{ __('site.storefront.featured.browse_all') }}</a>
                            @endif
                        </div>
                    </section>
                @endif

                {{-- The price list. --}}
                @if ($theme->on('news_band'))
                    <section class="news reveal">
                        <h3>{!! __('site.storefront.promo.h2_prefix', ['tenant' => '<em>' . e($tenant->name) . '</em>']) !!}</h3>
                        <p>{{ $theme->copy('news_body') }}</p>
                        <form onsubmit="return false">
                            <label class="sr-only" for="news-email">{{ __('site.storefront.footer.newsletter_placeholder') }}</label>
                            <input type="email" id="news-email" placeholder="{{ __('site.storefront.footer.newsletter_placeholder') }}">
                            <button type="submit" class="btn">{{ __('site.storefront.footer.subscribe') }}</button>
                        </form>
                    </section>
                @endif
            </div>
        @endif
    </main>
@endsection
