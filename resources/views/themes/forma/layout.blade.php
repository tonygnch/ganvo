<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>

    {{-- Forma hard-codes its typography: Sora (geometric display + body),
         Space Grotesk (techy alternate) and Space Mono (spec labels). A clean,
         configurator-grade DTC pairing. The merchant's font_family setting is
         intentionally ignored, like the other curated themes. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&family=Space+Mono:ital@0;1&display=swap" rel="stylesheet">

    <style>
        :root {
            /* The one merchant-controllable knob: brand accent maps to
               primary_color. Default is Forma cobalt. */
            --accent: {{ $store->primary_color ?: '#2f4fe0' }};
            --display: 'Sora', sans-serif;
            --body: 'Sora', system-ui, sans-serif;
            --mono: 'Space Mono', monospace;
            --bg: #f3f3f1;
            --ink: #14161c;
            --soft: #e8e8e5;
            --card: #ffffff;
            --line: #e2e2df;
            --line2: #d4d4d0;
            --muted: #6a6e78;
            /* product imagery placeholder — a cobalt-tinted radial wash */
            --pcolor: var(--accent);

            --header-height: 68px;

            /* Legacy aliases so the shared partials (catalog-controls,
               variant-picker, collection-strips, pagination, stripe-payment…)
               render in the Forma palette. */
            --primary: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 12%, var(--card));
            --primary-strong: color-mix(in srgb, var(--accent) 82%, #000);
            --secondary: var(--ink);
            --surface: var(--card);
            --border: var(--line);
            --text: var(--ink);
            --text-muted: var(--muted);
            --text-soft: var(--muted);

            /* Variant picker: squared chip with accent outline when selected. */
            --vp-radius: 10px;
            --vp-fill: var(--accent);
            --vp-on-accent: #ffffff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        body { background: var(--bg); background-image: radial-gradient(140% 46% at 50% -6%, #fbfbf9, rgba(251, 251, 249, 0) 62%); color: var(--ink); font-family: var(--body); line-height: 1.6; font-size: 16px; min-height: 100vh; overflow-x: hidden; }
        /* atmosphere: a whisper of photographic grain over the whole canvas —
           engineering-paper tooth rather than a flat digital grey. */
        body::before { content: ""; position: fixed; inset: 0; z-index: 2000; pointer-events: none; opacity: .05; mix-blend-mode: multiply; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.82' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='140' height='140' filter='url(%23n)'/%3E%3C/svg%3E"); }
        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .wrap { max-width: 1280px; margin: 0 auto; padding: 0 40px; }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
        ::selection { background: var(--accent); color: #fff; }
        /* one consistent focus ring for every form control across the theme */
        input:focus-visible, select:focus-visible, textarea:focus-visible { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 16%, transparent); }

        /* ===== drafting-table utilities (shared by all pages) =====
           .fig      — italic mono figure caption, the "FIG. 01" motif
           .xmark    — a registration crosshair (print alignment mark)
           .dim      — engineering dimension line with arrowheads + label */
        .fig { font-family: var(--mono); font-style: italic; font-size: 10.5px; letter-spacing: .16em; text-transform: uppercase; color: var(--muted); }
        .fig b { font-style: italic; font-weight: 400; color: var(--accent); }
        .xmark { position: absolute; width: 15px; height: 15px; pointer-events: none; }
        .xmark::before, .xmark::after { content: ""; position: absolute; background: color-mix(in srgb, var(--ink) 26%, transparent); }
        .xmark::before { left: 7px; top: 0; bottom: 0; width: 1px; }
        .xmark::after { top: 7px; left: 0; right: 0; height: 1px; }
        .dim { position: absolute; z-index: 3; pointer-events: none; font-family: var(--mono); font-size: 10px; letter-spacing: .14em; color: var(--accent); font-variant-numeric: tabular-nums; }
        .dim::before, .dim::after { content: ""; position: absolute; width: 0; height: 0; }
        .dim-v { width: 0; border-left: 1px solid color-mix(in srgb, var(--accent) 55%, transparent); }
        .dim-v::before { left: -4px; top: -1px; border-left: 4px solid transparent; border-right: 4px solid transparent; border-bottom: 7px solid color-mix(in srgb, var(--accent) 75%, transparent); }
        .dim-v::after { left: -4px; bottom: -1px; border-left: 4px solid transparent; border-right: 4px solid transparent; border-top: 7px solid color-mix(in srgb, var(--accent) 75%, transparent); }
        .dim-v span { position: absolute; top: 50%; left: 9px; transform: translateY(-50%); writing-mode: vertical-rl; background: color-mix(in srgb, var(--bg) 82%, transparent); padding: 6px 1px; white-space: nowrap; }
        .dim-h { height: 0; border-top: 1px solid color-mix(in srgb, var(--accent) 55%, transparent); }
        .dim-h::before { top: -4px; left: -1px; border-top: 4px solid transparent; border-bottom: 4px solid transparent; border-right: 7px solid color-mix(in srgb, var(--accent) 75%, transparent); }
        .dim-h::after { top: -4px; right: -1px; border-top: 4px solid transparent; border-bottom: 4px solid transparent; border-left: 7px solid color-mix(in srgb, var(--accent) 75%, transparent); }
        .dim-h span { position: absolute; left: 50%; top: 7px; transform: translateX(-50%); background: color-mix(in srgb, var(--bg) 82%, transparent); padding: 1px 7px; white-space: nowrap; }

        /* card registration tick — every white "instrument card" across the
           inner pages carries a faint crosshair in its top-right corner. */
        .summary, .osum, .filters, .fset, .ord-card, .ord-items, .acct-side, .auth .card, .cart-empty, .acct-empty, .acct-panel, .sheet { position: relative; }
        .summary::after, .osum::after, .filters::after, .fset::after, .ord-card::after, .acct-side::after, .auth .card::after, .cart-empty::after, .acct-empty::after {
            content: ""; position: absolute; top: 11px; right: 11px; width: 15px; height: 15px; pointer-events: none; opacity: .55;
            background:
                linear-gradient(color-mix(in srgb, var(--ink) 30%, transparent), color-mix(in srgb, var(--ink) 30%, transparent)) 7px 0 / 1px 100% no-repeat,
                linear-gradient(color-mix(in srgb, var(--ink) 30%, transparent), color-mix(in srgb, var(--ink) 30%, transparent)) 0 7px / 100% 1px no-repeat;
        }

        /* placeholder fills (used wherever a real image is missing).
           Forma has no product photos — the .ph block is a hatched canvas
           swatch; .bloomph is a cobalt-tinted radial wash that reads as the
           hero "object". */
        .ph { position: relative; background: var(--soft); overflow: hidden;
            background-image: repeating-linear-gradient(45deg, rgba(20, 22, 28, .04) 0 11px, transparent 11px 22px); }
        .bloomph { background: radial-gradient(120% 120% at 50% 25%, color-mix(in srgb, var(--pcolor) 26%, #fff), color-mix(in srgb, var(--pcolor) 78%, var(--ink))); }
        .ph img, .bloomph img, .bcard .pic img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .kicker { font-family: var(--mono); font-size: 12px; letter-spacing: .04em; color: var(--accent); text-transform: none; font-weight: 400; font-variant-numeric: tabular-nums; }

        /* buttons — squared, accent fill / ghost outline. Transitions are
           deliberately short (120–140ms): Forma hovers should snap like a
           machined switch, not ease like upholstery. */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 9px; font-size: 14px; font-weight: 600; padding: 15px 28px; border: 1px solid var(--accent); background: var(--accent); color: #fff; border-radius: 10px; font-variant-numeric: tabular-nums; transition: filter .13s ease, transform .13s ease, background-color .13s ease, color .13s ease, border-color .13s ease, box-shadow .13s ease; }
        .btn:hover { filter: brightness(1.08); transform: translateY(-1px); box-shadow: 0 3px 0 -1px color-mix(in srgb, var(--accent) 35%, transparent); }
        .btn:active { transform: translateY(0); box-shadow: none; }
        .btn.dark { background: var(--ink); border-color: var(--ink); }
        .btn.outline { background: transparent; color: var(--ink); border-color: var(--line2); }
        .btn.outline:hover { border-color: var(--ink); filter: none; background: transparent; color: var(--ink); box-shadow: 0 3px 0 -1px color-mix(in srgb, var(--ink) 22%, transparent); }
        .btn.block { width: 100%; }
        .btn.lg { padding: 17px 34px; font-size: 15px; }
        .btn:disabled { opacity: .55; cursor: not-allowed; transform: none; filter: none; box-shadow: none; }
        @media (prefers-reduced-motion: reduce) { .btn:hover, .btn:active { transform: none; } }

        /* variant chips (shared partial) — same machined snap */
        .vp .vp-option-body { transition: border-color .12s ease, background-color .12s ease, color .12s ease, transform .12s ease; }
        .vp .vp-option:active .vp-option-body { transform: translateY(1px); }

        /* mono spec label helper */
        .mono { font-family: var(--mono); font-size: 12px; font-variant-numeric: tabular-nums; }

        /* tape strip — neutralised for Forma (kept so old markup hooks don't
           leak a stray block). Renders as a thin accent rule, no rotation. */
        .tape { position: absolute; width: 0; height: 0; pointer-events: none; display: none; }
        .tape.r { transform: none; }

        /* reveal on scroll */
        .reveal { opacity: 0; transform: translateY(26px); transition: opacity .7s ease, transform .8s cubic-bezier(.2, .8, .2, 1); }
        .reveal.in { opacity: 1; transform: none; }
        .reveal.s1 { transition-delay: .08s; } .reveal.s2 { transition-delay: .16s; } .reveal.s3 { transition-delay: .24s; }
        @media (prefers-reduced-motion: reduce) { .reveal { opacity: 1 !important; transform: none !important; transition: none !important; } .floaty, .spin, .tick .track { animation: none !important; } }

        /* ===== system status strip (announcement bar) =====
           Not a marketing ticker: a hardware-style status line. A steady
           "live" pip on the left, mono caps content scrolling at the
           merchant's px/sec rate. Reads like a device readout, not a sale
           banner — part of Forma's spec-sheet identity. */
        .tick { background: var(--ink); color: var(--bg); overflow: hidden; white-space: nowrap; position: relative; display: flex; align-items: center; }
        .tick::before { content: ""; flex: none; width: 7px; height: 7px; border-radius: 50%; background: var(--accent); margin: 0 14px; box-shadow: 0 0 0 0 color-mix(in srgb, var(--accent) 70%, transparent); animation: pip 2.4s ease-out infinite; }
        .tick .track { display: inline-flex; gap: 30px; padding: 8px 0; animation: tick var(--tick-dur, 26s) linear infinite; font-family: var(--mono); font-size: 11px; letter-spacing: .14em; text-transform: uppercase; will-change: transform; }
        .tick .track .s { color: var(--accent); }
        .tick.link a { color: inherit; }
        @keyframes tick { to { transform: translateX(-50%); } }
        @keyframes pip { 0% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--accent) 65%, transparent); } 70%, 100% { box-shadow: 0 0 0 6px transparent; } }
        .tick[data-static="1"] .track { animation: none; }
        @media (prefers-reduced-motion: reduce) { .tick .track { animation-play-state: paused; } .tick::before { animation: none; } }

        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }

        /* ===== header — spec-sheet console =====
           Two-tier masthead. Tier 1 (.metabar): a thin mono "instrument"
           line — fixed spec coordinates the merchant doesn't control, the
           kind of readout a hardware brand prints across the top. Tier 2
           (.nav): wordmark, the catalogue links, and a bordered utility
           "console" cluster on the right (lang / currency / account / cart)
           that reads like a control panel rather than loose icons. */
        header.site { position: sticky; top: 0; z-index: 60; background: rgba(243, 243, 241, .9); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); }
        .metabar { border-bottom: 1px solid var(--line); font-family: var(--mono); font-size: 10.5px; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); }
        .metabar .wrap { display: flex; align-items: center; justify-content: space-between; gap: 16px; height: 30px; }
        .metabar .mb-l, .metabar .mb-r { display: flex; align-items: center; gap: 18px; min-width: 0; }
        .metabar .mb-l span, .metabar .mb-r span { white-space: nowrap; }
        .metabar .ac { color: var(--accent); }
        @media (max-width: 880px) { .metabar .mb-r span:nth-child(n+2) { display: none; } .metabar .mb-l span:nth-child(n+2) { display: none; } }

        .nav { display: flex; align-items: center; gap: 34px; height: 70px; }
        .logo { font-family: var(--display); font-weight: 800; font-size: 22px; letter-spacing: -.03em; color: var(--ink); white-space: nowrap; }
        .logo img { height: 30px; width: auto; display: block; }
        .nav .links { display: flex; gap: 26px; font-size: 14.5px; align-items: center; color: var(--muted); }
        .nav .links a { position: relative; }
        .nav .links > a::before, .nav .links .menu summary::before { content: ""; }
        .nav .links a:hover { color: var(--ink); }
        /* precise hairline underline — scales in from the left, 140ms */
        .nav .links > a::after { content: ""; position: absolute; left: 0; right: 0; bottom: -7px; height: 1px; background: var(--accent); transform: scaleX(0); transform-origin: left center; transition: transform .14s ease; }
        .nav .links > a:hover::after, .nav .links > a:focus-visible::after { transform: scaleX(1); }
        @media (prefers-reduced-motion: reduce) { .nav .links > a::after { transition: none; } }
        /* right side is a bordered console cluster */
        .nav .console { margin-left: auto; display: flex; align-items: stretch; border: 1px solid var(--line2); border-radius: 12px; background: color-mix(in srgb, var(--card) 70%, transparent); overflow: hidden; }
        .nav .console > * { display: flex; align-items: center; padding: 0 16px; font-size: 13.5px; color: var(--muted); border-left: 1px solid var(--line); }
        .nav .console > *:first-child { border-left: none; }
        .nav .console .menu summary { font-family: var(--mono); font-size: 11.5px; letter-spacing: .04em; }
        .nav .console a:hover, .nav .console .menu summary:hover { color: var(--ink); }
        .nav .console .acct { font-family: var(--mono); font-size: 11.5px; letter-spacing: .04em; text-transform: uppercase; }
        .nav .console .bag { background: var(--ink); color: var(--bg); font-family: var(--mono); font-size: 11.5px; letter-spacing: .06em; text-transform: uppercase; gap: 8px; }
        .nav .console .bag:hover { color: var(--bg); background: var(--accent); }
        .bag .n { background: var(--accent); color: #fff; min-width: 19px; height: 19px; padding: 0 5px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-grid; place-items: center; }
        .nav .console .bag:hover .n { background: var(--ink); }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; z-index: 80; color: var(--ink); }

        /* dropdown (lang / currency / nav groups) */
        .menu { position: relative; }
        .menu summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: 14.5px; user-select: none; }
        .menu summary::-webkit-details-marker, .menu summary::marker { display: none; content: none; }
        .menu summary:hover { color: var(--ink); }
        .menu .chev { width: 11px; height: 11px; fill: none; stroke: currentColor; stroke-width: 1.6; transition: transform .2s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items { position: absolute; top: calc(100% + 14px); right: 0; min-width: 200px; background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 8px; z-index: 70; box-shadow: 0 24px 50px -28px rgba(20, 22, 28, .3); }
        /* console dropdowns: anchor to the console cluster so they hang
           neatly off its bottom edge instead of each padded cell */
        .nav .console { position: relative; }
        .nav .console .menu { position: static; }
        .nav .console .menu .menu-items { top: calc(100% + 10px); }
        .menu-items a { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border-radius: 8px; font-size: 14px; color: var(--ink); }
        .menu-items a:hover { background: var(--soft); }
        .menu-items a.active { color: var(--accent); font-weight: 600; }
        .menu-items .check { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .menu-items a:not(.active) .check { visibility: hidden; }
        .menu.nav-menu .menu-items { left: 0; right: auto; min-width: 220px; }
        .menu.nav-menu .menu-items a[data-depth]:not([data-depth="0"]) { padding-left: calc(12px + 14px * var(--d, 0)); color: var(--muted); }
        .menu.nav-menu .menu-items a.view-all { color: var(--accent); font-weight: 600; }

        /* mobile drawer */
        .m-drawer { position: fixed; inset: 0; z-index: 70; background: var(--ink); color: var(--bg); display: flex; flex-direction: column; justify-content: center; padding: 0 40px; opacity: 0; visibility: hidden; transition: opacity .4s ease, visibility .4s; }
        .m-drawer.open { opacity: 1; visibility: visible; }
        .m-drawer .mclose { position: absolute; top: 20px; right: 30px; background: none; border: none; font-size: 26px; color: var(--bg); }
        .m-drawer .mlogo { position: absolute; top: 22px; left: 40px; font-family: var(--display); font-weight: 800; font-size: 20px; letter-spacing: -.03em; }
        .m-drawer nav { display: flex; flex-direction: column; gap: 2px; }
        .m-drawer nav a { font-family: var(--display); font-weight: 800; font-size: 38px; letter-spacing: -.03em; padding: 8px 0; }
        .m-drawer nav a em { font-style: normal; color: var(--accent); }
        .m-drawer .mfoot { position: absolute; bottom: 30px; left: 40px; font-family: var(--mono); font-size: 11px; color: rgba(255, 255, 255, .5); }

        /* section + page heads (shared by inner pages) */
        .sec-head { text-align: center; margin: 90px 0 36px; }
        .sec-head .kicker { display: block; margin-bottom: 10px; }
        .sec-head h2 { font-family: var(--display); font-weight: 800; font-size: clamp(28px, 3.6vw, 46px); letter-spacing: -.02em; }
        .sec-head h2 em { font-style: normal; color: var(--accent); }
        .sec-head .more { display: inline-block; margin-top: 14px; font-family: var(--mono); font-size: 12px; color: var(--accent); }
        .page-head { padding: 46px 0 26px; border-bottom: 1px solid var(--line); }
        .page-head .crumb { font-family: var(--mono); font-size: 12px; color: var(--muted); text-transform: none; letter-spacing: 0; }
        .page-head .crumb a:hover { color: var(--accent); }
        .page-head h1 { font-family: var(--display); font-weight: 800; font-size: clamp(38px, 4.6vw, 58px); letter-spacing: -.02em; margin-top: 8px; }
        .page-head h1 em { font-style: normal; color: var(--accent); }
        .page-head p { color: var(--muted); max-width: 48ch; margin: 8px 0 0; }

        /* product card — Forma accessory card (used on home, catalog, collection, related) */
        .blooms { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; padding-top: 14px; }
        .bcard { display: flex; flex-direction: column; color: inherit; cursor: pointer; position: relative; transition: transform .14s ease; }
        .bcard:hover { transform: translateY(-4px); }
        .bcard .pic { height: 230px; border-radius: 16px; margin-bottom: 14px; position: relative; overflow: hidden; background: var(--soft); }
        /* hover: a faint blueprint grid surveys the tile — the object becomes
           a drawing being measured. Crisp 140ms in/out. */
        .bcard .pic::after { content: ""; position: absolute; inset: 0; z-index: 1; pointer-events: none; opacity: 0; transition: opacity .14s ease;
            background-image:
                linear-gradient(color-mix(in srgb, var(--accent) 16%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in srgb, var(--accent) 16%, transparent) 1px, transparent 1px);
            background-size: 26px 26px; }
        .bcard:hover .pic::after { opacity: 1; }
        .bcard .badge { position: absolute; top: 12px; left: 12px; background: var(--card); border: 1px solid var(--line); font-family: var(--mono); font-size: 11px; padding: 4px 10px; border-radius: 99px; color: var(--accent); z-index: 2; }
        .bcard .cat { font-family: var(--mono); font-size: 11px; color: var(--muted); }
        .bcard h3 { font-family: var(--display); font-weight: 600; font-size: 16px; margin: 2px 0 4px; }
        .bcard .pr { font-family: var(--display); font-weight: 600; font-size: 16px; font-variant-numeric: tabular-nums; color: var(--ink); }
        .bcard .add { transition: border-color .12s ease, color .12s ease, background-color .12s ease; }
        .bcard:hover .add { border-color: var(--accent) !important; color: var(--accent) !important; }
        @media (prefers-reduced-motion: reduce) { .bcard, .bcard:hover { transform: none; } .bcard .pic::after { transition: none; } }

        /* ===== footer — spec-sheet colophon =====
           Opens with a full-width mono spec band (a tech-brand "colophon"
           line), then the link columns, then the legal row. */
        footer.site { background: var(--bg); border-top: 1px solid var(--line); padding: 0 0 34px; margin-top: 60px; }
        .fspec { display: grid; grid-template-columns: repeat(4, 1fr); border-bottom: 1px solid var(--line); position: relative; }
        .fspec > .xmark { top: -8px; }
        .fspec > .xmark.xl { left: -7px; }
        .fspec > .xmark.xr { right: -7px; }
        .fspec .fs { padding: 26px 0; border-right: 1px solid var(--line); padding-right: 22px; }
        .fspec .fs:last-child { border-right: none; }
        .fspec .fs .l { font-family: var(--mono); font-size: 10px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); }
        .fspec .fs .v { font-family: var(--display); font-weight: 700; font-size: 16px; margin-top: 8px; letter-spacing: -.01em; }
        .fspec .fs .v em { font-style: normal; color: var(--accent); }
        .fgrid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; padding-top: 56px; }
        .fgrid .logo { font-size: 22px; }
        .fcol h4 { font-family: var(--mono); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 16px; }
        .fcol a { display: block; font-size: 14px; margin-bottom: 10px; color: #55585f; }
        .fcol a:hover { color: var(--accent); }
        .fbot { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 22px; border-top: 1px solid var(--line); font-family: var(--mono); font-size: 11px; letter-spacing: .04em; color: var(--muted); gap: 16px; flex-wrap: wrap; }
        .fbot a { color: var(--accent); }

        /* toast */
        .toast { position: fixed; top: calc(var(--header-height) + 22px); right: 24px; background: var(--card); color: var(--ink); padding: 14px 20px; z-index: 100; font-size: 14px; border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 24px 50px -28px rgba(20, 22, 28, .3); display: flex; align-items: center; gap: 10px; animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards; }
        .toast::before { content: "◆"; color: var(--accent); font-size: 11px; }
        @keyframes toastIn { from { transform: translateY(-8px); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-8px); } }

        @media (max-width: 1000px) {
            .blooms { grid-template-columns: repeat(2, 1fr); }
            .fspec { grid-template-columns: 1fr 1fr; }
            .fspec .fs:nth-child(2) { border-right: none; }
            .fspec .fs:nth-child(-n+2) { border-bottom: 1px solid var(--line); }
        }
        @media (max-width: 680px) {
            .wrap { padding: 0 20px; }
            .nav .links { display: none; }
            .menu-toggle { display: block; }
            .blooms { grid-template-columns: 1fr 1fr; gap: 14px; }
            .fgrid { grid-template-columns: 1fr 1fr; }
            .metabar { display: none; }
            .nav .console .acct .lbl { display: none; }
            .nav .console > * { padding: 0 12px; }
            .fspec { grid-template-columns: 1fr; }
            .fspec .fs { border-right: none; border-bottom: 1px solid var(--line); }
            .fspec .fs:last-child { border-bottom: none; }
        }
    </style>
