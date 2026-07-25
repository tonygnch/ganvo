<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>
    @include('storefront.partials.mode-boot')

    {{-- Sankevi hard-codes its typography, like the other curated themes:
         Alegreya (a calligraphic, slightly restless serif — the voice of the
         story) over Commissioner (a quiet humanist grotesque that carries the
         specs). Both ship real Cyrillic AND Cyrillic-ext, which is the whole
         reason they were chosen: this storefront is Bulgarian first.

         REPLACED by an industrial pairing at the merchant's request: Oswald
         over IBM Plex Sans. Oswald is a condensed signage gothic — the
         lettering of crate stencils, grading stamps and machine plates, which
         is what a sawmill's own type actually looks like. IBM Plex Sans was
         drawn as an engineering face and carries the specs without arguing
         with it.

         Cyrillic was the binding constraint, and it eliminated most of the
         obvious answers: Anton, Bebas Neue, Archivo Black, Barlow Condensed,
         Antonio, Big Shoulders, Saira Condensed, Chakra Petch and Space
         Grotesk are all Latin-only, and a Bulgarian-first storefront cannot
         ship a headline face that cannot set its own headlines. Oswald and
         Plex both carry Cyrillic AND Cyrillic-ext, and Plex additionally
         ships Bulgarian localised forms.

         The serif pairings are still one click away in Customize theme. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500;600;700&family=IBM+Plex+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&display=swap" rel="stylesheet">

    <style>
        :root {
            /* The one merchant-controllable knob: brand accent maps to
               primary_color. Default is Sankevi's lichen — the pale grey-green
               that grows on the north face of a Rhodope pine. */
            --accent: {{ $store->primary_color ?: '#9dae86' }};
            /* Accent used as TEXT. The moss reads at 5.9:1 on the bark ground
               but only 2.8:1 on Daylight — below even the 3.0 large-text floor —
               so it has to be darkened per mode. Darkening it globally instead
               would drop the bark case to 3.7:1, which is why this is a separate
               token rather than a tweak to --accent. Derived with color-mix so it
               still follows the merchant's own primary_color. */
            --accent-ink: var(--accent);
            /* Darkened accent for ink on a pale ground. The mix ratio is tuned
               so ONE value clears AA on the daylight mode and still reads as a
               border/edge on the bark ground — no second knob for merchants. */
            --accent-deep: color-mix(in srgb, var(--accent) 52%, #16200f);
            --display: "Oswald", "Arial Narrow", "Helvetica Neue Condensed", sans-serif;
            /* Kept as an alias only. Nothing about this theme is a serif any
               more; any shared markup reaching for --serif gets the gothic. */
            --serif: var(--display);
            --body: "IBM Plex Sans", "Helvetica Neue", sans-serif;
            /* Sankevi has no monospace voice — specs are set in the companion
               with wide tracking. Alias kept so any shared markup that reaches
               for --mono lands on the body face instead of a browser default. */
            --mono: var(--body);

            /* Core palette — damp bark ground, birch cream ink, deep moss as
               the supporting slab. Warm off-black throughout: every neutral
               carries yellow, never blue. */
            --bg: #15120d;
            --surface: #1d1912;
            --surface2: #262117;
            --line: #332c21;
            --line2: #473e2f;
            --txt: #f1ebdd;
            --muted: #b3aa97;
            --faint: #8a806d;
            --moss: #3e4e36;
            --moss-soft: #4c5f42;
            --deep: #100e0a;
            /* Ink that sits ON the accent fill. Near-black green clears AA
               (7.0:1) against the default lichen; a merchant who picks a very
               dark accent should pick a light one instead — same trade the
               other curated themes make. */
            --on-accent: #14180e;
            --header-height: 76px;
            /* The planed corner — how deep the chamfer bites. */
            --notch: 13px;

            /* Legacy aliases so the SHARED partials (pagination, variant
               picker, collection strips, checkout extras, shipping methods,
               stripe payment, sticky ATC, cart drawer, quick view) render in
               this palette without knowing anything about it. */
            --primary: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 15%, var(--surface));
            /* Follows the ink, not the ground: brighter than --accent on bark,
               darker than it in daylight mode. One declaration, both modes. */
            --primary-strong: color-mix(in srgb, var(--accent) 68%, var(--txt));
            --secondary: var(--moss);
            --border: var(--line);
            --text: var(--txt);
            --text-muted: var(--muted);
            --text-soft: var(--faint);
            --card: var(--surface);
            --ink: var(--txt);
            --soft: var(--surface2);

            /* Variant picker (flat list AND the dependent option matrix):
               near-square chips, accent fill when chosen. */
            --vp-radius: 3px;
            --vp-fill: var(--accent);
            --vp-on-accent: var(--on-accent);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        body {
            background: var(--bg);
            color: var(--txt); font-family: var(--body); font-weight: 300; line-height: 1.65; font-size: 16.5px;
            min-height: 100vh; overflow-x: hidden;
        }
        /* Bark texture — irregular vertical striations, barely there. An
           overlay rather than a background layer so palette presets can swap
           --bg without losing the grain. */
        body::before {
            content: ""; position: fixed; inset: 0; z-index: 0; pointer-events: none; opacity: .5;
            background:
                repeating-linear-gradient(90deg, rgba(255, 246, 227, .022) 0 1px, transparent 1px 7px),
                repeating-linear-gradient(90deg, rgba(0, 0, 0, .10) 0 1px, transparent 1px 23px),
                radial-gradient(120% 60% at 50% -10%, rgba(157, 174, 134, .05), transparent 60%);
        }
        body.no-grain::before { display: none; }
        body > * { position: relative; z-index: 1; }
        ::selection { background: var(--accent); color: var(--on-accent); }

        /* ===== Daylight — the visitor-toggle alternate. The token map lives
           in manifest.php ('modes'); only what tokens cannot carry is retuned
           here. Moss stays a dark slab in both modes: it is a material, not a
           background. ===== */
        html[data-mode="light"] { --accent-ink: color-mix(in srgb, var(--accent) 64%, #12160b); }
        html[data-mode="light"] body::before {
            opacity: .7;
            background:
                repeating-linear-gradient(90deg, rgba(60, 44, 22, .03) 0 1px, transparent 1px 7px),
                repeating-linear-gradient(90deg, rgba(60, 44, 22, .05) 0 1px, transparent 1px 23px);
        }
        html[data-mode="light"] header.site { background: color-mix(in srgb, var(--bg) 86%, transparent); }
        html[data-mode="light"] .pcard .pic, html[data-mode="light"] .ph { box-shadow: 0 18px 34px -26px rgba(40, 32, 18, .5); }

        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .wrap { max-width: 1280px; margin: 0 auto; padding: 0 40px; }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
        input:focus, select:focus, textarea:focus { box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 20%, transparent); }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }

        /* ===== THE PLANED CORNER — the signature. Two opposite corners are
           cut off every photograph, panel, chip and filled button, the way a
           board end is chamfered before it leaves the mill. One utility, used
           everywhere; the merchant can switch the whole idea off. ===== */
        .cut { clip-path: polygon(var(--notch) 0, 100% 0, 100% calc(100% - var(--notch)), calc(100% - var(--notch)) 100%, 0 100%, 0 var(--notch)); }
        .cut-sm { --notch: 8px; }
        .cut-lg { --notch: 26px; }
        body.no-cut .cut { clip-path: none; }

        /* ===== THE GUTTER INDEX — the second half of the signature: a small
           letterspaced label stood on its end in the left margin with a
           hairline growing out of it, the way an atelier numbers its plates.
           Absolutely positioned, so it never touches the content column. ===== */
        .gx {
            position: absolute; left: -34px; top: 4px; z-index: 2;
            writing-mode: vertical-rl; text-orientation: mixed;
            font-size: 10.5px; font-weight: 500; letter-spacing: .34em; text-transform: uppercase;
            color: var(--faint); white-space: nowrap; pointer-events: none;
            display: flex; align-items: center; gap: 14px;
        }
        .gx::after { content: ""; width: 1px; height: 62px; background: linear-gradient(180deg, var(--line2), transparent); }
        .gx b { color: var(--accent-ink); font-weight: 500; }
        body.no-gx .gx { display: none; }
        @media (max-width: 1180px) { .gx { display: none; } }

        /* label voice — the spec register: wide tracking, small, quiet */
        .kicker, .spec { font-family: var(--body); font-size: 11px; font-weight: 500; letter-spacing: .26em; text-transform: uppercase; color: var(--faint); }
        .kicker em { font-style: normal; color: var(--accent-ink); }
        .num { font-variant-numeric: tabular-nums; }

        /* buttons — filled accent with a cut corner; the outline sibling is a
           bare hairline that fills on hover. No glow, no lift: a materials
           merchant should feel machined, not bouncy. */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            font-family: var(--body); font-size: 12px; font-weight: 600; letter-spacing: .18em; text-transform: uppercase;
            padding: 15px 30px; border: 1px solid var(--accent); background: var(--accent); color: var(--on-accent);
            clip-path: polygon(11px 0, 100% 0, 100% calc(100% - 11px), calc(100% - 11px) 100%, 0 100%, 0 11px);
            transition: background-color .25s ease, color .25s ease, border-color .25s ease;
        }
        .btn:hover { background: color-mix(in srgb, var(--accent) 78%, var(--txt)); border-color: color-mix(in srgb, var(--accent) 78%, var(--txt)); }
        .btn.outline { background: transparent; color: var(--txt); border-color: var(--line2); }
        .btn.outline:hover { background: transparent; border-color: var(--accent); color: var(--accent-ink); }
        .btn.block { width: 100%; }
        .btn:disabled { opacity: .45; cursor: not-allowed; }
        body.no-cut .btn { clip-path: none; }

        /* pill — the shared micro-tag contract (partials + cards) */
        .pill { display: inline-block; font-size: 10.5px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; padding: 6px 12px; border: 1px solid var(--line2); color: var(--muted); background: transparent; }

        /* placeholder ground for any missing photograph */
        .ph { position: relative; overflow: hidden; background: linear-gradient(150deg, var(--surface2), var(--surface) 55%, #191510); }
        .ph img, .pcard .pic img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* board glyph — the imageless-product artwork: one board seen END-ON,
           sapwood edge in moss, a chamfer taken off the top corner. Deliberately
           vertical and singular so it never reads as a stack of planks. */
        .board-glyph { position: relative; width: 92px; height: 138px; }
        .board-glyph i {
            position: absolute; inset: 0; border: 1px solid var(--line2);
            background:
                repeating-linear-gradient(178deg, rgba(255, 246, 227, .085) 0 1px, transparent 1px 9px),
                linear-gradient(100deg, #3a3122, #4a3f2b 60%, #332b1d);
            clip-path: polygon(18px 0, 100% 0, 100% 100%, 0 100%, 0 18px);
        }
        .board-glyph i::after { content: ""; position: absolute; left: 0; top: 18px; bottom: 0; width: 9px; background: linear-gradient(180deg, var(--moss-soft), var(--moss)); }
        .board-glyph::after { content: ""; position: absolute; right: -9px; bottom: -9px; width: 34px; height: 1px; background: var(--accent); opacity: .7; }
        .board-glyph.sm { transform: scale(.46); }
        .board-glyph.xs { transform: scale(.3); }

        /* seam — a single hairline used as a rule, a divider, a margin mark */
        .seam { height: 1px; background: linear-gradient(90deg, var(--line2), transparent 70%); border: 0; }

        /* ===== MOTION. One orchestrated page-load reveal (.rise, delays set
           inline per element with --d) plus a restrained scroll observer
           (.reveal). Both surrender entirely to prefers-reduced-motion. ===== */
        .rise { opacity: 0; transform: translateY(24px); animation: rise 1s cubic-bezier(.19, .74, .16, 1) both; animation-delay: var(--d, 0s); }
        @keyframes rise { to { opacity: 1; transform: none; } }
        /* photographs arrive by being uncovered, not by sliding */
        .wipe { clip-path: inset(0 0 100% 0); animation: wipe 1.25s cubic-bezier(.16, .78, .18, 1) both; animation-delay: var(--d, 0s); }
        @keyframes wipe { to { clip-path: inset(0 0 0 0); } }
        .reveal { opacity: 0; transform: translateY(22px); transition: opacity .8s ease, transform .9s cubic-bezier(.19, .74, .16, 1); }
        .reveal.in { opacity: 1; transform: none; }
        .reveal.s1 { transition-delay: .09s; } .reveal.s2 { transition-delay: .18s; } .reveal.s3 { transition-delay: .27s; } .reveal.s4 { transition-delay: .36s; }
        @media (prefers-reduced-motion: reduce) {
            .rise, .wipe { animation: none !important; opacity: 1 !important; transform: none !important; clip-path: none !important; }
            .reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
        }

        /* ticker — the announcement runs on a moss band, the one solid colour
           above the fold */
        .tick { background: var(--moss); color: #f1ebdd; overflow: hidden; white-space: nowrap; }
        .tick .track { display: inline-flex; gap: 34px; padding: 9px 0; animation: tick var(--tick-dur, 28s) linear infinite; font-size: 11px; font-weight: 500; letter-spacing: .22em; text-transform: uppercase; will-change: transform; }
        .tick .track .s { color: var(--accent-ink); }
        .tick.link a { color: inherit; }
        @keyframes tick { to { transform: translateX(-50%); } }
        .tick[data-static="1"] .track { animation: none; }
        @media (prefers-reduced-motion: reduce) { .tick .track { animation-play-state: paused; } }

        /* ===== header — a thin rule over the yard gate ===== */
        header.site { position: sticky; top: 0; z-index: 60; background: color-mix(in srgb, var(--bg) 86%, transparent); backdrop-filter: blur(14px); border-bottom: 1px solid var(--line); }
        .nav { display: flex; align-items: center; gap: 34px; height: var(--header-height); }
        /* the wordmark never gives ground to the nav — it only ellipsises once
           the phone breakpoint hands it a min-width */
        .logo { display: inline-flex; align-items: center; gap: 11px; flex-shrink: 0; font-family: var(--display); font-weight: 600; font-size: 27px; letter-spacing: .2em; text-transform: uppercase; color: var(--txt); white-space: nowrap; }
        /* The seal is painted as a background so a single rule can swap the
           colourway on mode change; as an <img> the src would need JS. */
        .seal, .fseal { background-image: var(--seal); background-size: contain; background-repeat: no-repeat; background-position: center; }
        html[data-mode="light"] .seal, html[data-mode="light"] .fseal { background-image: var(--seal-day); }
        .logo .seal { display: block; width: 26px; height: 26px; flex-shrink: 0; }
        .logo img.brand { height: 30px; width: auto; }
        .nav .links { display: flex; gap: 28px; align-items: center; font-size: 12px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); }
        .nav .links > a { position: relative; padding: 4px 0; }
        .nav .links > a::after { content: ""; position: absolute; left: 0; right: 100%; bottom: 0; height: 1px; background: var(--accent); transition: right .3s cubic-bezier(.19, .74, .16, 1); }
        .nav .links > a:hover { color: var(--txt); }
        .nav .links > a:hover::after { right: 0; }
        .nav .right { margin-left: auto; display: flex; gap: 20px; align-items: center; font-size: 12px; font-weight: 500; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); }
        .nav .right a, .nav .right summary { white-space: nowrap; }
        .nav .right a:hover { color: var(--txt); }
        .bag { color: var(--txt); display: inline-flex; align-items: baseline; gap: 7px; }
        .bag .n { color: var(--accent-ink); font-variant-numeric: tabular-nums; }
        .bag .ico { display: none; width: 17px; height: 17px; align-self: center; }
        .menu-toggle { display: none; background: none; border: none; color: var(--txt); font-size: 20px; line-height: 1; padding: 6px; z-index: 80; }

        /* dropdown — language, currency and the merchant's nav groups */
        .menu { position: relative; }
        .menu summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; color: var(--muted); user-select: none; }
        .menu summary::-webkit-details-marker, .menu summary::marker { display: none; content: none; }
        .menu summary:hover { color: var(--txt); }
        .menu .chev { width: 11px; height: 11px; fill: none; stroke: currentColor; stroke-width: 1.5; transition: transform .25s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items { position: absolute; top: calc(100% + 14px); right: 0; min-width: 210px; background: var(--surface); border: 1px solid var(--line2); padding: 7px; z-index: 70; box-shadow: 0 26px 50px -26px rgba(0, 0, 0, .8); animation: menuIn .24s cubic-bezier(.19, .74, .16, 1); }
        @keyframes menuIn { from { opacity: 0; transform: translateY(-7px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .menu-items { animation: none; } }
        .menu-items a { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; font-size: 13px; letter-spacing: .06em; text-transform: none; color: var(--txt); }
        .menu-items a:hover { background: var(--surface2); }
        .menu-items a.active { color: var(--accent-ink); }
        .menu-items .check { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .menu-items a:not(.active) .check { visibility: hidden; }
        .menu.nav-menu .menu-items { left: 0; right: auto; min-width: 230px; }
        .menu.nav-menu summary { font-size: 12px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; }
        .menu.nav-menu .menu-items a[data-depth]:not([data-depth="0"]) { padding-left: calc(12px + 14px * var(--d, 0)); color: var(--muted); }
        .menu.nav-menu .menu-items a.view-all { color: var(--accent-ink); }

        /* mobile drawer — the whole forest floor, links set big in the serif */
        .m-drawer { position: fixed; inset: 0; z-index: 90; background: var(--deep); color: var(--txt); display: flex; flex-direction: column; justify-content: center; padding: 0 26px; opacity: 0; visibility: hidden; transition: opacity .45s ease, visibility .45s; }
        .m-drawer.open { opacity: 1; visibility: visible; }
        .m-drawer::after { content: ""; position: absolute; inset: 0; pointer-events: none; background: radial-gradient(80% 50% at 100% 100%, color-mix(in srgb, var(--moss) 55%, transparent), transparent 70%); }
        .m-drawer .mclose { position: absolute; top: 22px; right: 24px; background: none; border: none; font-size: 24px; color: var(--txt); z-index: 2; }
        .m-drawer .mlogo { position: absolute; top: 24px; left: 26px; font-family: var(--display); font-weight: 700; font-size: 19px; letter-spacing: .16em; text-transform: uppercase; z-index: 2; }
        .m-drawer nav { position: relative; z-index: 2; display: flex; flex-direction: column; }
        .m-drawer nav a { font-family: var(--display); font-weight: 500; font-size: clamp(30px, 9vw, 44px); line-height: 1.25; display: flex; align-items: baseline; gap: 14px; opacity: 0; transform: translateY(16px); transition: opacity .5s ease, transform .55s cubic-bezier(.19, .74, .16, 1); }
        .m-drawer.open nav a { opacity: 1; transform: none; }
        .m-drawer.open nav a:nth-child(1) { transition-delay: .07s; } .m-drawer.open nav a:nth-child(2) { transition-delay: .13s; }
        .m-drawer.open nav a:nth-child(3) { transition-delay: .19s; } .m-drawer.open nav a:nth-child(4) { transition-delay: .25s; }
        .m-drawer.open nav a:nth-child(5) { transition-delay: .31s; } .m-drawer.open nav a:nth-child(6) { transition-delay: .37s; }
        .m-drawer nav a .ix { font-family: var(--body); font-size: 11px; font-weight: 500; letter-spacing: .2em; color: var(--accent-ink); }
        .m-drawer .mfoot { position: absolute; bottom: 30px; left: 26px; right: 26px; z-index: 2; font-size: 11px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); opacity: 0; transition: opacity .5s ease .4s; }
        .m-drawer.open .mfoot { opacity: 1; }
        @media (prefers-reduced-motion: reduce) { .m-drawer nav a, .m-drawer .mfoot { opacity: 1 !important; transform: none !important; transition: none !important; } }

        /* ===== section + page heads. The headline is the loudest thing on
           any page; the label above it is the quietest. ===== */
        .sec-head { position: relative; display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin: 96px 0 34px; padding-bottom: 18px; border-bottom: 1px solid var(--line); }
        .sec-head .kicker { display: block; margin-bottom: 14px; }
        .sec-head h2 { font-family: var(--display); font-weight: 500; font-size: clamp(27px, 3.6vw, 44px); line-height: 1.02; letter-spacing: 0; }
        .sec-head h2 em { font-style: normal; font-weight: 600; color: var(--accent-ink); }
        .sec-head .more { font-size: 11px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); border-bottom: 1px solid var(--line2); padding-bottom: 4px; transition: color .25s ease, border-color .25s ease; }
        .sec-head .more:hover { color: var(--accent-ink); border-color: var(--accent); }
        .page-head { position: relative; padding: 54px 0 28px; border-bottom: 1px solid var(--line); }
        .page-head .crumb { font-size: 11px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }
        .page-head .crumb a:hover { color: var(--accent-ink); }
        .page-head h1 { font-family: var(--display); font-weight: 500; font-size: clamp(30px, 4vw, 52px); line-height: 1.02; letter-spacing: 0; margin-top: 14px; }
        .page-head h1 em { font-style: normal; font-weight: 600; color: var(--accent-ink); }
        .page-head p { color: var(--muted); max-width: 52ch; margin-top: 12px; font-size: 15.5px; }

        /* ===== product card — a photograph, a sheet number, a name in the
           serif and a price. Every grid numbers its cards like plates in a
           catalogue raisonné (CSS counters). ===== */
        .shelf { display: grid; grid-template-columns: repeat(4, 1fr); gap: 46px 26px; padding-top: 10px; counter-reset: sheet; }
        /* a column, so the price rules line up across a row even when one name
           runs to two lines */
        .pcard { display: flex; flex-direction: column; color: inherit; position: relative; counter-increment: sheet; }
        .pcard .pic { position: relative; aspect-ratio: 4 / 5; overflow: hidden; display: grid; place-items: center; background: linear-gradient(150deg, var(--surface2), #191510); margin-bottom: 16px; }
        .pcard .pic::before { content: ""; position: absolute; inset: 0; z-index: 2; background: linear-gradient(180deg, transparent 55%, rgba(16, 14, 10, .55)); opacity: 0; transition: opacity .45s ease; }
        .pcard:hover .pic::before { opacity: 1; }
        .pcard .pic img { transition: transform 1.1s cubic-bezier(.19, .74, .16, 1); }
        .pcard:hover .pic img { transform: scale(1.045); }
        /* the sheet number — set beside the category like a plate number in a
           catalogue, never stamped over the photograph */
        .pcard .line { display: flex; align-items: baseline; gap: 11px; font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }
        .shelf .pcard .sheet { color: var(--accent-ink); }
        .shelf .pcard .sheet::after { content: var(--sheet-label, "№ ") counter(sheet, decimal-leading-zero); }
        .shelf.no-sheet .pcard .sheet { display: none; }
        .pcard .badge { position: absolute; top: 12px; right: 13px; z-index: 3; font-size: 10px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; padding: 5px 10px; background: var(--accent); color: var(--on-accent); }
        .pcard h3 { font-family: var(--display); font-weight: 500; font-size: 23px; line-height: 1.15; margin: 6px 0 12px; transition: color .3s ease; }
        .pcard:hover h3 { color: var(--accent-ink); }
        .pcard .foot { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-top: auto; padding-top: 10px; border-top: 1px solid var(--line); }
        .pcard .pr { font-family: var(--display); font-weight: 500; font-size: 19px; font-variant-numeric: tabular-nums; }
        .pcard .add { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); transition: color .3s ease; }
        .pcard:hover .add { color: var(--accent-ink); }
        @media (prefers-reduced-motion: reduce) { .pcard .pic img, .pcard:hover .pic img { transform: none; transition: none; } }

        /* ===== footer — the yard at closing time ===== */
        footer.site { position: relative; overflow: hidden; margin-top: 110px; padding: 74px 0 34px; background: var(--deep); border-top: 1px solid var(--line); }
        footer.site .fseal { position: absolute; right: -60px; bottom: -70px; width: 320px; aspect-ratio: 1; opacity: .05; pointer-events: none; filter: grayscale(1); }
        .fgrid { display: grid; grid-template-columns: 2.2fr 1fr 1fr 1fr; gap: 46px; }
        .fgrid .logo { font-size: 23px; }
        .fgrid .ftag { color: var(--muted); max-width: 32ch; margin-top: 18px; font-size: 14.5px; }
        .fcol h4 { font-size: 10.5px; font-weight: 500; letter-spacing: .24em; text-transform: uppercase; color: var(--faint); margin-bottom: 18px; }
        .fcol a { display: block; font-size: 14.5px; margin-bottom: 11px; color: var(--muted); transition: color .25s ease; }
        .fcol a:hover { color: var(--accent-ink); }
        .fbot { display: flex; justify-content: space-between; gap: 18px; flex-wrap: wrap; margin-top: 58px; padding-top: 24px; border-top: 1px solid var(--line); font-size: 11px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; color: var(--faint); }
        .fbot a { color: var(--muted); }
        .fbot a:hover { color: var(--accent-ink); }

        /* toast */
        .toast { position: fixed; top: calc(var(--header-height) + 20px); right: 24px; z-index: 100; display: flex; align-items: center; gap: 12px; background: var(--surface); color: var(--txt); border: 1px solid var(--line2); border-left: 2px solid var(--accent); padding: 15px 22px; font-size: 14px; box-shadow: 0 26px 50px -26px rgba(0, 0, 0, .85); animation: toastIn .28s ease-out, toastOut .3s ease-in 3.2s forwards; }
        @keyframes toastIn { from { transform: translateY(-8px); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-8px); } }

        @media (max-width: 1000px) {
            .shelf { grid-template-columns: repeat(3, 1fr); gap: 36px 20px; }
            .fgrid { grid-template-columns: 1fr 1fr; gap: 34px; }
        }
        /* The header runs out of room well before the rest of the page does, and
           it is Bulgarian that decides when: НАЧАЛО МАГАЗИН КОНТАКТИ measures
           278px and the utility cluster 328px, so with the wordmark they need
           ~815px before a single gap. At 760 the links were still on show with
           only 688px to sit in, and the cluster was clipped — the cart silently
           ran off the right edge. These two breakpoints are the header's own;
           the page keeps its existing ones. */
        @media (max-width: 900px) {
            .nav .links { display: none; }
            .menu-toggle { display: block; }
        }
        @media (max-width: 640px) {
            .nav { gap: 10px; }
            .nav .right { gap: 13px; font-size: 11px; letter-spacing: .08em; }
            .nav .right .acct { display: none; }
        }
        @media (max-width: 760px) {
            .wrap { padding: 0 22px; }
            .shelf { grid-template-columns: repeat(2, 1fr); gap: 30px 16px; }
            .sec-head { margin: 66px 0 26px; }
            footer.site { margin-top: 76px; padding-top: 56px; }
        }
        @media (max-width: 430px) {
            /* The utility cluster is where Bulgarian overflows first: "Количка"
               and "Моят профил" run half again as long as CART and MY ACCOUNT.
               So on a phone the account link goes (the drawer carries it), the
               cart word becomes a bag — visually only, its label stays in the
               accessible name — the gaps tighten, the nav seal goes, and the
               wordmark is finally allowed to ellipsise. */
            .wrap { padding: 0 18px; }
            .nav { gap: 10px; }
            .nav .right { gap: 13px; font-size: 11px; letter-spacing: .08em; }
            .nav .right .acct { display: none; }
            .logo { font-size: 17px; letter-spacing: .1em; gap: 8px; min-width: 0; }
            .logo span { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
            .logo .seal { display: none; }
            .bag { gap: 5px; }
            .bag .ico { display: block; }
            .bag .lbl { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
            .toast { left: 18px; right: 18px; }
        }
    </style>
    {!! $theme->headExtras() !!}
    {{-- Storefront kit — motion (GSAP/Lenis) + cart drawer / quick-view (Alpine). --}}
    @vite(['resources/css/storefront.css', 'resources/js/storefront.js'])
</head>
{{-- Long, soft easing and a slow scroll: this storefront sells a forest
     before it sells a cutting list. --}}
<body class="{{ $theme->on('planed_corner') ? '' : 'no-cut' }} {{ $theme->on('gutter_index') ? '' : 'no-gx' }} {{ $theme->on('bark_texture') ? '' : 'no-grain' }}"
      data-gv-motion='{"duration":1.15,"ease":"power3.out","distance":34,"stagger":0.11,"scroll":{"lerp":0.09,"wheelMultiplier":0.95}}'>
    @php
        $csAnnouncement = $store->announcementBar();
        // The contact and history pages can each be switched off per store;
        // don't leave a footer link pointing at a route that 404s.
        $csContactOn = $store->contactPage()['enabled'];
        $csAboutOn = $store->aboutPage()['enabled'];
        $csNavMenu = $store->navMenuItems();
        $customer = auth('customer')->user();
        $currentLocale = app()->getLocale();
        $languages = \App\Http\Middleware\SetLocale::available();
        $supportedCurrencies = $store->supportedDisplayCurrencies();
        $cartCount = \App\Services\Cart::forCurrent()->itemCount();
        $logoUrl = $store->logo_path
            ? \Illuminate\Support\Facades\Storage::url($store->logo_path)
            : null;
        // The seal doubles as the nav mark when no logo was uploaded, and as
        // the ghost watermark in the footer.
        //
        // Two colourways: the shipped mark is cream, which is right on the bark
        // ground but disappears on the Daylight header. So when the slot is
        // still OUR file, pair it with the walnut master and let CSS swap them
        // on data-mode. A merchant who uploaded their own seal gets it in both
        // modes — we cannot recolour someone else's artwork, and silently
        // showing nothing would be worse than showing it twice.
        $csSeal = $theme->on('brand_seal') ? $theme->image('seal_image') : null;
        $csSealDaylight = ($csSeal && str_contains($csSeal, 'mark-cream.svg'))
            ? str_replace('mark-cream.svg', 'mark.svg', $csSeal)
            : $csSeal;
    @endphp

    @if ($csAnnouncement['enabled'] && $csAnnouncement['text'] !== '')
        @php
            $tape = trim($csAnnouncement['text']);
            $tapeUnit = e($tape) . ' &nbsp;<span class="s">/</span>&nbsp; ';
            $tapeHalf = str_repeat($tapeUnit, 4);
            $isStatic = (int) $csAnnouncement['speed_px'] === 0;
        @endphp
        <div class="tick {{ $csAnnouncement['link'] ? 'link' : '' }}" data-tick data-pps="{{ (int) $csAnnouncement['speed_px'] }}"
             @if ($isStatic) data-static="1" @endif aria-label="{{ $tape }}">
            <div class="track" aria-hidden="true">
                @if ($csAnnouncement['link'])
                    <a class="tick-half" href="{{ $csAnnouncement['link'] }}" tabindex="-1">{!! $tapeHalf !!}</a><a class="tick-half" href="{{ $csAnnouncement['link'] }}" tabindex="-1">{!! $tapeHalf !!}</a>
                @else
                    <span class="tick-half">{!! $tapeHalf !!}</span><span class="tick-half">{!! $tapeHalf !!}</span>
                @endif
            </div>
            @if ($csAnnouncement['link'])<a href="{{ $csAnnouncement['link'] }}" class="sr-only">{{ $tape }}</a>@endif
        </div>
    @endif

    <header class="site">
        <div class="wrap">
            <div class="nav">
                <button class="menu-toggle" aria-label="{{ __('site.storefront.nav.shop') }}">☰</button>
                <a class="logo" href="/">
                    @if ($logoUrl)
                        <img class="brand" src="{{ $logoUrl }}" alt="{{ $tenant->name }}">
                    @else
                        @if ($csSeal)<span class="seal" aria-hidden="true" style="--seal: url('{{ $csSeal }}'); --seal-day: url('{{ $csSealDaylight }}');"></span>@endif
                        <span>{{ $tenant->name }}</span>
                    @endif
                </a>
                <div class="links">
                    @if (! empty($csNavMenu))
                        @foreach ($csNavMenu as $item)
                            @if (! empty($item['children']))
                                <details class="menu nav-menu">
                                    <summary><span>{{ $item['label'] }}</span><svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                                    <div class="menu-items" role="menu">
                                        @if ($item['url'])<a role="menuitem" href="{{ $item['url'] }}" class="view-all">{{ __('site.storefront.featured.browse_all') }}</a>@endif
                                        @foreach ($item['children'] as $child)
                                            @php $depth = (int) ($child['depth'] ?? 0); @endphp
                                            <a role="menuitem" href="{{ $child['url'] }}" data-depth="{{ $depth }}" @if ($depth > 0) style="--d: {{ $depth }};" @endif>{{ $child['label'] }}</a>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                            @endif
                        @endforeach
                    @else
                        {{-- Fallback nav, for a store that has not built its own
                             menu. The landing page is the brand; the catalogue
                             lives at /shop — so "Shop" is a destination here,
                             never an anchor into a grid the home no longer has. --}}
                        <a href="/shop">{{ __('site.storefront.nav.shop') }}</a>
                        @if ($csAboutOn)<a href="/about">{{ __('site.storefront.footer.about') }}</a>@endif
                        @if ($csContactOn)<a href="/contact">{{ __('site.storefront.footer.contact') }}</a>@endif
                    @endif
                </div>
                <div class="right">
                    @include('storefront.partials.mode-toggle')
                    @if (count($languages) > 1)
                        <details class="menu">
                            <summary aria-label="{{ __('site.lang.switch') }}"><span>{{ strtoupper($currentLocale) }}</span><svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                            <div class="menu-items" role="menu">
                                @foreach ($languages as $code => $name)
                                    <a role="menuitem" href="/lang/{{ $code }}" class="@if($currentLocale===$code) active @endif"><span>{{ $name }}</span><svg class="check" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10l4 4 8-8"/></svg></a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                    @if (count($supportedCurrencies) > 1)
                        <details class="menu">
                            <summary aria-label="{{ __('site.currency.switch') }}"><span>{{ $displayCurrency }}</span><svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                            <div class="menu-items" role="menu">
                                @foreach ($supportedCurrencies as $code)
                                    <a role="menuitem" href="/currency/{{ $code }}" class="@if($displayCurrency===$code) active @endif"><span>{{ \App\Services\Money::symbol($code) }} · {{ $code }}</span><svg class="check" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10l4 4 8-8"/></svg></a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                    @if ($store->showsAccountUi())
                        <a class="acct" href="{{ $customer ? '/account' : '/account/login' }}">{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</a>
                    @endif
                    <a class="bag" href="/cart">
                        {{-- The label is only ever hidden VISUALLY (see the phone
                             breakpoint), never removed: the accessible name has
                             to stay "Cart 3" once the icon takes over. --}}
                        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 11.2a1.5 1.5 0 0 1-1.5 1.3H7.7a1.5 1.5 0 0 1-1.5-1.3Z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/></svg>
                        <span class="lbl">{{ __('site.common.cart') }}</span><span class="n">{{ $cartCount }}</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="m-drawer" id="mDrawer">
        <div class="mlogo">{{ $tenant->name }}</div>
        <button class="mclose" id="mClose" aria-label="{{ __('site.storefront.product.close') }}">✕</button>
        <nav>
            @php $mIx = 0; @endphp
            @if (! empty($csNavMenu))
                @foreach ($csNavMenu as $item)
                    <a href="{{ $item['url'] ?: '/' }}"><span class="ix">{{ sprintf('%02d', ++$mIx) }}</span>{{ $item['label'] }}</a>
                @endforeach
            @else
                <a href="/shop"><span class="ix">{{ sprintf('%02d', ++$mIx) }}</span>{{ __('site.storefront.nav.shop') }}</a>
                @if ($csAboutOn)<a href="/about"><span class="ix">{{ sprintf('%02d', ++$mIx) }}</span>{{ __('site.storefront.footer.about') }}</a>@endif
                @if ($csContactOn)<a href="/contact"><span class="ix">{{ sprintf('%02d', ++$mIx) }}</span>{{ __('site.storefront.footer.contact') }}</a>@endif
            @endif
            @if ($store->showsAccountUi())
                <a href="{{ $customer ? '/account' : '/account/login' }}"><span class="ix">{{ sprintf('%02d', ++$mIx) }}</span>{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</a>
            @endif
            <a href="/cart"><span class="ix">{{ sprintf('%02d', ++$mIx) }}</span>{{ __('site.common.cart') }}</a>
        </nav>
        <div class="mfoot">{{ __('site.storefront.sankevi.hero_kicker') }}</div>
    </div>

    @if (session('cart.flash'))
        <div class="toast">{{ session('cart.flash') }}</div>
    @endif

    @yield('content')

    <footer class="site">
        @if ($csSeal)<span class="fseal" aria-hidden="true" style="--seal: url('{{ $csSeal }}'); --seal-day: url('{{ $csSealDaylight }}');"></span>@endif
        <div class="wrap">
            <div class="fgrid">
                <div>
                    <div class="logo">
                        @if ($csSeal)<span class="seal" aria-hidden="true" style="--seal: url('{{ $csSeal }}'); --seal-day: url('{{ $csSealDaylight }}');"></span>@endif
                        <span>{{ $tenant->name }}</span>
                    </div>
                    <p class="ftag">{{ __('site.storefront.footer.tagline') }}</p>
                </div>
                <div class="fcol">
                    <h4>{{ __('site.storefront.footer.col_shop') }}</h4>
                    {{-- The stock book, then whatever sections the merchant
                         actually keeps — the footer follows the same split as
                         the nav: the catalogue is never the home page. --}}
                    <a href="/shop">{{ __('site.storefront.footer.all_products') }}</a>
                    @foreach (array_slice($csNavMenu, 0, 4) as $item)
                        @if ($item['url'] && str_starts_with($item['url'], '/categories/'))
                            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                        @endif
                    @endforeach
                    <a href="/cart">{{ __('site.common.cart') }}</a>
                </div>
                <div class="fcol">
                    <h4>{{ __('site.storefront.footer.col_help') }}</h4>
                    <a href="#">{{ __('site.storefront.footer.shipping') }}</a>
                    <a href="#">{{ __('site.storefront.footer.returns') }}</a>
                    @if ($csContactOn)<a href="/contact">{{ __('site.storefront.footer.contact') }}</a>@endif
                    @if ($csAboutOn)<a href="/about">{{ __('site.storefront.footer.about') }}</a>@endif
                </div>
                {{-- Account links and the language switcher share this column.
                     Guard the COLUMN as well as the switcher: with one locale
                     and account UI off it would otherwise render empty and the
                     footer grid would carry a blank track. --}}
                @if ($store->showsAccountUi() || count($languages) > 1)
                    <div class="fcol">
                        @if ($store->showsAccountUi())
                            <h4>{{ __('site.common.my_account') }}</h4>
                            <a href="{{ $customer ? '/account' : '/account/login' }}">{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</a>
                        @endif
                        @if (count($languages) > 1)
                            <h4 style="margin-top: {{ $store->showsAccountUi() ? '24px' : '0' }};">{{ __('site.lang.switch') }}</h4>
                            @foreach ($languages as $code => $name)
                                <a href="/lang/{{ $code }}">{{ $name }}</a>
                            @endforeach
                        @endif
                    </div>
                @endif
            </div>
            <div class="fbot">
                <span>© {{ date('Y') }} {{ $tenant->name }} — {{ __('site.common.all_rights') }}</span>
                <span>{!! __('site.common.powered_by', ['brand' => '<a href="http://' . config('ganvo.central_domain') . ':8000" target="_blank" rel="noopener">Ganvo</a>']) !!}</span>
            </div>
        </div>
    </footer>

    <script>
        // Ticker — set duration from the merchant's px/sec rate so perceived
        // speed is length-independent. The track holds two identical halves and
        // translates -50%; duration = halfWidth / pps. data-pps="0" = static.
        (function () {
            var bar = document.querySelector('[data-tick]');
            if (! bar) return;
            var pps = parseInt(bar.getAttribute('data-pps'), 10) || 0;
            if (pps <= 0) return;
            var half = bar.querySelector('.tick-half');
            if (! half) return;
            function apply() {
                var w = half.getBoundingClientRect().width;
                if (! w) return;
                var dur = Math.max(8, Math.min(180, w / pps));
                bar.style.setProperty('--tick-dur', dur.toFixed(2) + 's');
            }
            apply();
            if (document.fonts && document.fonts.ready) document.fonts.ready.then(apply).catch(function () {});
            var t; window.addEventListener('resize', function () { clearTimeout(t); t = setTimeout(apply, 150); }, { passive: true });
        })();

        // Mobile drawer.
        (function () {
            var drawer = document.getElementById('mDrawer');
            var toggle = document.querySelector('.menu-toggle');
            var close = document.getElementById('mClose');
            if (! drawer || ! toggle) return;
            function open() { drawer.classList.add('open'); document.body.style.overflow = 'hidden'; }
            function shut() { drawer.classList.remove('open'); document.body.style.overflow = ''; }
            toggle.addEventListener('click', function (e) { e.stopPropagation(); open(); });
            if (close) close.addEventListener('click', shut);
            drawer.querySelectorAll('nav a').forEach(function (a) { a.addEventListener('click', shut); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') shut(); });
        })();

        // Reveal on scroll.
        (function () {
            if (! ('IntersectionObserver' in window)) {
                document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('in'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });
            document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
        })();
    </script>
    @include('storefront.partials.cart-drawer')
    @include('storefront.partials.quick-view')

    @stack('scripts')
</body>
</html>
