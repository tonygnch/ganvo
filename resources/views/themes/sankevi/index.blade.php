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
         |   /       this file — six acts, no grid, NO PRODUCTS AT ALL
         |   /shop   themes.sankevi.shop — the catalogue, filters, pagination
         |
         | StorefrontController@index already re-routes a visitor who is
         | searching/filtering/paginating to the shop view, so nothing here has
         | to double as a product list. Not one SKU appears below: the merchant
         | asked for the mill, not the stock, and every road out of this page
         | leads to /shop. ($products is therefore untouched here.)
         |
         | The running order, and why:
         |
         |   1  HERO           the yard, once, at full height
         |   2  OPTION WHEEL   the families, straight under the fold (id="shop")
         |   3  OFFER          what this yard can actually DO for you
         |   4  MARQUEE        scroll-velocity bands — the name, moving
         |   5  WHY            the reasons to order it here, and the facts
         |   6  FOREST         where the wood comes from
         |   7  CLOSING        the one ask on the page
         |
         | Two things moved at the merchant's request, and they moved together.
         | The FAMILIES came up to sit directly under the hero and got bigger —
         | the first question a visitor has is "what do you cut", so the answer
         | is now the first thing under the fold rather than the fourth act.
         | And the STORY went away entirely: the family, the four generations
         | and the history now live in one place, /about, instead of being told
         | half here and half there. What stands in its place is what the
         | merchant actually sells — the capabilities (act 3) and the reasons
         | (act 5). The landing page describes the mill's work; the About page
         | describes the family.
         |
         | Motion comes from the shared storefront kit (Lenis smooth scroll,
         | data-gv-reveal / -parallax / -counter) plus two ReactBits ports
         | written in vanilla JS at the foot of the file. All of it surrenders
         | to prefers-reduced-motion.
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

        // The hero's third line: the three things the yard does FOR the caller,
        // as separate plates rather than one run-on sentence. It is a single
        // merchant-editable field, so it is split on the separator they type
        // and falls back to being one plate if they type no separator at all.
        $heroNote = trim((string) $theme->copy('hero_note'));
        $heroPills = array_values(array_filter(array_map(
            'trim',
            preg_split('/\s*[·|]\s*/u', $heroNote) ?: []
        ), fn ($p) => $p !== ''));

        // The workshop photograph moved to /about with the story it belonged
        // to; nothing on this page reads the story_image slot any more.
        $forestImageUrl = $theme->image('forest_image');
        $csSeal = $theme->on('brand_seal') ? $theme->image('seal_image') : null;

        // The option wheel's photography, mapped by category ORDER. A category
        // that carries its own image uses that instead — a merchant who
        // photographs their own families should see their own families.
        //
        // The list is CYCLED, not indexed: the merchant owns their categories
        // and can add an eighth from the admin panel at any time. Indexing
        // straight into a fixed list left every extra family with no picture.
        $familyArt = [
            asset('/images/demo/sankevi/beams.webp'),
            asset('/images/demo/sankevi/letvi.webp'),
            asset('/images/demo/sankevi/floor.webp'),
            asset('/images/demo/sankevi/lamperia.webp'),
            asset('/images/demo/sankevi/pervazi.webp'),
            asset('/images/demo/sankevi/mebeli.webp'),
            asset('/images/demo/sankevi/deck.webp'),
        ];

        $founded = (int) ($store->aboutPage()['founded_year'] ?? 0);

        /*
         | FACTS FOR THE COUNTERS — the merchant's, not ours.
         |
         | These were three hard-coded numbers with a comment calling them "yard
         | constants": 50 years, a 40 km sourcing radius, 12% kiln moisture. Two
         | of the three describe a business that fells and dries its own timber,
         | and the 50 was a fallback that appeared whenever a merchant had not
         | filled in a founding year — which is to say, it was a number the site
         | invented and then animated at the visitor.
         |
         | Each is a copy slot now. Value and label come from the same place, so
         | correcting one cannot leave the other describing something else.
         |
         | The value is free text; the leading digits drive the count-up and the
         | remainder is the suffix, so "25+" and "12%" both work and a value with
         | no digits ("FSC") renders as a plain word instead of animating from
         | zero.
         */
        $factStrip = [];
        foreach ([1, 2, 3] as $n) {
            $raw = trim((string) $theme->copy("fact_{$n}_value"));
            $label = trim((string) $theme->copy("fact_{$n}_label"));

            if ($raw === '' || $label === '') {
                continue;   // a half-filled slot is left out, not rendered blank
            }

            preg_match('/^\s*(\d+)(.*)$/u', $raw, $m);

            $factStrip[] = [
                'count'  => isset($m[1]) ? (int) $m[1] : null,
                'text'   => isset($m[1]) ? $m[1] : $raw,
                'suffix' => isset($m[2]) ? trim($m[2]) : '',
                'label'  => $label,
            ];
        }

        // The marquee is built from the merchant's own name so it is never
        // Sankevi-specific: NAME · SAWMILL · SINCE 1974 · RHODOPES ·
        $marqueeWords = array_values(array_filter([
            $tenant->name,
            __('site.storefront.sankevi.marquee_trade'),
            $founded ? __('site.storefront.sankevi.marquee_since', ['year' => $founded]) : null,
            __('site.storefront.sankevi.marquee_place'),
        ]));

        // THE WHEEL'S ITEMS — one per family, then one that opens the whole
        // yard. Built once, here, so the wheel, its plates and the no-JS list
        // all read from the same array and can never disagree about order,
        // artwork or destination.
        $wheelItems = [];
        foreach ($categories->values() as $i => $cat) {
            $wheelItems[] = [
                'name' => $cat->name,
                'href' => '/shop?category=' . $cat->slug,
                'art' => $cat->image_path
                    ? \Illuminate\Support\Facades\Storage::url($cat->image_path)
                    : $familyArt[$i % count($familyArt)],
            ];
        }
        $wheelItems[] = [
            'name' => __('site.storefront.sankevi.families_all'),
            'href' => '/shop',
            // The establishing shot of the whole yard — the only place on this
            // page it still earns its keep now that the hero shows end-grain.
            'art' => asset('/images/demo/sankevi/yard.webp'),
        ];

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
        /* THE HEADLINE, RISING OUT OF A SLOT. The mask takes its height from
           the h1 in normal flow and the h1 is moved with a transform, which
           takes no part in layout — so the slot is always exactly as tall as
           the type inside it, at any width, and can never clip a line that
           rewraps later. (That is the failure mode of splitting a headline
           into per-line masks at load: narrow the window afterwards and the
           second line is guillotined. This cannot.) */
        /* THE HEADLINE, RISING OUT OF A SLOT.
           No overflow:hidden wrapper any more. That masked the rise, but it
           also cut the DESCENDERS off the finished headline: line-height .9 is
           tighter than the glyphs, so the ink overflowed its own box by 25px
           at 88px, and Cyrillic is full of descenders — р, у, д, ц, щ, ф. The
           tail of "размер" was sliced flat. (The padding meant to allow for it
           was written in em on the WRAPPER, where em resolves against the
           wrapper's 16.5px, not the headline's 88px — it came to 0.99px.)

           The wipe is now the headline's own clip-path, and it ENDS on a
           negative inset, i.e. clipping nothing. So the reveal still runs and
           the finished type is never cut. --h1-size carries the size so the
           slide distance scales with it instead of guessing a percentage. */
        .hero { --h1-size: clamp(36px, 6.1vw, 88px); }
        .hero h1 {
            font-family: var(--display); font-weight: 500;
            font-size: var(--h1-size); line-height: .9; letter-spacing: 0;
            max-width: 15ch; text-wrap: balance;
            text-shadow: 0 2px 40px rgba(9, 7, 4, .5);
            animation: h1Rise 1.2s cubic-bezier(.16, .78, .18, 1) both;
            animation-delay: var(--d, 0s);
        }
        @keyframes h1Rise {
            from { clip-path: inset(0 0 100% 0); transform: translateY(28%); }
            /* negative inset = the finished headline is not clipped at all,
               so every descender survives */
            to   { clip-path: inset(-0.4em 0 -0.4em 0); transform: none; }
        }
        .hero h1 em { font-style: normal; font-weight: 600; color: var(--accent-ink); }
        .hero .sub { max-width: 46ch; margin-top: clamp(18px, 3vh, 30px); font-size: 16.5px; color: rgba(244, 239, 226, .92); text-shadow: 0 1px 12px rgba(9, 7, 4, .8); }

        /* THE CAPABILITY PLATES. One merchant-editable line, broken on its own
           separator into the three answers every caller wants before they ask
           for a price. Each opens with a short accent rule that draws itself
           out as the plate arrives — the same gesture the capability grid
           below repeats, so the page has one signature for "here is a fact". */
        .hero .pills { display: flex; flex-wrap: wrap; gap: 10px 28px; margin-top: clamp(20px, 3.4vh, 34px); list-style: none; padding: 0; }
        .hero .pills li { display: flex; align-items: center; gap: 11px; font-size: 11px; font-weight: 500; letter-spacing: .17em; text-transform: uppercase; color: rgba(244, 239, 226, .84); text-shadow: 0 1px 10px rgba(9, 7, 4, .8); }
        .hero .pills li::before {
            content: ""; width: 20px; height: 1px; background: var(--accent); flex-shrink: 0;
            transform-origin: 0 50%; transform: scaleX(0);
            animation: ruleDraw .9s cubic-bezier(.16, .78, .18, 1) both;
            animation-delay: calc(var(--d, 0s) + var(--i, 0) * .1s);
        }
        @keyframes ruleDraw { to { transform: scaleX(1); } }

        .hero .foot { display: flex; align-items: flex-end; justify-content: space-between; gap: 30px; margin-top: clamp(24px, 4.4vh, 48px); }
        .hero .cta { display: flex; flex-wrap: wrap; gap: 12px; }
        /* the hero is a night frame in both modes, so its buttons are lit for
           the photograph rather than for --txt */
        .hero .cta .btn.ghost { color: #f4efe2; border-color: rgba(244, 239, 226, .38); background: rgba(11, 9, 6, .22); }
        .hero .cta .btn.ghost:hover { border-color: var(--accent); color: var(--accent-ink); }
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
           ACT 4 — SCROLL VELOCITY. A ReactBits "Scroll Velocity" port: two
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
            font-family: var(--display); font-weight: 500; font-size: clamp(32px, 5.4vw, 78px);
            line-height: 1.06; letter-spacing: 0; text-transform: uppercase;
        }
        .vel-item i { font-style: normal; color: var(--accent-ink); font-size: .6em; transform: translateY(-.06em); }
        /* the second band is the same words cut out of the ground — outline
           only, so the two together read as one thick, woven rule */
        .vel-row.ghost .vel-item { color: transparent; -webkit-text-stroke: 1px var(--line2); }
        .vel-row.ghost .vel-item i { color: color-mix(in srgb, var(--accent) 55%, transparent); -webkit-text-stroke: 0; }

        /* =================================================================
           ACT 3 — OFFER. What used to stand here was the STORY: the workshop
           photograph beside four generations of manifesto. The merchant asked
           for the family and the history to live in the About page, so this
           act says the other half of what a landing page owes a visitor —
           not who we are, but what we can do with your list.

           Six capabilities on a hairline grid, built out of the gaps between
           the cells rather than six separate borders, so the rules are 1px at
           every zoom level and never double up where two cells meet. Each
           cell opens with an accent rule that draws itself as the cell scrolls
           into view — scroll-driven, no script, and simply absent (rule at
           full width) on a browser without view() timelines.
           ================================================================= */
        .offer { position: relative; padding: clamp(96px, 16vh, 180px) 0 0; }
        .offer .head { max-width: 64ch; }
        .offer h2 { font-family: var(--display); font-weight: 500; font-size: clamp(27px, 3.6vw, 50px); line-height: 1.01; letter-spacing: 0; margin-top: 14px; }
        .offer h2 em { font-style: normal; font-weight: 600; color: var(--accent-ink); }
        .offer .lead { margin-top: 22px; max-width: 56ch; color: var(--muted); font-size: 16px; }

        .offer .grid {
            display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1px; background: var(--line); border: 1px solid var(--line);
            margin-top: clamp(44px, 7vh, 76px);
        }
        .offer .cell { position: relative; background: var(--bg); padding: clamp(28px, 3vw, 46px); transition: background .45s ease; }
        .offer .cell:hover { background: var(--surface); }
        .offer .cell .n { display: block; font-size: 10.5px; font-weight: 500; letter-spacing: .24em; color: var(--faint); font-variant-numeric: tabular-nums; }
        .offer .cell .rule { display: block; width: 44px; height: 2px; margin: 20px 0 22px; background: var(--accent); transform-origin: 0 50%; }
        .offer .cell h3 { font-family: var(--display); font-weight: 500; font-size: clamp(19px, 1.7vw, 25px); line-height: 1.16; letter-spacing: 0; }
        .offer .cell p { margin-top: 13px; color: var(--muted); font-size: 14.5px; line-height: 1.62; }

        /* The rule's RESTING state is full width — that is what a browser
           without view() timelines shows, and what the print/no-JS reading
           of the page is. The scroll-driven version therefore needs its own
           keyframe with an EXPLICIT from: reusing the hero's `to`-only
           ruleDraw here would take its implicit from off the element's own
           computed transform (none, i.e. scaleX(1)) and animate nothing. */
        @keyframes ruleGrow { from { transform: scaleX(0); } to { transform: scaleX(1); } }
        @media (prefers-reduced-motion: no-preference) {
            @supports (animation-timeline: view()) {
                .offer .cell .rule {
                    animation: ruleGrow linear both;
                    animation-timeline: view();
                    animation-range: entry 14% entry 56%;
                }
            }
        }

        /* =================================================================
           ACT 5 — WHY. The reasons, beside a column that stays put while they
           pass: the heading, the three counted facts and the one door to the
           history. The sticky aside IS the animation here — the numbers hold
           still and the arguments move against them.
           ================================================================= */
        .why { position: relative; padding: clamp(96px, 16vh, 180px) 0 0; }
        .why .in { display: grid; grid-template-columns: minmax(0, .86fr) minmax(0, 1.14fr); gap: clamp(34px, 6vw, 92px); align-items: start; }
        .why .aside { position: sticky; top: calc(var(--header-height) + 46px); }
        .why h2 { font-family: var(--display); font-weight: 500; font-size: clamp(26px, 3.3vw, 44px); line-height: 1.02; letter-spacing: 0; margin-top: 14px; }
        .why h2 em { font-style: normal; font-weight: 600; color: var(--accent-ink); }

        /* The counted facts. The value lives in its own <span> because the kit
           rewrites that element's text — the label rule below is scoped to
           .lb so it can never shrink the number itself. */
        .why .facts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: clamp(30px, 4vh, 44px); padding-top: 28px; border-top: 1px solid var(--line); }
        .why .facts b { display: flex; align-items: baseline; font-family: var(--display); font-weight: 500; font-size: clamp(26px, 2.8vw, 38px); line-height: 1; font-variant-numeric: tabular-nums; }
        .why .facts b i { font-style: normal; color: var(--accent-ink); }
        .why .facts .lb { display: block; margin-top: 10px; font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); }

        /* The one door to the family and the years — the whole reason the
           story left this page. Underlined on the hover, drawn from the left,
           like every other rule on the yard. */
        .why .hist { display: inline-flex; align-items: center; gap: 13px; margin-top: 34px; font-size: 11px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--accent-ink); }
        .why .hist::after { content: ""; width: 30px; height: 1px; background: currentColor; transition: width .5s cubic-bezier(.19, .74, .16, 1); }
        .why .hist:hover::after { width: 52px; }

        .why .list { list-style: none; margin: 0; padding: 0; }
        .why .list li { display: grid; grid-template-columns: 34px minmax(0, 1fr); gap: clamp(16px, 2vw, 28px); padding: clamp(24px, 3.2vh, 34px) 0; border-top: 1px solid var(--line); }
        .why .list li:last-child { border-bottom: 1px solid var(--line); }
        .why .list .ix { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; color: var(--accent-ink); font-variant-numeric: tabular-nums; padding-top: .5em; }
        .why .list h3 { font-family: var(--display); font-weight: 500; font-size: clamp(21px, 2vw, 29px); line-height: 1.12; letter-spacing: 0; }
        .why .list p { margin-top: 11px; color: var(--muted); font-size: 15px; max-width: 52ch; }

        /* =================================================================
           ACT 2 — THE OPTION WHEEL. A ReactBits port in vanilla JS.

           What it replaced: seven full-width rows, ~850px of page, to say
           seven words. The merchant's brief was "i don't want to take too
           much space", so the families now live on a turning arc no taller
           than a paragraph, with the chosen family's plate beside it. Read
           it as a stock-book index being turned — a hinge on the left, one
           line on the read-line, the rest curving away.

           GEOMETRY (all of it lives in the JS; the CSS only consumes it):
           the radius is derived so that the ARC between two neighbours is
           exactly one row height — R = rowH / tiltRad. Every item then gets
           --ow-x / --ow-y / --ow-r for its place on the arc, and --ow-p, its
           normalised distance from the centre, which is what drives the fade
           and the blur below. One custom property per axis means the whole
           look is tunable here without touching a line of script.

           WITHOUT JS none of that applies: .ow has no .is-live class, so the
           rules below never match and the markup renders as what it is — a
           plain vertical list of links beside a photograph.

           SIZE. This act used to be squeezed under ~460px because it was the
           fourth thing on the page and the brief was "don't take too much
           space". It has since been moved directly under the hero and asked
           to be bigger, which is a different brief: it is now the first
           answer the page gives, so the row height, the type and the plate
           all step up roughly a third. What it must NOT become again is the
           1350px flowing menu it replaced — the whole act still lands inside
           a single screen on a laptop, which is the actual constraint. */
        .families { position: relative; padding: clamp(76px, 12vh, 140px) 0 0; }
        .families .head { position: relative; display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; flex-wrap: wrap; padding-bottom: 20px; }
        /* Sized with the other acts now that this one opens the page. The
           plate's family name still runs larger — the photograph is where the
           display moment belongs — but the heading no longer whispers. */
        .families .head h2 { font-family: var(--display); font-weight: 500; font-size: clamp(27px, 3.5vw, 48px); line-height: 1.02; letter-spacing: 0; margin-top: 14px; }
        .families .head h2 em { font-style: normal; font-weight: 600; color: var(--accent-ink); }
        .families .head .hint { font-size: 11px; font-weight: 500; letter-spacing: .24em; text-transform: uppercase; color: var(--faint); padding-bottom: 6px; }
        /* The hint describes a gesture that only exists once the wheel is
           live. With JS off it would be instructions for a control that
           isn't there, so it goes. Browsers without :has() simply keep it —
           a stale hint is a far smaller failure than a hidden one. */
        .families:not(:has(.ow.is-live)) .head .hint { display: none; }

        .ow {
            --ow-row: 62px;                       /* JS reads this — one row */
            display: grid; grid-template-columns: minmax(0, 390px) minmax(0, 1fr);
            gap: clamp(26px, 4vw, 64px); align-items: center;
            margin-top: clamp(18px, 2.6vh, 32px);
        }

        /* ── the wheel ─────────────────────────────────────────────────── */
        .ow-list { list-style: none; margin: 0; padding: 0; }
        /* WebKit still honours the proprietary flag, and it stops the drag
           image appearing before dragstart can be cancelled */
        .ow-item a { -webkit-user-drag: none; display: inline-flex; align-items: baseline; gap: 15px; padding: 8px 0; color: var(--txt); font-family: var(--display); font-weight: 500; font-size: 23px; line-height: 1.3; transition: color .3s ease; }
        /* --accent-ink, not --accent: these are the act's only accent-coloured
           TEXT, and the flat moss falls to 2.6:1 on the Daylight ground. The
           ink token is the moss on bark and a darkened mix in light mode, so
           the read-line label stays legible in both. Fills (the read-line
           rule itself) keep the full-strength moss. */
        .ow-item a:hover { color: var(--accent-ink); }
        .ow-ix { font-family: var(--body); font-size: 11px; font-weight: 500; letter-spacing: .2em; color: var(--faint); font-variant-numeric: tabular-nums; transition: color .3s ease; }

        .ow.is-live .ow-wheel {
            position: relative; height: calc(var(--ow-row) * 5.3); overflow: hidden;
            /* the drag owns the vertical gesture inside this one small box;
               everywhere else on the section the page scrolls normally */
            touch-action: none; cursor: grab;
            -webkit-user-select: none; user-select: none;
            /* Items two rows out are already tilted ~48°, so their tails run
               well past the box. Dissolve the top and bottom edges instead of
               cutting them: the far rows should curve away out of sight, not
               get guillotined — the same trick the velocity band uses on its
               left and right edges. */
            -webkit-mask-image: linear-gradient(180deg, transparent 0, #000 17%, #000 83%, transparent 100%);
            mask-image: linear-gradient(180deg, transparent 0, #000 17%, #000 83%, transparent 100%);
        }
        .ow.is-live .ow-wheel.is-drag { cursor: grabbing; }
        /* the binding, and the read-line the chosen family sits on */
        .ow.is-live .ow-wheel::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 1px; background: linear-gradient(180deg, transparent, var(--line2) 24%, var(--line2) 76%, transparent); }
        .ow.is-live .ow-wheel::after { content: ""; position: absolute; left: 0; top: 50%; width: 19px; height: 1px; background: var(--accent); }
        .ow.is-live .ow-list { position: absolute; inset: 0; }
        .ow.is-live .ow-item {
            position: absolute; left: 29px; top: 50%; width: max-content;
            height: var(--ow-row); margin-top: calc(var(--ow-row) / -2);
            /* hinged at the spine, like a page — not spun about its middle */
            transform-origin: 0 50%;
            transform: translate3d(var(--ow-x, 0px), var(--ow-y, 0px), 0) rotate(var(--ow-r, 0deg));
            opacity: calc(1.04 - var(--ow-p, 0) * 1.04);
            filter: blur(calc(var(--ow-p, 0) * var(--ow-p, 0) * 3.2px));
            will-change: transform, opacity;
        }
        .ow.is-live .ow-item a { height: 100%; padding: 0; align-items: center; white-space: nowrap; font-size: clamp(20px, 1.9vw, 27px); }
        .ow.is-live .ow-item.is-sel a, .ow.is-live .ow-item.is-sel .ow-ix { color: var(--accent-ink); }

        /* ── the plate ─────────────────────────────────────────────────── */
        .ow-plate { position: relative; height: calc(var(--ow-row) * 5.3); overflow: hidden; background: var(--surface2); }
        .ow-pl { position: absolute; inset: 0; display: block; color: #f4efe2; opacity: 0; transition: opacity .5s ease; }
        .ow-pl img { width: 100%; height: 100%; object-fit: cover; }
        .ow-pl::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(11, 9, 6, .04) 0%, rgba(11, 9, 6, .22) 40%, rgba(11, 9, 6, .84) 100%); }
        /* with JS off nothing is "selected", so the first family stands in */
        .ow:not(.is-live) .ow-pl:first-child { opacity: 1; }
        .ow.is-live .ow-pl.is-sel { opacity: 1; }
        .ow-pl .cap { position: absolute; z-index: 2; left: clamp(18px, 2.2vw, 30px); right: clamp(18px, 2.2vw, 30px); bottom: clamp(15px, 2vw, 24px); display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; }
        .ow-pl .nm { font-family: var(--display); font-weight: 500; font-size: clamp(26px, 3.3vw, 46px); line-height: 1.02; letter-spacing: 0; text-shadow: 0 2px 26px rgba(9, 7, 4, .6); }
        .ow-pl .go { flex-shrink: 0; display: inline-flex; align-items: center; gap: 10px; padding-bottom: 6px; font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: rgba(244, 239, 226, .78); }
        .ow-pl .go svg { width: 26px; height: 10px; fill: none; stroke: currentColor; stroke-width: 1.2; transition: transform .5s cubic-bezier(.19, .74, .16, 1); }
        .ow-pl:hover .go svg { transform: translateX(8px); }

        /* ── TURNED (phones) ──────────────────────────────────────────────
           NOT a horizontal variant of the wheel — the wheel itself, rotated a
           quarter turn. Every number that makes the arc what it is (--ow-row,
           TILT, CURVE, RANGE), the binding, the read-line tick, the index
           numerals and the edge fade are the DESKTOP ones, untouched; the only
           thing that changes is which way up the box is hung.

           An earlier attempt rebuilt a sideways wheel out of its own
           parameters — its own spacing, its own hinge, no numerals — and it
           read as a different control. This one cannot drift from the desktop
           wheel, because it IS the desktop wheel.

           -90° and not +90°: it puts item 01 at the LEFT with later items to
           the right, so a leftward drag brings the next family in from the
           right the way every horizontal carousel does. (+90° is the prettier
           turn — the binding would land under the plate rather than along the
           floor — but it runs the list right-to-left and inverts the drag.)
           The words end up reading bottom-to-top, which is the part the owner
           accepted in order to get the arc.

           Being rotated, the element's own HEIGHT is what a visitor sees as
           WIDTH: --ow-run is that on-screen width, and since CSS cannot read a
           parent's width into a length the JS publishes it. --ow-depth is the
           on-screen height — however far the longest name reaches from the
           spine. */
        .ow-turn { display: contents; }
        @media (max-width: 760px) {
            /* Only the value before JS has measured the real names — see
               measure(), which overwrites this from the widest of them. */
            .ow.is-horiz { --ow-depth: 180px; }
            .ow.is-horiz.is-live .ow-turn {
                display: block; position: relative; height: var(--ow-depth);
            }
            .ow.is-horiz.is-live .ow-wheel {
                position: absolute; top: 50%; left: 50%;
                width: var(--ow-depth);
                height: var(--ow-run, 320px);
                transform-origin: 50% 50%;
                transform: translate(-50%, -50%) rotate(-90deg);
                /* The desktop fade runs along the element's own Y — which the
                   turn carries round to the on-screen LEFT and RIGHT, exactly
                   where the arc enters and leaves, so it needs no help there.
                   The second layer feathers the element's far X edge, which
                   lands on the on-screen TOP: the outer end of every name.
                   Without it a name longer than the depth ceiling would meet a
                   bare overflow:hidden cut in the middle of a letter.
                   INTERSECTED; where mask-composite is unsupported the layers
                   merely add, which is the harmless direction to fail. */
                -webkit-mask-image:
                    linear-gradient(180deg, transparent 0, #000 17%, #000 83%, transparent 100%),
                    linear-gradient(90deg, #000 0, #000 92%, transparent 100%);
                mask-image:
                    linear-gradient(180deg, transparent 0, #000 17%, #000 83%, transparent 100%),
                    linear-gradient(90deg, #000 0, #000 92%, transparent 100%);
                -webkit-mask-composite: source-in;
                mask-composite: intersect;
                /* The column claims every gesture with `none`. Turned, it must
                   claim only the sideways ones — touch-action is read in SCREEN
                   directions, not the element's own, so pan-y leaves the browser
                   free to scroll the page on an up/down swipe while the wheel
                   still takes the left/right ones. */
                touch-action: pan-y;
            }
        }
        /* one hint or the other, never both */
        .hint-h { display: none; }
        @media (max-width: 760px) { .hint-v { display: none; } .hint-h { display: inline; } }

        /* ── REDUCED MOTION. Not "the wheel without the animation" — a wheel
           that snaps has no arc to read. JS flags .is-flat and switches the
           geometry to a plain vertical stack: no curve, no rotation, no
           blur, no smoothing, and every visible item at full legibility.
           The cubic on the fade only takes the very outermost row to zero. */
        .ow.is-flat .ow-item { filter: none; opacity: calc(1 - var(--ow-p, 0) * var(--ow-p, 0) * var(--ow-p, 0)); }
        .ow.is-flat .ow-pl { transition: none; }
        /* The edge fade exists to soften the tails of ROTATED rows; an
           upright stack has none, and dimming its outer rows would be the
           opposite of the point. TURNED is the exception — a flat run still
           leaves the box at both ends, where the fade is all that stands
           between the outer names and a hard cut. Being the same mask on the
           same element, it needs no rule of its own to get there. */
        .ow.is-flat:not(.is-horiz) .ow-wheel { -webkit-mask-image: none; mask-image: none; }

        /* =================================================================
           ACT 6 — FOREST. Full bleed, one line of type, nothing else.
           ================================================================= */
        .forest { position: relative; margin: clamp(100px, 17vh, 190px) 0; margin-left: calc(50% - 50vw); width: 100vw; min-height: 82vh; display: grid; place-items: center; overflow: hidden; }
        .forest img { position: absolute; inset: -8% 0; width: 100%; height: 116%; object-fit: cover; }
        .forest::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, var(--bg), rgba(11, 9, 6, .5) 26%, rgba(11, 9, 6, .46) 62%, var(--bg)); }
        .forest .q { position: relative; z-index: 2; text-align: center; padding: 70px 26px; max-width: 1080px; }
        .forest .q blockquote { font-family: var(--display); font-weight: 300; letter-spacing: .012em; font-size: clamp(24px, 3.6vw, 52px); line-height: 1.1; letter-spacing: 0; color: #f4efe2; text-shadow: 0 2px 34px rgba(9, 7, 4, .55); }
        .forest .q figcaption { margin-top: 30px; font-size: 10.5px; font-weight: 500; letter-spacing: .3em; text-transform: uppercase; color: var(--accent-ink); }

        /* =================================================================
           ACT 7 — THE CLOSING CALL. The one saturated field on the page, and
           the only place the page asks for anything.
           ================================================================= */
        .closing { position: relative; overflow: hidden; margin-left: calc(50% - 50vw); width: 100vw; background: var(--moss); color: #f4f0e4; padding: clamp(70px, 13vh, 140px) 0; }
        .closing::after { content: ""; position: absolute; inset: 0; pointer-events: none; background: repeating-linear-gradient(90deg, rgba(255, 255, 255, .035) 0 1px, transparent 1px 28px); }
        .closing .in { position: relative; z-index: 2; display: grid; grid-template-columns: 1.2fr .8fr; gap: clamp(30px, 5vw, 76px); align-items: end; }
        .closing .k { display: block; color: var(--accent-ink); margin-bottom: 20px; }
        .closing h2 { font-family: var(--display); font-weight: 500; font-size: clamp(28px, 4vw, 58px); line-height: 1; letter-spacing: 0; }
        .closing h2 em { font-style: normal; font-weight: 600; color: var(--accent-ink); }
        .closing p { color: rgba(244, 240, 228, .82); max-width: 46ch; margin-top: 22px; font-size: 15.5px; }
        .closing .rows { display: flex; flex-direction: column; gap: 13px; margin: 30px 0 0; }
        .closing .rows .row { display: flex; align-items: center; gap: 12px; font-size: 11px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: rgba(244, 240, 228, .8); }
        .closing .rows .row::before { content: ""; width: 18px; height: 1px; background: var(--accent); }
        .closing .cta { display: flex; flex-wrap: wrap; gap: 14px; }
        .closing .btn.outline { color: #f4f0e4; border-color: rgba(244, 240, 228, .35); }
        .closing .btn.outline:hover { border-color: var(--accent); color: var(--accent-ink); }

        /* =================================================================
           REDUCED MOTION — the whole page holds still. Nothing here is
           decoration-with-meaning, so every one of these can simply stop:
           the grain freezes, the hero stops pushing in, the scroll bead
           stops falling, and the velocity bands sit where they were
           rendered. The wheel's own reduced-motion behaviour is .is-flat,
           declared with the act; the rules here are only the belt-and-braces
           for a browser that reports "reduce" after the script has run.
           ================================================================= */
        @media (prefers-reduced-motion: reduce) {
            .grain { animation: none; }
            /* With the animation off there is no clip-path and no transform at
               all — which is exactly the finished state. Stated anyway so a
               browser that applies the from-keyframe before honouring the
               media query cannot leave the headline wiped out. */
            .hero h1 { animation: none; transform: none; clip-path: none; }
            .hero .pills li::before { animation: none; transform: none; }
            .offer .cell .rule { animation: none; transform: none; }
            .why .hist::after, .offer .cell { transition: none; }
            .hero .cue .rail::after { animation: none; top: 8px; }
            .ow.is-live .ow-item { filter: none; }
            .ow-pl, .ow-pl .go svg { transition: none; }
        }

        @media (max-width: 1100px) {
            .offer .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            /* the aside stops being a column that holds still and becomes a
               header — sticky positioning on a full-width block just pins the
               heading over the reasons it introduces */
            .why .in { grid-template-columns: minmax(0, 1fr); gap: 40px; }
            .why .aside { position: static; }
            .why .facts { max-width: 560px; }
        }
        @media (max-width: 900px) {
            .hero { height: auto; min-height: calc(100svh - var(--header-height)); padding-top: 26vh; }
            .hero .foot { flex-direction: column; align-items: flex-start; gap: 26px; }
            .hero .cue { flex-direction: row; align-items: center; gap: 14px; }
            .hero .cue .rail { width: 46px; height: 1px; }
            .hero .cue .rail::after { width: 18px; height: 3px; left: auto; top: -1px; animation-name: cueSlide; }
            .closing .in { grid-template-columns: 1fr; align-items: start; }
            .forest { min-height: 62vh; }
        }
        @keyframes cueSlide { 0% { left: -20px; } 100% { left: 48px; } }
        /* One column below 760: the plate leads, the index follows it. Two
           240px columns side by side would give the arc no room to curve
           into and the photograph no room to be a photograph. */
        @media (max-width: 760px) {
            .ow { --ow-row: 52px; grid-template-columns: minmax(0, 1fr); gap: 22px; }
            .ow-plate { order: -1; height: 250px; }

            /* THE PHONE PRESENTATION — the wheel never goes live here (see the
               script), so this styles the plain list it leaves behind: two
               columns of real, full-size tap targets, every family visible at
               once instead of one legible name on an arc. The last item
               ("Целият склад") spans both columns, because it is the way out
               rather than one more family. */
            .ow:not(.is-live) .ow-list {
                display: grid; grid-template-columns: 1fr 1fr; gap: 1px;
                background: var(--line); border: 1px solid var(--line);
            }
            .ow:not(.is-live) .ow-item { background: var(--bg); }
            .ow:not(.is-live) .ow-item:last-child { grid-column: 1 / -1; }
            .ow:not(.is-live) .ow-item a {
                display: flex; align-items: baseline; gap: 10px;
                padding: 15px 14px; min-height: 52px; font-size: 17px;
                width: 100%; box-sizing: border-box;
            }
            .ow-pl .nm { font-size: clamp(25px, 6vw, 34px); }
            .ow-pl .go span { display: none; }
        }
        @media (max-width: 620px) {
            .hero h1 { max-width: none; }
            .hero .pills { gap: 9px 20px; }
            /* One column means six cells stacked, so the generous desktop
               padding turns the act into a 1700px scroll on a phone. Tighten
               it: the cells are still separated by their own hairlines. */
            .offer .grid { grid-template-columns: minmax(0, 1fr); }
            .offer .cell { padding: 24px 22px; }
            .offer .cell .rule { margin: 15px 0 16px; }
            .why .facts { grid-template-columns: 1fr 1fr; }
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
                {{-- The brand line above the headline. Switchable, because it
                     is the one place on the page that repeats what the header
                     already says two centimetres higher up, and a merchant
                     whose headline stands on its own wants the room. --}}
                @if ($theme->on('hero_mark'))
                    <div class="mark rise" style="--d: .3s;">
                        @if ($csSeal)<img src="{{ $csSeal }}" alt="" aria-hidden="true">@endif
                        <span>{{ $csHero['title'] !== '' ? $csHero['title'] : $tenant->name }}</span>
                    </div>
                @endif
                <div class="h1w">
                    <h1 style="--d: .38s;">
                        @if ($csHero['subtitle'] !== '')
                            {{ $csHero['subtitle'] }}
                        @else
                            {!! __('site.storefront.sankevi.hero_h1_html') !!}
                        @endif
                    </h1>
                </div>
                <p class="sub rise" style="--d: .62s;">{{ __('site.storefront.sankevi.hero_sub') }}</p>

                {{-- The three answers, as plates. One editable field behind
                     them, so a merchant who sells something other than timber
                     rewrites all three at once and gets however many they
                     typed — including one. --}}
                @if ($heroPills)
                    <ul class="pills rise" style="--d: .8s;">
                        @foreach ($heroPills as $pill)
                            <li style="--d: .86s; --i: {{ $loop->index }};">{{ $pill }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="foot">
                    <div class="cta rise" style="--d: 1s;">
                        <a class="btn" href="/shop">{{ __('site.storefront.sankevi.hero_cta') }}</a>
                        @if ($contactOn)
                            <a class="btn ghost" href="/contact">{{ __('site.storefront.sankevi.hero_cta2') }}</a>
                        @endif
                    </div>
                    <span class="cue rise" style="--d: 1.12s;" aria-hidden="true">
                        <span class="rail"></span>
                        {{ __('site.storefront.sankevi.scroll_cue') }}
                    </span>
                </div>
            </div>
        </section>

        {{-- ============ ACT 2 — THE OPTION WHEEL ============
             Moved up to sit directly under the hero, and scaled up with it:
             the first question a visitor arrives with is "what do you cut",
             so it is now the first thing they are answered with.

             id="shop" so the layout's stock "#shop" links (nav + footer, which
             this view must not edit) land on the families rather than nowhere.

             Everything below is the NO-JS truth: a <nav> with an accessible
             name, a real list, real <a> elements, one photograph. The script
             adds .is-live and turns that same list into the arc — it adds no
             markup, no href and no destination of its own, so if it never
             runs the visitor loses a flourish and nothing else. --}}
        <section class="families" id="shop">
            <div class="wrap">
                <div class="head" data-gv-reveal>
                    @if ($theme->on('gutter_index'))
                        <span class="gx" aria-hidden="true">{{ __('site.storefront.sankevi.gx_home') }}</span>
                    @endif
                    <div>
                        <h2>{!! __('site.storefront.sankevi.families_h2_html') !!}</h2>
                    </div>
                    {{-- The hint describes a GESTURE, and the gesture turns with
                         the wheel: up/down on a desktop column, left/right on a
                         phone's horizontal strip. Both are rendered and the CSS
                         shows whichever matches, so no JS decides copy. --}}
                    <span class="hint hint-v">{{ __('site.storefront.sankevi.families_hint') }}</span>
                    <span class="hint hint-h">{{ __('site.storefront.sankevi.families_hint_h') }}</span>
                </div>

                <div class="ow" data-ow data-gv-reveal data-gv-delay="0.1">
                    {{-- data-lenis-prevent: the storefront kit's smooth scroll
                         listens on the window and does not honour a nested
                         preventDefault, so without this the wheel would turn
                         AND the page would slide out from under it. Scoped to
                         this one 300px box; the rest of the act scrolls. --}}
                    {{-- ow-turn only exists for the phone. A rotated element
                         still occupies its UNROTATED box in the layout, so the
                         turned wheel needs a parent that reserves the footprint
                         it actually paints into. On desktop the wrapper is
                         display:contents — no box, no effect, the wheel stays a
                         direct grid item of .ow. --}}
                    <div class="ow-turn">
                    <nav class="ow-wheel" data-ow-wheel data-lenis-prevent
                         aria-label="{{ __('site.storefront.sankevi.wheel_label') }}">
                        <ul class="ow-list">
                            @foreach ($wheelItems as $w)
                                <li class="ow-item" data-ow-item>
                                    <a href="{{ $w['href'] }}">
                                        <span class="ow-ix" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                                        <span class="ow-nm">{{ $w['name'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                    </div>

                    {{-- The plates. Every one of them repeats a link the wheel
                         already exposes by name, so they are a pointer
                         affordance only and stay out of the a11y tree — a
                         screen reader should hear seven families, not
                         fourteen. All are rendered up front so turning the
                         wheel never waits on an image. --}}
                    <div class="ow-plate cut cut-lg" data-ow-plate>
                        @foreach ($wheelItems as $w)
                            <a class="ow-pl" data-ow-pl href="{{ $w['href'] }}" tabindex="-1" aria-hidden="true">
                                <img src="{{ $w['art'] }}" alt="" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                                <span class="cap">
                                    <span class="nm">{{ $w['name'] }}</span>
                                    <span class="go">
                                        <span>{{ __('site.storefront.sankevi.card_action') }}</span>
                                        <svg viewBox="0 0 26 10" aria-hidden="true"><path d="M0 5h24M20 1l4 4-4 4"/></svg>
                                    </span>
                                </span>
                            </a>
                        @endforeach
                    </div>

                    {{-- Politely spoken when the wheel is turned by pointer or
                         scroll. Suppressed while focus is inside the wheel,
                         where the focused link is announced already. --}}
                    <p class="sr-only" role="status" aria-live="polite"
                       data-ow-status data-ow-tpl="{{ __('site.storefront.sankevi.wheel_selected', ['name' => '%s']) }}"></p>
                </div>
            </div>
        </section>

        {{-- ============ ACT 3 — OFFER ============
             The capabilities. This is the half of the old story act that
             belongs on a landing page: not the generations — those are at
             /about now — but what the yard will do with a list of sizes.
             Six cells on one hairline grid, and no photograph: the plate in
             the wheel above is the picture and the forest below is the other
             one, and three images in a row would say less than six lines. --}}
        @if ($theme->on('offer_band'))
            <section class="offer">
                <div class="wrap">
                    <div class="head" data-gv-reveal>
                        @if ($theme->on('gutter_index'))
                            <span class="gx" aria-hidden="true">{{ __('site.storefront.sankevi.offer_eyebrow') }}</span>
                        @endif
                        <h2>{!! __('site.storefront.sankevi.offer_h2_html') !!}</h2>
                        <p class="lead">{{ __('site.storefront.sankevi.offer_lead') }}</p>
                    </div>

                    <div class="grid" data-gv-reveal data-gv-delay="0.1">
                        @foreach (range(1, 6) as $n)
                            <article class="cell">
                                <span class="n" aria-hidden="true">{{ sprintf('%02d', $n) }}</span>
                                <span class="rule" aria-hidden="true"></span>
                                <h3>{{ __('site.storefront.sankevi.offer_' . $n . '_h') }}</h3>
                                <p>{{ __('site.storefront.sankevi.offer_' . $n . '_p') }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- ============ ACT 4 — SCROLL VELOCITY ============ --}}
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

        {{-- ============ ACT 5 — WHY ============
             The reasons, and the only place the landing page still mentions
             the family: one link, to the page that now tells it properly. --}}
        @if ($theme->on('why_band'))
            <section class="why">
                <div class="wrap in">
                    <div class="aside" data-gv-reveal>
                        @if ($theme->on('gutter_index'))
                            <span class="gx" aria-hidden="true">{{ __('site.storefront.sankevi.why_eyebrow') }}</span>
                        @endif
                        <h2>{!! __('site.storefront.sankevi.why_h2_html') !!}</h2>

                        @if ($theme->on('ledger_strip') && $factStrip)
                            <div class="facts">
                                @foreach ($factStrip as $fact)
                                    <div>
                                        <b>
                                            {{-- data-gv-counter only when there is
                                                 something to count to; without it the
                                                 script leaves the text alone. --}}
                                            <span @if ($fact['count'] !== null) data-gv-counter="{{ $fact['count'] }}" @endif>{{ $fact['text'] }}</span>
                                            @if ($fact['suffix'] !== '')<i>{{ $fact['suffix'] }}</i>@endif
                                        </b>
                                        <span class="lb">{{ $fact['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($aboutOn && trim(__('site.storefront.sankevi.why_about')) !== '')
                            <a class="hist" href="/about">{{ __('site.storefront.sankevi.why_about') }}</a>
                        @endif
                    </div>

                    {{-- Built first so the numbering counts the reasons that
                         SURVIVE. Looping 1..4 and skipping inside would have
                         printed 01, 02, 04 the moment a merchant cleared one. --}}
                    @php
                        $whyReasons = [];
                        foreach (range(1, 4) as $n) {
                            $h = trim(__('site.storefront.sankevi.why_' . $n . '_h'));
                            if ($h === '') {
                                continue;
                            }
                            $whyReasons[] = ['h' => $h, 'p' => trim(__('site.storefront.sankevi.why_' . $n . '_p'))];
                        }
                    @endphp
                    <ol class="list" data-gv-reveal data-gv-delay="0.1">
                        @foreach ($whyReasons as $i => $reason)
                            <li>
                                <span class="ix" aria-hidden="true">{{ sprintf('%02d', $i + 1) }}</span>
                                <div>
                                    <h3>{{ $reason['h'] }}</h3>
                                    @if ($reason['p'] !== '')<p>{{ $reason['p'] }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif

        {{-- ============ ACT 6 — FOREST ============ --}}
        @if ($theme->on('forest_band') && $forestImageUrl)
            <figure class="forest">
                <img src="{{ $forestImageUrl }}" alt="" loading="lazy" data-gv-parallax="0.12">
                <div class="q" data-gv-reveal>
                    <blockquote>{{ $theme->copy('forest_quote') }}</blockquote>
                    <figcaption>{{ __('site.storefront.sankevi.forest_caption') }}</figcaption>
                </div>
            </figure>
        @endif

        {{-- ============ ACT 7 — THE CLOSING CALL ============ --}}
        {{-- The closing band used to pitch trade quantities and send visitors
             back into the shop. It carries the merchant's actual contact
             details now: the last thing on the page is how to reach them, and
             the button goes to the contact page rather than round again.

             The three rows fall back to the trade lines when the merchant has
             no contact details on file, so the band never renders as a heading
             above nothing. --}}
        @php
            $closingContact = $store->contactPage();
            $closingRows = array_values(array_filter([
                $closingContact['phone'] ?? '',
                $closingContact['email'] ?? '',
                // Address is multi-line by nature; only the first line belongs
                // on a single chip.
                trim(strtok((string) ($closingContact['address'] ?? ''), "\n")),
            ]));
        @endphp
        <section class="closing">
            <div class="wrap in">
                <div data-gv-reveal>
                    <h2>{!! __('site.storefront.sankevi.closing_h2_html') !!}</h2>
                    <p>{{ $theme->copy('trade_body') }}</p>
                    @if ($closingRows)
                        <div class="rows">
                            @foreach ($closingRows as $row)
                                <span class="row">{{ $row }}</span>
                            @endforeach
                        </div>
                    @elseif ($theme->on('trade_band'))
                        <div class="rows">
                            <span class="row">{{ __('site.storefront.sankevi.trade_row1') }}</span>
                            <span class="row">{{ __('site.storefront.sankevi.trade_row2') }}</span>
                            <span class="row">{{ __('site.storefront.sankevi.trade_row3') }}</span>
                        </div>
                    @endif
                </div>
                <div class="cta" data-gv-reveal data-gv-delay="0.12">
                    <a class="btn" href="{{ $contactOn ? '/contact' : '/shop' }}">{{ __('site.storefront.sankevi.closing_shop') }}</a>
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
 | Both read prefers-reduced-motion once. The velocity bands simply never
 | start (they render as static type and lose nothing); the option wheel does
 | still run — it is a control, not decoration — but in a flat, snapping mode
 | with no arc and no blur. Neither one adds markup or destinations of its
 | own, so with JS off the page is a plain, complete document.
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

    /* ── ACT 4 — Option Wheel ─────────────────────────────────────────────
     | A ReactBits "Option Wheel" port. The items sit on a circular arc whose
     | radius is DERIVED rather than picked: we want the arc length between
     | two neighbours to be exactly one row height, so with `tilt` degrees of
     | arc per row,  R = rowH / tiltRad.  An item d rows from the read-line is
     | then at angle d·tiltRad, and:
     |
     |     y = R·sin(angle)                        along the arc
     |     x = -R·(1 - cos(angle))·curve           pulled back toward the axis
     |     r = angle in degrees                    tangent to the curve
     |
     | `curve` is the 0..1 knob for how far the arc leans away; at 1 the items
     | follow the true circle, at 0 they ride a straight column.
     |
     | Distance from the centre also becomes --ow-p, and the stylesheet turns
     | that one number into the fade and the blur. Anything past RANGE rows is
     | pinned at the edge with p = 1 (invisible) and taken out of the pointer
     | path, but NOT out of the document: these are navigation links and they
     | stay in the accessibility tree.
     |
     | This is a NAVIGATION control, not a form field. The centred item is the
     | active one; clicking it (or Enter/Space on it) follows its href, and
     | clicking any other item turns the wheel to it first, by the short way
     | round. Without JS none of this exists and the same markup is a list.
     */
    (function () {
        var root = document.querySelector('[data-ow]');
        if (!root) return;

        var wheel = root.querySelector('[data-ow-wheel]');
        var turn = root.querySelector('.ow-turn');
        var items = [].slice.call(root.querySelectorAll('[data-ow-item]'));
        var plates = [].slice.call(root.querySelectorAll('[data-ow-pl]'));
        var status = root.querySelector('[data-ow-status]');
        var n = items.length;
        // A yard with one door needs no wheel to choose between doors.
        if (!wheel || n < 2) return;

        var links = items.map(function (li) { return li.querySelector('a'); });
        if (links.indexOf(null) > -1) return;
        // The plate number is aria-hidden decoration; only the family's own
        // name is worth saying out loud.
        var names = items.map(function (li) {
            var el = li.querySelector('.ow-nm') || li;
            return el.textContent.replace(/\s+/g, ' ').trim();
        });

        // No arc, no rotation, no blur and no smoothing when the visitor has
        // asked for less motion — a plain stack that jumps between states.
        var flat = reduced;

        // ── parameters ────────────────────────────────────────────────────
        // TILT   degrees of arc per row. Lower curls less and shows more of
        //        the list; higher turns the far rows almost side-on. 24° puts
        //        the first neighbour at a readable slant and the third row
        //        past 70°, by which point the fade has already taken it.
        // CURVE  0.55 — enough lean that the arc reads as an arc, not so much
        //        that the second row swims out of its own column.
        // RANGE  3 rows either side. The arc turns back on itself past 90°
        //        (90/24 ≈ 3.7), so this is a geometric ceiling, not a taste.
        //        In flat mode it is also what fits: rows 1 and 2 land inside
        //        the box, row 3 is already at opacity 0.
        // TAU    0.11s — the smoothing time constant. A page being turned,
        //        not a reel coming to rest.
        var TILT = 24;
        var CURVE = 0.55;
        var RANGE = 3;
        var TAU = 0.11;
        var DRAG_PX = 4;      // below this a pointer gesture is still a click
        var LOOP = true;      // a handful of families read better as a ring
        var SETTLE = 0.001;   // stop the rAF when the wheel is this close

        var tiltRad = TILT * Math.PI / 180;
        var rowH = 46;
        var R = rowH / tiltRad;
        /* ORIENTATION. A vertical wheel in a 354px phone column showed three
           legible names out of eight with two thirds of the box empty. So on a
           phone the whole control is ROTATED a quarter turn (see the CSS): it
           then spends the width a handset actually has, and a sideways swipe is
           the natural gesture there.

           Nothing below this line knows about that. The geometry, the arc and
           every parameter stay exactly what they are on a desktop — the turn is
           one CSS transform, not a second wheel. `horiz` therefore means only
           "the box is turned", and all it changes is which SCREEN axis an input
           arrives on. */
        var horiz = false;

        // The row height is a CSS decision, so the geometry reads it back
        // rather than duplicating the media query in here.
        function measure() {
            var v = parseFloat(getComputedStyle(wheel).getPropertyValue('--ow-row'));
            rowH = v > 0 ? v : 46;
            R = rowH / tiltRad;
            /* Turned, what the layout calls the wheel's HEIGHT is what the
               visitor sees as its width, and it should fill the space the
               wrapper was given. CSS cannot read a parent's width into a
               length, so that one number is published from here — and only
               while turned, because a display:contents wrapper reports a
               clientWidth of 0. */
            if (horiz && turn) {
                root.style.setProperty('--ow-run', turn.clientWidth + 'px');
                /* And the DEPTH — how far the longest name reaches out from
                   the spine — is measured rather than guessed. Upright, a name
                   has the column's whole width to run into and the slack is
                   enormous; turned, it has only the strip's on-screen height,
                   and a hard 180px (tuned to this shop's seven names) left
                   about 6px of it. Category names are merchant-authored free
                   text: "Обработени первази" already measures 194px and would
                   have been guillotined mid-letter, at full opacity, on the
                   read-line. Clamped at both ends — never a sliver, never a
                   strip taller than the plate above it — and the far end is
                   feathered below, so a name past even the ceiling dissolves
                   instead of being cut. */
                var reach = 0;
                for (var q = 0; q < n; q++) {
                    var w = items[q].offsetLeft + items[q].offsetWidth;
                    if (w > reach) reach = w;
                }
                /* +22 is not slack, it is the feather's clearance: the mask
                   below dissolves the last 8% of the depth, and a name whose
                   tip landed inside that would be dimmed for no reason. 22px
                   is wider than 8% of any depth this can produce, so an
                   ordinary name is never touched by it and only one that has
                   pushed past the 300px ceiling ever meets it. */
                root.style.setProperty('--ow-depth',
                    Math.max(150, Math.min(reach + 22, 300)) + 'px');
            }
        }

        var cur = 0, target = 0, sel = -1, raf = 0, lastT = 0, onScreen = true;

        function norm(i) { return ((i % n) + n) % n; }
        // Signed rows from the read-line, wrapped to the SHORT way round.
        function offset(i, c) {
            var d = i - c;
            if (LOOP) d -= n * Math.round(d / n);
            return d;
        }

        function select(i) {
            if (i === sel) return;
            if (sel > -1) {
                items[sel].classList.remove('is-sel');
                links[sel].tabIndex = -1;
                links[sel].removeAttribute('aria-current');
                if (plates[sel]) plates[sel].classList.remove('is-sel');
            }
            sel = i;
            items[i].classList.add('is-sel');
            // Roving tabindex: the wheel is ONE tab stop, and the arrow keys
            // move within it — the composite-widget contract. Tabbing through
            // eight links that are 90% invisible would be the worse trade.
            links[i].tabIndex = 0;
            links[i].setAttribute('aria-current', 'true');
            if (plates[i]) plates[i].classList.add('is-sel');
        }

        function announce() {
            if (!status) return;
            // Focus inside the wheel means the browser has just read the
            // focused link out; a live region here would say it all again.
            if (wheel.contains(document.activeElement)) return;
            var tpl = status.getAttribute('data-ow-tpl') || '%s';
            status.textContent = tpl.replace('%s', names[sel]);
        }

        function render() {
            for (var i = 0; i < n; i++) {
                var d = offset(i, cur);
                /* >= and not >: the fade reaches ZERO at exactly RANGE rows
                   (p hits 1 there, and opacity is 1.04 - p * 1.04), so the row
                   AT the limit is already invisible — and `out` is what clears
                   its pointer-events. Upright, those two ghosts sat outside the
                   box and the mistake cost nothing. Turned, they park along the
                   bottom edge of a full-width strip, over blank ground a thumb
                   lands on, and a tap there jumped the wheel three places to a
                   family that was never on screen. */
                var out = Math.abs(d) >= RANGE;
                var cd = out ? (d < 0 ? -RANGE : RANGE) : d;
                var p = Math.min(1, Math.abs(d) / RANGE);
                var x = 0, y, r = 0;
                if (flat) {
                    // Reduced motion: a plain stack. No arc to read means no
                    // reason to rotate, lean or blur anything.
                    y = cd * rowH;
                } else {
                    var a = cd * tiltRad;
                    y = R * Math.sin(a);
                    x = -R * (1 - Math.cos(a)) * CURVE;
                    r = a * 180 / Math.PI;
                }
                var s = items[i].style;
                s.setProperty('--ow-x', x.toFixed(2) + 'px');
                s.setProperty('--ow-y', y.toFixed(2) + 'px');
                s.setProperty('--ow-r', r.toFixed(2) + 'deg');
                s.setProperty('--ow-p', p.toFixed(3));
                s.pointerEvents = out ? 'none' : '';
            }
            select(norm(Math.round(cur)));
        }

        /* Frame-rate independent exponential smoothing: over dt seconds the
           gap closes by 1 - e^(-dt/tau), which is the same journey at 60Hz
           and at 144Hz. A fixed per-frame lerp is not. */
        function tick(t) {
            var dt = lastT ? Math.min((t - lastT) / 1000, 0.05) : 1 / 60;
            lastT = t;
            cur += (target - cur) * (1 - Math.exp(-dt / TAU));
            if (Math.abs(target - cur) < SETTLE) {
                cur = target;
                raf = 0;
                render();
                announce();
                return;                       // arrived — no more frames
            }
            render();
            raf = requestAnimationFrame(tick);
        }

        function run() {
            // Nothing to animate toward when motion is off or nobody is
            // looking: land on the answer and stay there.
            if (flat || !onScreen || document.hidden) {
                if (raf) { cancelAnimationFrame(raf); raf = 0; }
                cur = target;
                render();
                announce();
                return;
            }
            // Already where it wants to be — do not spend a frame proving it.
            if (Math.abs(target - cur) < SETTLE) { cur = target; return; }
            if (!raf) { lastT = 0; raf = requestAnimationFrame(tick); }
        }

        function goTo(i) {
            var base = Math.round(target);
            var d = i - norm(base);
            if (LOOP) d -= n * Math.round(d / n);   // the short way round
            target = base + d;
            run();
        }

        function clampTarget() {
            if (!LOOP) target = Math.max(0, Math.min(n - 1, target));
        }

        // ── wheel / touchpad ──────────────────────────────────────────────
        // Scoped to the wheel box alone (~300 × 250) and not to the section,
        // so the page still scrolls everywhere else in this act.
        var snapT;
        wheel.addEventListener('wheel', function (e) {
            // Horizontally the sideways axis leads, but a trackpad that only
            // reports deltaY should still turn it rather than do nothing.
            var d = horiz ? (Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY) : e.deltaY;
            if (e.deltaMode === 1) d *= 16;                    // lines
            else if (e.deltaMode === 2) d *= wheel.clientHeight;   // the run, turned or not
            if (!d) return;
            e.preventDefault();
            target += d / (rowH * 1.35);
            clampTarget();
            // A wheel that stops between two families has chosen neither.
            clearTimeout(snapT);
            snapT = setTimeout(function () { target = Math.round(target); run(); }, 140);
            run();
        }, { passive: false });

        // ── pointer drag ──────────────────────────────────────────────────
        /* Every item on this wheel is an <a>, and dragging a link starts the
           browser's OWN drag-and-drop. That fires pointercancel a couple of
           moves in and kills the gesture — measured as down:1 move:2 cancel:1,
           with the wheel never turning. It went unnoticed while the wheel was
           vertical because the items sit right of the grab point; laid
           horizontally the centred link is exactly under the thumb, so every
           swipe landed on one. Refusing dragstart is the whole fix, and it
           helps the vertical wheel too whenever a drag begins on a name. */
        wheel.addEventListener('dragstart', function (e) { e.preventDefault(); });

        var down = false, moved = false, sy = 0, st = 0, pid = -1, dragEnd = 0;
        wheel.addEventListener('pointerdown', function (e) {
            if (e.button > 0) return;
            down = true; moved = false; sy = horiz ? e.clientX : e.clientY; st = target; pid = e.pointerId;
            clearTimeout(snapT);
        });
        wheel.addEventListener('pointermove', function (e) {
            if (!down) return;
            var dy = (horiz ? e.clientX : e.clientY) - sy;
            if (!moved) {
                if (Math.abs(dy) < DRAG_PX) return;   // still a click
                moved = true;
                wheel.classList.add('is-drag');
                /* Capture only once it IS a drag: capturing on pointerdown would
                   retarget the click away from the item's own <a>.

                   And NEVER for touch. A touch pointer already has IMPLICIT
                   capture — the spec keeps delivering its moves to the element
                   the touch started on — so setPointerCapture buys nothing, and
                   on a panning-capable element the browser answers it by firing
                   lostpointercapture immediately. That is wired to release(),
                   which set down = false, so every move after the first was
                   dropped: the wheel twitched about a pixel and stopped. It
                   looked like the drag was ignored; it was actually cancelled
                   one move in, by us. */
                if (e.pointerType !== 'touch') {
                    try { wheel.setPointerCapture(pid); } catch (err) {}
                }
            }
            target = st - dy / rowH;
            clampTarget();
            run();
        });
        function release() {
            if (!down) return;
            down = false;
            wheel.classList.remove('is-drag');
            if (pid > -1) { try { wheel.releasePointerCapture(pid); } catch (err) {} pid = -1; }
            if (moved) { dragEnd = Date.now(); target = Math.round(target); run(); }
        }
        wheel.addEventListener('pointerup', release);
        wheel.addEventListener('pointercancel', release);
        wheel.addEventListener('lostpointercapture', release);

        // ── clicking an item ──────────────────────────────────────────────
        links.forEach(function (a, i) {
            a.addEventListener('click', function (e) {
                // The click that ends a drag is not a click.
                if (Date.now() - dragEnd < 260) { e.preventDefault(); return; }
                if (i !== sel) { e.preventDefault(); goTo(i); }
                // The centred item IS the active one: let its href do the rest.
            });
            // Roving tabindex normally keeps focus on the centred item, but a
            // screen reader's virtual cursor or a find-link can still land
            // elsewhere. Turn the wheel to whatever has focus.
            a.addEventListener('focus', function () { if (i !== sel) goTo(i); });
        });

        // ── keyboard ──────────────────────────────────────────────────────
        wheel.addEventListener('keydown', function (e) {
            if (e.altKey || e.ctrlKey || e.metaKey) return;
            var to = -1;
            var next = horiz ? 'ArrowRight' : 'ArrowDown';
            var prev = horiz ? 'ArrowLeft' : 'ArrowUp';
            if (e.key === next) to = norm(sel + 1);
            else if (e.key === prev) to = norm(sel - 1);
            else if (e.key === 'Home') to = 0;
            else if (e.key === 'End') to = n - 1;
            else if (e.key === ' ' || e.key === 'Spacebar') {
                // A link ignores Space; on a wheel it should open the centred
                // family exactly the way Enter does.
                e.preventDefault();
                links[sel].click();
                return;
            } else {
                return;
            }
            e.preventDefault();
            goTo(to);
            links[to].focus();          // focus follows selection, never traps
        });

        // ── lifecycle ─────────────────────────────────────────────────────
        /*
         | THE WHEEL IS LIVE AT EVERY WIDTH — only its ORIENTATION changes.
         |
         | It was desktop-only once. Squeezed into a 354px phone COLUMN the arc
         | showed three legible names out of eight with two thirds of the box
         | empty, so the phone fell back to a plain list. Turning the same arc
         | on its side fixed the real problem: laid horizontally it spends the
         | width a handset actually has, and a sideways swipe is the natural
         | gesture there.
         |
         | Without JS the markup is still a plain list of real links, every
         | family visible and tappable, so nothing is duplicated to get there.
         */
        /* THE SAME QUERY STRING THE CSS USES, character for character.
           It used to be the complement, `(min-width: 761px)` negated, which is
           NOT the same predicate: a viewport 760.5px wide — routine at 125%
           Windows scaling or any non-integral zoom — satisfies neither
           `min-width: 761px` nor `max-width: 760px`. JS would flip every input
           axis to X while the CSS media query stayed unmatched and painted the
           upright column, so Up/Down did nothing on a visibly vertical wheel
           and the box swallowed the page scroll it had claimed with
           touch-action: none. One predicate, one source of truth. */
        var turned = window.matchMedia('(max-width: 760px)');

        function orient() {
            var want = turned.matches;
            if (want === horiz) return false;
            horiz = want;
            root.classList.toggle('is-horiz', horiz);
            return true;
        }

        function setLive(on) {
            if (on === root.classList.contains('is-live')) return;
            if (on) {
                // Roving tabindex: everything out of the tab order, then
                // render() puts the CENTRED item back in. Do not try to set it
                // here — `sel` is not initialised until the first render, and
                // reaching into links[sel] threw before the wheel ever went
                // live, which silently killed it on desktop too.
                links.forEach(function (a) { a.tabIndex = -1; });
                root.classList.add('is-live');
                measure(); render();
            } else {
                // hand every link back to the tab order — on the list there is
                // no "selected" row to carry focus on everyone else's behalf
                links.forEach(function (a) { a.tabIndex = 0; });
                root.classList.remove('is-live');
            }
        }

        var rz;
        window.addEventListener('resize', function () {
            clearTimeout(rz);
            rz = setTimeout(function () {
                orient();
                measure(); render();
            }, 160);
        }, { passive: true });

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (en) {
                onScreen = en[0].isIntersecting;
                if (!onScreen) run();       // snaps and cancels the rAF
            }, { rootMargin: '80px 0px' }).observe(root);
        }
        document.addEventListener('visibilitychange', function () { run(); });

        if (flat) root.classList.add('is-flat');
        orient();
        // The wheel is live at every width now — only its ORIENTATION changes.
        setLive(true);

        /* Measured once more when the webfont lands. --ow-depth is derived from
           how wide the NAMES are, and at first paint they are still set in the
           fallback face: "Целият склад" measured 136px there and 145px once
           Oswald swapped in. The strip was therefore sized 9px too shallow for
           the whole session — enough to push the tip of the longest name into
           the feather that is meant never to touch it. Harmless where the
           font is already cached; the guard is for the first visit. */
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function () { measure(); render(); });
        }
    })();
})();
</script>
@endpush
