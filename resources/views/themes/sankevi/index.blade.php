@extends('themes.sankevi.layout')

@section('content')
    @php
        /*
         | Sankevi — THE BRAND PAGE.
         |
         | This view used to be the catalogue: a hero, then a wall of product
         | cards. The merchant's complaint was exactly that — "we are mixing the
         | shop and the main page and it looks like an e-commerce". So the yard
         | now has two front doors:
         |
         |   /       this file — six cinematic acts, no grid, no add-to-cart
         |   /shop   themes.sankevi.shop — the catalogue, filters, pagination
         |
         | StorefrontController@index already re-routes a visitor who is
         | searching/filtering/paginating to the shop view, so nothing here has
         | to double as a product list. The only products on this page are ONE
         | restrained editorial trio that hands off to /shop.
         |
         | Every act is full- or near-full-viewport and separated by generous
         | space. Motion comes from the shared storefront kit (Lenis smooth
         | scroll, data-gv-reveal / -parallax / -counter) plus two ReactBits
         | ports written in vanilla JS at the foot of the file. All of it
         | surrenders to prefers-reduced-motion.
         */

        $csHero = $store->heroBanner();

        // Hero photography. The merchant's own banner wins; then their upload
        // in the theme's hero slot; and only if that slot is still the SHIPPED
        // default (the dawn yard, a wide establishing shot) do we swap in the
        // end-grain — a 21:9 close-up is the frame this act was designed for,
        // and the yard shot would read as a stock header.
        $themeHeroUrl = $theme->image('hero_image');
        $heroIsShipped = $themeHeroUrl && str_contains($themeHeroUrl, '/images/demo/sankevi/yard');
        $heroImageUrl = $csHero['enabled'] && $csHero['image_path']
            ? \Illuminate\Support\Facades\Storage::url($csHero['image_path'])
            : ($heroIsShipped || ! $themeHeroUrl
                ? asset('/images/demo/sankevi/endgrain.webp')
                : $themeHeroUrl);

        $storyImageUrl = $theme->image('story_image');
        // The shipped workshop photograph carries a printed white mount, which
        // has no business on a bark ground — the default asset is zoomed past
        // its border. A merchant's own upload is shown exactly as uploaded.
        $storyIsShipped = $storyImageUrl && str_contains($storyImageUrl, '/images/demo/sankevi/workshop');
        $forestImageUrl = $theme->image('forest_image');
        $csSeal = $theme->on('brand_seal') ? $theme->image('seal_image') : null;

        // The flowing menu's photography, mapped by category ORDER: floorboards,
        // beams, decking. A category that carries its own image uses that
        // instead — a merchant who photographs their own families should see
        // their own families.
        $familyArt = [
            asset('/images/demo/sankevi/floor.webp'),
            asset('/images/demo/sankevi/beams.webp'),
            asset('/images/demo/sankevi/deck.webp'),
        ];

        // Facts for the counters. Years come from the merchant's own founding
        // year when they set one on the About page; the sourcing radius and the
        // kiln target are yard constants, and they are numerals — identical in
        // both locales — so only their labels are translated.
        $founded = (int) ($store->aboutPage()['founded_year'] ?? 0);
        $yearsRunning = $founded ? max(1, (int) date('Y') - $founded) : 50;
        $sourcingRadiusKm = 40;
        $kilnMoisturePct = 12;

        // The marquee is built from the merchant's own name so it is never
        // Sankevi-specific: NAME · SAWMILL · SINCE 1974 · RHODOPES ·
        $marqueeWords = array_values(array_filter([
            $tenant->name,
            __('site.storefront.sankevi.marquee_trade'),
            $founded ? __('site.storefront.sankevi.marquee_since', ['year' => $founded]) : null,
            __('site.storefront.sankevi.marquee_place'),
        ]));

        // The editorial trio — three boards, photographed. Prefer the ones that
        // actually have a photograph; a trio of placeholders is not an act.
        $pickup = $products->getCollection()->filter(fn ($p) => $p->image_path);
        if ($pickup->count() < 3) {
            $pickup = $products->getCollection();
        }
        $pickup = $pickup->take(3);

        $aboutOn = $store->aboutPage()['enabled'];
        $contactOn = $store->contactPage()['enabled'];
        // Where "the second door" goes: the story if the merchant publishes one,
        // otherwise their contact page, otherwise nothing (never a dead link).
        $secondaryHref = $aboutOn ? '/about' : ($contactOn ? '/contact' : null);
        $secondaryLabel = $aboutOn
            ? __('site.storefront.sankevi.story_cta')
            : __('site.storefront.footer.contact');
    @endphp

    <style>
        /* =================================================================
           FILM GRAIN — one fine, moving layer over the whole page. Generated
           by feTurbulence inside a data URI, so it costs zero requests and
           scales with the viewport. It lives inside <main>, which the layout
           pins at z-index 1, so it can never rise over the sticky header,
           the drawer or the toast.
           ================================================================= */
        .grain {
            position: fixed; inset: -140px; z-index: 40; pointer-events: none;
            opacity: .055; mix-blend-mode: overlay; will-change: transform;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.82' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='180' height='180' filter='url(%23n)'/%3E%3C/svg%3E");
            animation: grainDrift 1.1s steps(5) infinite;
        }
        @keyframes grainDrift {
            0% { transform: translate3d(0, 0, 0); }
            20% { transform: translate3d(-32px, 14px, 0); }
            40% { transform: translate3d(18px, -26px, 0); }
            60% { transform: translate3d(-14px, 24px, 0); }
            80% { transform: translate3d(28px, 10px, 0); }
            100% { transform: translate3d(0, 0, 0); }
        }
        html[data-mode="light"] .grain { opacity: .045; mix-blend-mode: multiply; }
        body.no-grain .grain { display: none; }

        /* =================================================================
           ACT 1 — HERO. Full bleed end-grain under a heavy vignette, the
           wordmark and one enormous line laid over it. The photograph drifts
           (kit parallax on the <img>) inside a wrapper that slowly scales
           with the scroll — two separate elements, because GSAP owns the
           img's transform and CSS owns the wrapper's.
           ================================================================= */
        .hero {
            position: relative; overflow: hidden; display: flex; align-items: flex-end;
            height: calc(100vh - var(--header-height));
            height: calc(100svh - var(--header-height));
            min-height: 560px;
        }
        /* oversized on every side so neither the drift nor the scale can ever
           expose an edge of the photograph */
        .hero .media { position: absolute; inset: -9% -4%; }
        .hero .media .zoom { width: 100%; height: 100%; transform-origin: 52% 46%; }
        .hero .media img { width: 100%; height: 100%; object-fit: cover; }
        /* THE SCRIM. Legibility is engineered, not hoped for: a radial vignette
           closes the corners, a vertical ramp darkens the foot where the type
           sits, and the last stop dissolves the photograph into the page. */
        .hero .veil {
            position: absolute; inset: 0; pointer-events: none;
            background:
                radial-gradient(128% 96% at 50% 34%, transparent 4%, rgba(11, 9, 6, .42) 52%, rgba(11, 9, 6, .86) 100%),
                linear-gradient(180deg, rgba(11, 9, 6, .66) 0%, rgba(11, 9, 6, .16) 20%, rgba(11, 9, 6, .38) 44%, rgba(11, 9, 6, .86) 68%, rgba(11, 9, 6, .96) 88%, var(--bg) 100%);
        }
        html[data-mode="light"] .hero .veil {
            background:
                radial-gradient(128% 96% at 50% 34%, transparent 4%, rgba(11, 9, 6, .5) 52%, rgba(11, 9, 6, .88) 100%),
                linear-gradient(180deg, rgba(11, 9, 6, .72) 0%, rgba(11, 9, 6, .34) 24%, rgba(11, 9, 6, .66) 62%, rgba(11, 9, 6, .9) 92%, rgba(11, 9, 6, .96) 100%);
        }
        /* the ink over the photograph stays birch cream in BOTH modes — the
           hero is a night frame either way, so it never follows --txt */
        .hero .lead { position: relative; z-index: 3; width: 100%; padding-bottom: clamp(38px, 7vh, 88px); color: #f4efe2; }
        /* A SECOND, local scrim under the copy block only. The vignette above
           is tuned for the photograph; this one is tuned for the type, and it
           is what guarantees AA on the small letterspaced label no matter how
           pale the merchant's photograph happens to be behind it. Bled past
           the content column so it never shows a vertical edge. */
        .hero .lead::before {
            content: ""; position: absolute; z-index: -1; pointer-events: none;
            left: -50vw; right: -50vw; bottom: 0; top: -70px;
            background: linear-gradient(180deg, transparent, rgba(11, 9, 6, .58) 22%, rgba(11, 9, 6, .8));
        }
        .hero .mark { display: flex; align-items: center; gap: 14px; margin-bottom: clamp(16px, 3vh, 30px); }
        .hero .mark img { width: 30px; height: 30px; object-fit: contain; opacity: .9; }
        .hero .mark span { font-size: 11px; font-weight: 500; letter-spacing: .38em; text-transform: uppercase; color: #f4efe2; text-shadow: 0 1px 12px rgba(9, 7, 4, .85); }
        .hero .mark::after { content: ""; flex: 1; max-width: 120px; height: 1px; background: linear-gradient(90deg, var(--accent), transparent); }
        .hero h1 {
            font-family: var(--display); font-weight: 500;
            font-size: clamp(50px, 10.6vw, 168px); line-height: .9; letter-spacing: -.032em;
            max-width: 15ch; text-wrap: balance;
            text-shadow: 0 2px 40px rgba(9, 7, 4, .5);
        }
        .hero h1 em { font-style: italic; color: var(--accent); }
        .hero .foot { display: flex; align-items: flex-end; justify-content: space-between; gap: 30px; margin-top: clamp(20px, 4vh, 44px); }
        .hero .foot p { max-width: 44ch; font-size: 16px; color: rgba(244, 239, 226, .92); text-shadow: 0 1px 12px rgba(9, 7, 4, .8); }
        /* the scroll cue — a hairline with a bead that falls down it */
        .hero .cue { display: flex; flex-direction: column; align-items: center; gap: 12px; flex-shrink: 0; font-size: 10px; font-weight: 500; letter-spacing: .3em; text-transform: uppercase; color: rgba(244, 239, 226, .72); }
        .hero .cue .rail { position: relative; width: 1px; height: 62px; background: rgba(244, 239, 226, .22); overflow: hidden; }
        .hero .cue .rail::after { content: ""; position: absolute; left: -1px; width: 3px; height: 22px; background: var(--accent); animation: cueFall 2.3s cubic-bezier(.5, 0, .5, 1) infinite; }
        @keyframes cueFall { 0% { top: -24px; } 100% { top: 64px; } }

        /* Scroll-driven scale: supported browsers get the slow push-in over the
           first viewport of travel; everyone else keeps the parallax drift and
           loses nothing. Progressive enhancement, never a dependency. */
        @media (prefers-reduced-motion: no-preference) {
            @supports (animation-timeline: scroll()) {
                .hero .media .zoom {
                    animation: heroPush linear both;
                    animation-timeline: scroll(root block);
                    animation-range: 0 100vh;
                }
                @keyframes heroPush { from { transform: scale(1.005); } to { transform: scale(1.16); } }
            }
        }

        /* =================================================================
           ACT 2 — SCROLL VELOCITY. A ReactBits "Scroll Velocity" port: two
           bands of enormous type that always creep, speed up with the
           scroll's velocity and flip with its direction. The JS lives at the
           foot of the file; everything here is only the look.
           ================================================================= */
        .vel { position: relative; overflow: hidden; padding: clamp(72px, 12vh, 132px) 0; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
        .vel::before, .vel::after { content: ""; position: absolute; top: 0; bottom: 0; width: clamp(60px, 12vw, 190px); z-index: 2; pointer-events: none; }
        .vel::before { left: 0; background: linear-gradient(90deg, var(--bg), transparent); }
        .vel::after { right: 0; background: linear-gradient(270deg, var(--bg), transparent); }
        .vel-row { display: flex; width: max-content; will-change: transform; }
        .vel-row + .vel-row { margin-top: clamp(4px, 1.2vh, 14px); }
        .vel-seq { display: flex; }
        .vel-item {
            display: flex; align-items: center; gap: .3em; padding-right: .3em; white-space: nowrap;
            font-family: var(--display); font-weight: 500; font-size: clamp(42px, 8.4vw, 132px);
            line-height: 1.06; letter-spacing: -.02em; text-transform: uppercase;
        }
        .vel-item i { font-style: normal; color: var(--accent); font-size: .6em; transform: translateY(-.06em); }
        /* the second band is the same words cut out of the ground — outline
           only, so the two together read as one thick, woven rule */
        .vel-row.ghost .vel-item { color: transparent; -webkit-text-stroke: 1px var(--line2); }
        .vel-row.ghost .vel-item i { color: color-mix(in srgb, var(--accent) 55%, transparent); -webkit-text-stroke: 0; }

        /* =================================================================
           ACT 3 — FLOWING MENU. The signature. One full-width row per product
           family; on hover/focus/tap the row fills with a running marquee of
           that family's photograph, entering from the edge the pointer
           crossed. Rows are plain links: with JS off nothing is revealed and
           nothing is lost.
           ================================================================= */
        .families { position: relative; padding: clamp(90px, 15vh, 170px) 0 0; }
        .families .head { position: relative; display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; flex-wrap: wrap; padding-bottom: 26px; }
        .families .head h2 { font-family: var(--display); font-weight: 500; font-size: clamp(34px, 5.2vw, 76px); line-height: 1; letter-spacing: -.022em; margin-top: 16px; }
        .families .head h2 em { font-style: italic; color: var(--accent); }
        .families .head .hint { font-size: 11px; font-weight: 500; letter-spacing: .24em; text-transform: uppercase; color: var(--faint); padding-bottom: 6px; }

        .fm { border-top: 1px solid var(--line2); }
        .fm-row {
            position: relative; display: flex; align-items: center; gap: 22px; overflow: hidden;
            height: clamp(104px, 17vh, 178px); padding: 0 clamp(6px, 2vw, 26px);
            border-bottom: 1px solid var(--line2); color: inherit;
            --fm-idle: 101%;
        }
        .fm-row .ix { flex-shrink: 0; font-size: 11px; font-weight: 500; letter-spacing: .22em; color: var(--accent); font-variant-numeric: tabular-nums; }
        .fm-row .name { font-family: var(--display); font-weight: 500; font-size: clamp(30px, 5.4vw, 74px); line-height: 1; letter-spacing: -.02em; transition: transform .5s cubic-bezier(.19, .74, .16, 1), color .35s ease; }
        .fm-row .ix, .fm-row .name, .fm-row .go, .fm-row .thumb { position: relative; z-index: 1; }
        .fm-row .go { margin-left: auto; flex-shrink: 0; font-size: 11px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); display: inline-flex; align-items: center; gap: 10px; transition: color .35s ease; }
        .fm-row .go svg { width: 26px; height: 10px; fill: none; stroke: currentColor; stroke-width: 1.2; transition: transform .5s cubic-bezier(.19, .74, .16, 1); }
        .fm-row:hover .name, .fm-row:focus-visible .name { transform: translateX(14px); }
        .fm-row:hover .go svg, .fm-row:focus-visible .go svg { transform: translateX(8px); }
        /* the still that carries the family on touch, where there is no hover
           to reveal the marquee with */
        .fm-row .thumb { display: none; width: 84px; height: 58px; object-fit: cover; margin-left: auto; }

        /* the reveal itself: a full-bleed accent panel that slides in from the
           side the pointer crossed and runs its own horizontal marquee */
        /* The panel sits ABOVE the row's own type and covers it outright, the
           way the reference does: while the family is revealed, the marquee IS
           the row. Layering it underneath instead would leave the static title
           colliding with the running one. */
        .fm-marquee {
            position: absolute; inset: 0; z-index: 2; overflow: hidden; pointer-events: none;
            background: var(--accent); color: var(--on-accent);
            transform: translate3d(0, var(--fm-idle), 0);
            transition: transform .62s cubic-bezier(.19, .74, .16, 1);
            will-change: transform;
        }
        .fm-row.is-on .fm-marquee { transform: translate3d(0, 0, 0); }
        .fm-track { display: flex; align-items: center; height: 100%; width: max-content; animation: fmRun 34s linear infinite; animation-play-state: paused; }
        .fm-row.is-on .fm-track { animation-play-state: running; }
        @keyframes fmRun { to { transform: translate3d(-50%, 0, 0); } }
        .fm-cell { display: flex; align-items: center; gap: clamp(20px, 3vw, 44px); padding-right: clamp(20px, 3vw, 44px); }
        .fm-cell .w { font-family: var(--display); font-weight: 500; font-size: clamp(26px, 4vw, 56px); line-height: 1; letter-spacing: -.02em; white-space: nowrap; }
        .fm-cell .p { width: clamp(130px, 17vw, 240px); height: clamp(64px, 11vh, 116px); background-size: cover; background-position: center; flex-shrink: 0; }

        /* =================================================================
           ACT 4 — STORY. The workshop beside the manifesto, and the three
           facts that matter, counted up.
           ================================================================= */
        .story { position: relative; display: grid; grid-template-columns: 1.02fr .98fr; align-items: center; margin: clamp(96px, 17vh, 190px) 0 0; }
        .story .art { position: relative; aspect-ratio: 4 / 3; overflow: hidden; background: var(--surface2); }
        .story .art img { width: 100%; height: 100%; object-fit: cover; }
        .story .art img.mounted { transform: scale(1.14); }
        .story .tx { position: relative; z-index: 2; margin-left: -13%; background: var(--surface); border: 1px solid var(--line); padding: clamp(34px, 4.4vw, 62px); }
        .story .tx .k { display: block; margin-bottom: 18px; }
        .story .tx h3 { font-family: var(--display); font-weight: 500; font-size: clamp(30px, 3.8vw, 54px); line-height: 1.03; letter-spacing: -.018em; margin-bottom: 22px; }
        .story .tx h3 em { font-style: italic; color: var(--accent); }
        .story .tx p { color: var(--muted); max-width: 46ch; font-size: 15.5px; }
        .story .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; margin-top: 34px; padding-top: 28px; border-top: 1px solid var(--line); }
        /* The counted value lives in its own <span> (the kit rewrites that
           element's text), so the label rule below is scoped to .lb — an
           unscoped `span` would shrink the number itself. */
        .story .stats b { display: flex; align-items: baseline; font-family: var(--display); font-weight: 500; font-size: clamp(28px, 3vw, 40px); line-height: 1; font-variant-numeric: tabular-nums; }
        .story .stats b i { font-style: normal; color: var(--accent); }
        .story .stats .lb { display: block; margin-top: 10px; font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); }

        /* =================================================================
           THE EDITORIAL TRIO — the only products on this page. No prices, no
           cards, no add-to-cart: three photographs, three names, and a door
           to the yard. If this ever grows a fourth column it has become a
           grid again, which is the thing we removed.
           ================================================================= */
        .pickup { margin: clamp(90px, 15vh, 168px) 0 0; }
        .pickup .head { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap; padding-bottom: 22px; border-bottom: 1px solid var(--line); }
        .pickup .head .more { font-size: 11px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); border-bottom: 1px solid var(--line2); padding-bottom: 4px; transition: color .25s ease, border-color .25s ease; }
        .pickup .head .more:hover { color: var(--accent); border-color: var(--accent); }
        .pickup .trio { display: grid; grid-template-columns: repeat(3, 1fr); gap: clamp(22px, 4vw, 58px); margin-top: 44px; }
        .pickup .it { display: flex; flex-direction: column; color: inherit; }
        .pickup .it .pic { position: relative; aspect-ratio: 3 / 4; overflow: hidden; background: linear-gradient(150deg, var(--surface2), #191510); display: grid; place-items: center; }
        .pickup .it .pic img { width: 100%; height: 100%; object-fit: cover; transition: transform 1.2s cubic-bezier(.19, .74, .16, 1); }
        .pickup .it:hover .pic img { transform: scale(1.05); }
        .pickup .it .cat { margin-top: 16px; font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }
        .pickup .it h3 { font-family: var(--display); font-weight: 500; font-size: clamp(20px, 1.9vw, 27px); line-height: 1.14; margin-top: 8px; transition: color .3s ease; }
        .pickup .it:hover h3 { color: var(--accent); }
        /* the second/third items step down the page — an editorial rhythm the
           eye reads as a spread, not a row of SKUs */
        .pickup .trio .it:nth-child(2) { margin-top: clamp(20px, 5vw, 64px); }
        .pickup .trio .it:nth-child(3) { margin-top: clamp(40px, 9vw, 118px); }

        /* =================================================================
           ACT 5 — FOREST. Full bleed, one line of type, nothing else.
           ================================================================= */
        .forest { position: relative; margin: clamp(100px, 17vh, 190px) 0; margin-left: calc(50% - 50vw); width: 100vw; min-height: 82vh; display: grid; place-items: center; overflow: hidden; }
        .forest img { position: absolute; inset: -8% 0; width: 100%; height: 116%; object-fit: cover; }
        .forest::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, var(--bg), rgba(11, 9, 6, .5) 26%, rgba(11, 9, 6, .46) 62%, var(--bg)); }
        .forest .q { position: relative; z-index: 2; text-align: center; padding: 70px 26px; max-width: 1080px; }
        .forest .q blockquote { font-family: var(--display); font-style: italic; font-weight: 400; font-size: clamp(28px, 5vw, 74px); line-height: 1.1; letter-spacing: -.022em; color: #f4efe2; text-shadow: 0 2px 34px rgba(9, 7, 4, .55); }
        .forest .q figcaption { margin-top: 30px; font-size: 10.5px; font-weight: 500; letter-spacing: .3em; text-transform: uppercase; color: var(--accent); }

        /* =================================================================
           ACT 6 — THE CLOSING CALL. The one saturated field on the page, and
           the only place the page asks for anything.
           ================================================================= */
        .closing { position: relative; overflow: hidden; margin-left: calc(50% - 50vw); width: 100vw; background: var(--moss); color: #f4f0e4; padding: clamp(70px, 13vh, 140px) 0; }
        .closing::after { content: ""; position: absolute; inset: 0; pointer-events: none; background: repeating-linear-gradient(90deg, rgba(255, 255, 255, .035) 0 1px, transparent 1px 28px); }
        .closing .in { position: relative; z-index: 2; display: grid; grid-template-columns: 1.2fr .8fr; gap: clamp(30px, 5vw, 76px); align-items: end; }
        .closing .k { display: block; color: var(--accent); margin-bottom: 20px; }
        .closing h2 { font-family: var(--display); font-weight: 500; font-size: clamp(34px, 5.4vw, 80px); line-height: 1; letter-spacing: -.024em; }
        .closing h2 em { font-style: italic; color: var(--accent); }
        .closing p { color: rgba(244, 240, 228, .82); max-width: 46ch; margin-top: 22px; font-size: 15.5px; }
        .closing .rows { display: flex; flex-direction: column; gap: 13px; margin: 30px 0 0; }
        .closing .rows .row { display: flex; align-items: center; gap: 12px; font-size: 11px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: rgba(244, 240, 228, .8); }
        .closing .rows .row::before { content: ""; width: 18px; height: 1px; background: var(--accent); }
        .closing .cta { display: flex; flex-wrap: wrap; gap: 14px; }
        .closing .btn.outline { color: #f4f0e4; border-color: rgba(244, 240, 228, .35); }
        .closing .btn.outline:hover { border-color: var(--accent); color: var(--accent); }

        /* =================================================================
           REDUCED MOTION — the whole page holds still. Nothing here is
           decoration-with-meaning, so every one of these can simply stop:
           the grain freezes, the hero stops pushing in, the scroll bead
           stops falling, the velocity bands sit where they were rendered,
           and the flowing menu trades its slide for a plain cross-fade.
           ================================================================= */
        @media (prefers-reduced-motion: reduce) {
            .grain { animation: none; }
            .hero .cue .rail::after { animation: none; top: 8px; }
            .fm-marquee { transform: none; opacity: 0; transition: opacity .18s linear; }
            .fm-row.is-on .fm-marquee { opacity: 1; }
            .fm-track { animation: none; }
            .fm-row .name, .fm-row .go svg, .fm-row:hover .name, .fm-row:hover .go svg { transform: none; transition: none; }
            .pickup .it .pic img, .pickup .it:hover .pic img { transform: none; transition: none; }
        }

        @media (max-width: 1100px) {
            .story .tx { margin-left: -8%; }
        }
        @media (max-width: 900px) {
            .hero { height: auto; min-height: calc(100svh - var(--header-height)); padding-top: 26vh; }
            .hero .foot { flex-direction: column; align-items: flex-start; gap: 26px; }
            .hero .cue { flex-direction: row; align-items: center; gap: 14px; }
            .hero .cue .rail { width: 46px; height: 1px; }
            .hero .cue .rail::after { width: 18px; height: 3px; left: auto; top: -1px; animation-name: cueSlide; }
            /* on a phone the whole row hands its reveal to the still, which is
               always visible — nothing about the family is hover-only */
            .fm-row .go span { display: none; }
            .story { grid-template-columns: 1fr; }
            .story .tx { margin-left: 0; margin-top: -44px; width: 93%; }
            .pickup .trio { grid-template-columns: 1fr 1fr; }
            .pickup .trio .it:nth-child(3) { display: none; }
            .closing .in { grid-template-columns: 1fr; align-items: start; }
            .forest { min-height: 62vh; }
        }
        @keyframes cueSlide { 0% { left: -20px; } 100% { left: 48px; } }
        @media (hover: none) {
            .fm-row .thumb { display: block; }
            .fm-row .go { margin-left: 18px; }
        }
        @media (max-width: 620px) {
            .hero h1 { max-width: none; }
            .story .tx { width: 100%; }
            .pickup .trio { grid-template-columns: 1fr; gap: 34px; }
            .pickup .trio .it:nth-child(2), .pickup .trio .it:nth-child(3) { margin-top: 0; }
            .pickup .trio .it:nth-child(3) { display: flex; }
            .fm-row { height: clamp(88px, 14vh, 120px); gap: 14px; }
            .fm-row .thumb { width: 62px; height: 44px; }
        }
    </style>

    <main>
        <div class="grain" aria-hidden="true"></div>

        {{-- ============ ACT 1 — HERO ============ --}}
        <section class="hero">
            <div class="media wipe" style="--d: .05s;">
                <div class="zoom">
                    @if ($heroImageUrl)
                        <img src="{{ $heroImageUrl }}" alt="" fetchpriority="high" data-gv-parallax="0.22">
                    @endif
                </div>
            </div>
            <div class="veil" aria-hidden="true"></div>

            <div class="wrap lead">
                <div class="mark rise" style="--d: .3s;">
                    @if ($csSeal)<img src="{{ $csSeal }}" alt="" aria-hidden="true">@endif
                    <span>{{ $csHero['title'] !== '' ? $csHero['title'] : $tenant->name }}</span>
                </div>
                <h1 class="rise" style="--d: .42s;">
                    @if ($csHero['subtitle'] !== '')
                        {{ $csHero['subtitle'] }}
                    @else
                        {!! __('site.storefront.sankevi.hero_h1_html') !!}
                    @endif
                </h1>
                <div class="foot">
                    <p class="rise" style="--d: .6s;">{{ __('site.storefront.sankevi.hero_sub') }}</p>
                    <span class="cue rise" style="--d: .78s;" aria-hidden="true">
                        <span class="rail"></span>
                        {{ __('site.storefront.sankevi.scroll_cue') }}
                    </span>
                </div>
            </div>
        </section>

        {{-- ============ ACT 2 — SCROLL VELOCITY ============ --}}
        {{-- The band itself is decorative repetition; a screen reader gets one
             sentence instead of the same four words eight times over. --}}
        <section class="vel" data-vel>
            <span class="sr-only">{{ __('site.storefront.sankevi.marquee_sr', ['name' => $tenant->name]) }}</span>
            <div class="vel-row" data-vel-row data-vel-dir="-1" data-vel-base="0.062" aria-hidden="true">
                <div class="vel-seq">
                    @foreach ($marqueeWords as $word)
                        <span class="vel-item">{{ $word }} <i>◆</i></span>
                    @endforeach
                </div>
            </div>
            <div class="vel-row ghost" data-vel-row data-vel-dir="1" data-vel-base="0.044" aria-hidden="true">
                <div class="vel-seq">
                    @foreach (array_reverse($marqueeWords) as $word)
                        <span class="vel-item">{{ $word }} <i>◆</i></span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ============ ACT 3 — FLOWING MENU ============
             id="shop" so the layout's stock "#shop" links (nav + footer, which
             this view must not edit) land on the families rather than nowhere. --}}
        <section class="families" id="shop">
            <div class="wrap">
                <div class="head" data-gv-reveal>
                    @if ($theme->on('gutter_index'))
                        <span class="gx" aria-hidden="true"><b>{{ $theme->label('gutter_index') }} 01</b> {{ __('site.storefront.sankevi.gx_home') }}</span>
                    @endif
                    <div>
                        <span class="kicker">{{ __('site.storefront.sankevi.families_eyebrow') }}</span>
                        <h2>{!! __('site.storefront.sankevi.families_h2_html') !!}</h2>
                    </div>
                    <span class="hint">{{ __('site.storefront.sankevi.families_hint') }}</span>
                </div>
            </div>

            <nav class="fm" aria-label="{{ __('site.storefront.sankevi.families_eyebrow') }}">
                @forelse ($categories as $cat)
                    @php
                        $art = $cat->image_path
                            ? \Illuminate\Support\Facades\Storage::url($cat->image_path)
                            : $familyArt[$loop->index % count($familyArt)];
                    @endphp
                    <a class="fm-row" data-fm-row href="/shop?category={{ $cat->slug }}">
                        <span class="fm-marquee" data-fm-marquee aria-hidden="true">
                            <span class="fm-track">
                                {{-- Twelve cells: six that fill the row and six identical
                                     ones behind them, so the -50% loop is seamless. --}}
                                @for ($i = 0; $i < 12; $i++)
                                    <span class="fm-cell">
                                        <span class="w">{{ $cat->name }}</span>
                                        <span class="p" style="background-image: url('{{ $art }}');"></span>
                                    </span>
                                @endfor
                            </span>
                        </span>
                        <span class="ix">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <span class="name">{{ $cat->name }}</span>
                        <img class="thumb cut cut-sm" src="{{ $art }}" alt="" loading="lazy" aria-hidden="true">
                        <span class="go">
                            <span>{{ __('site.storefront.sankevi.card_action') }}</span>
                            <svg viewBox="0 0 26 10" aria-hidden="true"><path d="M0 5h24M20 1l4 4-4 4"/></svg>
                        </span>
                    </a>
                @empty
                    {{-- No families yet — one row, straight to the yard. --}}
                    <a class="fm-row" data-fm-row href="/shop">
                        <span class="fm-marquee" data-fm-marquee aria-hidden="true">
                            <span class="fm-track">
                                @for ($i = 0; $i < 12; $i++)
                                    <span class="fm-cell">
                                        <span class="w">{{ __('site.storefront.sankevi.families_all') }}</span>
                                        <span class="p" style="background-image: url('{{ $familyArt[0] }}');"></span>
                                    </span>
                                @endfor
                            </span>
                        </span>
                        <span class="ix">01</span>
                        <span class="name">{{ __('site.storefront.sankevi.families_all') }}</span>
                        <span class="go">
                            <span>{{ __('site.storefront.sankevi.card_action') }}</span>
                            <svg viewBox="0 0 26 10" aria-hidden="true"><path d="M0 5h24M20 1l4 4-4 4"/></svg>
                        </span>
                    </a>
                @endforelse
            </nav>
        </section>

        <div class="wrap">
            {{-- ============ ACT 4 — STORY ============ --}}
            @if ($theme->on('story_band'))
                <section class="story">
                    @if ($theme->on('gutter_index'))
                        <span class="gx" aria-hidden="true" style="top: 40px;"><b>{{ $theme->label('gutter_index') }} 02</b> {{ __('site.storefront.sankevi.story_eyebrow') }}</span>
                    @endif
                    <div class="art cut cut-lg" data-gv-reveal="scale">
                        @if ($storyImageUrl)
                            <img class="{{ $storyIsShipped ? 'mounted' : '' }}" src="{{ $storyImageUrl }}" alt="" loading="lazy">
                        @endif
                    </div>
                    <div class="tx" data-gv-reveal data-gv-delay="0.12">
                        <span class="kicker k">{{ __('site.storefront.sankevi.story_eyebrow') }}</span>
                        <h3>{!! __('site.storefront.sankevi.story_h2_html') !!}</h3>
                        <p>{{ $theme->copy('story_body') }}</p>

                        @if ($theme->on('ledger_strip'))
                            <div class="stats">
                                <div>
                                    <b><span data-gv-counter="{{ $yearsRunning }}">{{ $yearsRunning }}</span><i>+</i></b>
                                    <span class="lb">{{ __('site.storefront.sankevi.stat_years_label') }}</span>
                                </div>
                                <div>
                                    <b><span data-gv-counter="{{ $sourcingRadiusKm }}">{{ $sourcingRadiusKm }}</span></b>
                                    <span class="lb">{{ __('site.storefront.sankevi.stat_radius_label') }}</span>
                                </div>
                                <div>
                                    <b><span data-gv-counter="{{ $kilnMoisturePct }}">{{ $kilnMoisturePct }}</span><i>%</i></b>
                                    <span class="lb">{{ __('site.storefront.sankevi.ledger_1_k') }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            {{-- ============ THE EDITORIAL TRIO ============ --}}
            @if ($pickup->isNotEmpty())
                <section class="pickup">
                    <div class="head" data-gv-reveal>
                        <span class="kicker">{{ __('site.storefront.sankevi.pickup_eyebrow') }}</span>
                        <a class="more" href="/shop">{{ __('site.storefront.sankevi.pickup_all') }}</a>
                    </div>
                    <div class="trio">
                        @foreach ($pickup as $product)
                            @php
                                $picUrl = $product->image_path
                                    ? \Illuminate\Support\Facades\Storage::url($product->image_path)
                                    : null;
                                $catLabel = $product->relationLoaded('categories') && $product->categories->isNotEmpty()
                                    ? $product->categories->first()->name
                                    : null;
                            @endphp
                            <a class="it" href="/products/{{ $product->slug }}" data-gv-reveal data-gv-delay="{{ $loop->index * 0.1 }}">
                                <div class="pic cut {{ $picUrl ? '' : 'ph' }}">
                                    @if ($picUrl)
                                        <img src="{{ $picUrl }}" alt="{{ $product->name }}" loading="lazy">
                                    @else
                                        <span class="board-glyph" aria-hidden="true"><i></i></span>
                                    @endif
                                </div>
                                @if ($catLabel)<span class="cat">{{ $catLabel }}</span>@endif
                                <h3>{{ $product->name }}</h3>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        {{-- ============ ACT 5 — FOREST ============ --}}
        @if ($theme->on('forest_band') && $forestImageUrl)
            <figure class="forest">
                <img src="{{ $forestImageUrl }}" alt="" loading="lazy" data-gv-parallax="0.12">
                <div class="q" data-gv-reveal>
                    <blockquote>{{ $theme->copy('forest_quote') }}</blockquote>
                    <figcaption>{{ __('site.storefront.sankevi.forest_caption') }}</figcaption>
                </div>
            </figure>
        @endif

        {{-- ============ ACT 6 — THE CLOSING CALL ============ --}}
        <section class="closing">
            <div class="wrap in">
                <div data-gv-reveal>
                    <span class="kicker k">{{ __('site.storefront.sankevi.closing_eyebrow') }}</span>
                    <h2>{!! __('site.storefront.sankevi.closing_h2_html') !!}</h2>
                    <p>{{ $theme->copy('trade_body') }}</p>
                    @if ($theme->on('trade_band'))
                        <div class="rows">
                            <span class="row">{{ __('site.storefront.sankevi.trade_row1') }}</span>
                            <span class="row">{{ __('site.storefront.sankevi.trade_row2') }}</span>
                            <span class="row">{{ __('site.storefront.sankevi.trade_row3') }}</span>
                        </div>
                    @endif
                </div>
                <div class="cta" data-gv-reveal data-gv-delay="0.12">
                    <a class="btn" href="/shop">{{ __('site.storefront.sankevi.closing_shop') }}</a>
                    @if ($secondaryHref)
                        <a class="btn outline" href="{{ $secondaryHref }}">{{ $secondaryLabel }}</a>
                    @endif
                </div>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
<script>
/*
 | Sankevi landing — the two ReactBits ports, in vanilla JS.
 |
 | Both read prefers-reduced-motion once and simply do not start when it is
 | set: the markup they enhance is already legible and complete without them
 | (the bands render as static type, the menu rows stay plain links).
 */
(function () {
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ── ACT 2 — Scroll Velocity ──────────────────────────────────────────
     | ReactBits drives a motion-value: a base creep, multiplied by how fast
     | the page is moving, signed by which way it is moving, wrapped modulo
     | one sequence width. This is the same idea on requestAnimationFrame.
     |
     | Velocity is read from Lenis when the storefront kit has it (that is
     | the number the page is ACTUALLY scrolling at under smooth scroll) and
     | falls back to the raw window.scrollY delta otherwise. The kit is a
     | deferred module, so window.gv does not exist yet when this classic
     | script runs — hence the lookup happens per frame, not once.
     */
    (function () {
        var band = document.querySelector('[data-vel]');
        if (!band || reduced) return;

        var rows = [].slice.call(band.querySelectorAll('[data-vel-row]')).map(function (row) {
            return {
                el: row,
                seq: row.firstElementChild,
                dir: parseFloat(row.getAttribute('data-vel-dir')) || 1,
                base: parseFloat(row.getAttribute('data-vel-base')) || 0.05,
                x: 0,
                w: 1,
            };
        });
        if (!rows.length) return;

        // Fill each row with enough copies of its sequence that the modulo
        // wrap always has content on both sides of the viewport.
        function measure() {
            rows.forEach(function (r) {
                var need = window.innerWidth * 2 + r.seq.getBoundingClientRect().width;
                var guard = 0;
                while (r.el.scrollWidth < need && guard++ < 14) {
                    r.el.appendChild(r.seq.cloneNode(true));
                }
                r.w = r.seq.getBoundingClientRect().width || 1;
            });
        }
        measure();
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(measure).catch(function () {});
        }
        var rt;
        window.addEventListener('resize', function () {
            clearTimeout(rt);
            rt = setTimeout(measure, 180);
        }, { passive: true });

        var lastY = window.scrollY || 0;
        var lastT = 0;
        var sign = 1;      // which way the page last moved
        var boost = 0;     // eased velocity multiplier
        var skew = 0;      // eased lean, purely cosmetic
        var live = true;   // paused while the band is off-screen
        var raf = 0;

        function velocity() {
            var kit = window.gv && window.gv.lenis;
            if (kit && typeof kit.velocity === 'number') return kit.velocity;
            var y = window.scrollY || 0;
            var d = y - lastY;
            lastY = y;
            return d;
        }

        function frame(t) {
            var dt = lastT ? Math.min(t - lastT, 48) : 16;
            lastT = t;

            var v = velocity();
            if (v > 0.06) sign = 1;
            else if (v < -0.06) sign = -1;

            // Velocity arrives as px-per-frame; /14 turns a brisk flick into
            // roughly a 5× multiplier and a slow drag into almost nothing.
            var target = Math.min(Math.abs(v) / 14, 5);
            boost += (target - boost) * 0.12;
            var wantSkew = Math.max(-3.5, Math.min(3.5, v * -0.1));
            skew += (wantSkew - skew) * 0.1;

            for (var i = 0; i < rows.length; i++) {
                var r = rows[i];
                r.x += r.dir * sign * r.base * (1 + boost) * dt;
                var p = ((r.x % r.w) + r.w) % r.w;
                r.el.style.transform = 'translate3d(' + (p - r.w).toFixed(2) + 'px,0,0) skewX(' + skew.toFixed(2) + 'deg)';
            }

            if (live) raf = requestAnimationFrame(frame);
        }
        raf = requestAnimationFrame(frame);

        // Nothing animates while nobody can see it.
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                var onScreen = entries[0].isIntersecting;
                if (onScreen && !live) {
                    live = true;
                    lastT = 0;
                    raf = requestAnimationFrame(frame);
                } else if (!onScreen && live) {
                    live = false;
                    cancelAnimationFrame(raf);
                }
            }, { rootMargin: '120px 0px' }).observe(band);
        }
    })();

    /* ── ACT 3 — Flowing Menu ─────────────────────────────────────────────
     | ReactBits animates the panel in from whichever horizontal edge of the
     | row the cursor crossed. A full-width row is far wider than it is tall,
     | so the honest edge here is the vertical one: we compare the pointer's
     | Y against the row's mid-line and enter from the nearer side, then
     | leave toward whichever side the pointer left by.
     |
     | The side lives in a custom property (--fm-idle) that the idle
     | transform reads. Switching sides while the panel is hidden would
     | otherwise transition it right across the row, so the transition is
     | suppressed for exactly one reflow while we move it.
     |
     | Touch and keyboard get the same reveal, from the bottom, on
     | touchstart / focus — the row is never hover-only. Without JS the panel
     | simply stays off-canvas and the row is a plain link.
     */
    [].forEach.call(document.querySelectorAll('[data-fm-row]'), function (row) {
        var panel = row.querySelector('[data-fm-marquee]');
        if (!panel) return;

        function edge(e) {
            var r = row.getBoundingClientRect();
            var y = (e && typeof e.clientY === 'number' && e.clientY) ? e.clientY : r.top + r.height / 2;
            return (y - r.top) < r.height / 2 ? '-101%' : '101%';
        }
        function show(from) {
            panel.style.transition = 'none';
            row.style.setProperty('--fm-idle', from);
            void panel.offsetHeight;          // commit the move before animating
            panel.style.transition = '';
            row.classList.add('is-on');
        }
        function hide(to) {
            // No suppression here: setting the exit side and dropping the class
            // in the same tick resolves to one transition, out that way.
            row.style.setProperty('--fm-idle', to);
            row.classList.remove('is-on');
        }

        row.addEventListener('pointerenter', function (e) {
            if (e.pointerType === 'touch') return;   // touch is handled below
            show(edge(e));
        });
        row.addEventListener('pointerleave', function (e) {
            if (e.pointerType === 'touch') return;
            hide(edge(e));
        });
        row.addEventListener('focus', function () { show('101%'); });
        row.addEventListener('blur', function () { hide('101%'); });

        // On touch the reveal plays under the finger while the tap resolves
        // into a navigation; if the tap is abandoned it retracts.
        var back;
        row.addEventListener('touchstart', function () {
            clearTimeout(back);
            show('101%');
        }, { passive: true });
        row.addEventListener('touchend', function () {
            back = setTimeout(function () { hide('101%'); }, 700);
        }, { passive: true });
        row.addEventListener('touchcancel', function () { hide('101%'); }, { passive: true });
    });
})();
</script>
@endpush