</head>
<body>
    @php
        $csAnnouncement = $store->announcementBar();
        $csNavMenu = $store->navMenuItems();
        $customer = auth('customer')->user();
        $currentLocale = app()->getLocale();
        $languages = \App\Http\Middleware\SetLocale::available();
        $supportedCurrencies = $store->supportedDisplayCurrencies();
        $cartCount = \App\Services\Cart::forCurrent()->itemCount();
        $logoUrl = $store->logo_path
            ? \Illuminate\Support\Facades\Storage::url($store->logo_path)
            : null;
    @endphp

    @if ($csAnnouncement['enabled'] && $csAnnouncement['text'] !== '')
        @php
            $tape = trim($csAnnouncement['text']);
            $tapeUnit = e($tape) . ' &nbsp;<span class="s">◆</span>&nbsp; ';
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
        {{-- Tier 1: mono instrument bar — fixed spec coordinates, the
             tech-brand readout the merchant doesn't control. --}}
        <div class="metabar">
            <div class="wrap">
                <div class="mb-l">
                    <span class="ac">● {{ __('site.storefront.value_props.shipping_title') }}</span>
                    <span>{{ __('site.storefront.value_props.returns_title') }}</span>
                    <span>{{ __('site.storefront.value_props.checkout_title') }}</span>
                </div>
                <div class="mb-r">
                    <span>{{ $tenant->name }} / {{ date('Y') }}</span>
                    <span class="ac">{{ __('site.storefront.shop_all.eyebrow') }}</span>
                </div>
            </div>
        </div>

        {{-- Tier 2: wordmark + catalogue links + utility console. --}}
        <div class="wrap">
            <div class="nav">
                <button class="menu-toggle" aria-label="Menu">☰</button>
                <a class="logo" href="/">
                    @if ($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $tenant->name }}">@else{{ $tenant->name }}@endif
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
                        <a href="/">{{ __('site.storefront.nav.shop') }}</a>
                        <a href="/#featured">{{ __('site.storefront.nav.featured') }}</a>
                    @endif
                </div>
                <div class="console">
                    <details class="menu">
                        <summary aria-label="{{ __('site.lang.switch') }}"><span>{{ strtoupper($currentLocale) }}</span><svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                        <div class="menu-items" role="menu">
                            @foreach ($languages as $code => $name)
                                <a role="menuitem" href="/lang/{{ $code }}" class="@if($currentLocale===$code) active @endif"><span>{{ $name }}</span><svg class="check" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10l4 4 8-8"/></svg></a>
                            @endforeach
                        </div>
                    </details>
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
                        <a class="acct" href="{{ $customer ? '/account' : '/account/login' }}"><span class="lbl">{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</span></a>
                    @endif
                    <a class="bag" href="/cart">{{ __('site.common.cart') }}<span class="n">{{ $cartCount }}</span></a>
                </div>
            </div>
        </div>
    </header>

    <div class="m-drawer" id="mDrawer">
        <div class="mlogo">{{ $tenant->name }}</div>
        <button class="mclose" id="mClose" aria-label="Close menu">✕</button>
        <nav>
            @if (! empty($csNavMenu))
                @foreach ($csNavMenu as $item)
                    <a href="{{ $item['url'] ?: '/' }}">{{ $item['label'] }}</a>
                @endforeach
            @else
                <a href="/">{{ __('site.storefront.nav.shop') }}</a>
                <a href="/#featured">{{ __('site.storefront.nav.featured') }}</a>
            @endif
            @if ($store->showsAccountUi())
                <a href="{{ $customer ? '/account' : '/account/login' }}">{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</a>
            @endif
            <a href="/cart">{{ __('site.common.cart') }}</a>
        </nav>
        <div class="mfoot">{{ __('site.storefront.footer.tagline') }}</div>
    </div>

    @if (session('cart.flash'))
        <div class="toast">{{ session('cart.flash') }}</div>
    @endif

    @yield('content')

    <footer class="site">
        <div class="wrap">
            {{-- Spec-sheet colophon band — mono labels, display values. --}}
            <div class="fspec">
                <i class="xmark xl" aria-hidden="true"></i>
                <i class="xmark xr" aria-hidden="true"></i>
                <div class="fs"><div class="l">{{ __('site.storefront.value_props.shipping_title') }}</div><div class="v">{{ __('site.storefront.value_props.shipping_sub') }}</div></div>
                <div class="fs"><div class="l">{{ __('site.storefront.value_props.returns_title') }}</div><div class="v">{{ __('site.storefront.value_props.returns_sub') }}</div></div>
                <div class="fs"><div class="l">{{ __('site.storefront.value_props.checkout_title') }}</div><div class="v">{{ __('site.storefront.value_props.checkout_sub') }}</div></div>
                <div class="fs"><div class="l">{{ __('site.storefront.shop_all.eyebrow') }}</div><div class="v"><em>{{ $tenant->name }}</em></div></div>
            </div>
            <div class="fgrid">
                <div>
                    <div class="logo">{{ $tenant->name }}</div>
                    <p style="color: var(--muted); max-width: 30ch; margin-top: 14px; font-size: 14px;">{{ __('site.storefront.footer.tagline') }}</p>
                </div>
                <div class="fcol">
                    <h4>{{ __('site.storefront.footer.col_shop') }}</h4>
                    <a href="/">{{ __('site.storefront.footer.all_products') }}</a>
                    <a href="/#featured">{{ __('site.storefront.nav.featured') }}</a>
                    <a href="/cart">{{ __('site.common.cart') }}</a>
                </div>
                <div class="fcol">
                    <h4>{{ __('site.storefront.footer.col_help') }}</h4>
                    <a href="#">{{ __('site.storefront.footer.shipping') }}</a>
                    <a href="#">{{ __('site.storefront.footer.returns') }}</a>
                    <a href="#">{{ __('site.storefront.footer.contact') }}</a>
                </div>
                <div class="fcol">
                    <h4>{{ __('site.lang.switch') }}</h4>
                    @foreach ($languages as $code => $name)
                        <a href="/lang/{{ $code }}">{{ $name }}</a>
                    @endforeach
                </div>
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
            }, { threshold: 0.08, rootMargin: '0px 0px -5% 0px' });
            document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
        })();
    </script>
    @stack('scripts')
</body>
</html>
