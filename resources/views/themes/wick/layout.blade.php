<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>
    @include('storefront.partials.mode-boot')

    {{-- Wick hard-codes its typography: Fraunces (soft, wonky old-style serif —
         the display AND accent voice of the apothecary) + Space Mono (batch
         labels) + Hanken Grotesk (body). The merchant's font_family setting is
         intentionally ignored, like the other curated themes. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..900;1,9..144,300..900&family=Space+Mono:wght@400;700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* The one merchant-controllable knob: brand accent maps to
               primary_color. Default is the Wick amber/brass. */
            --accent: {{ $store->primary_color ?: '#d99a4e' }};
            --accent-bright: color-mix(in srgb, var(--accent) 72%, #ffe9c4);
            --display: "Fraunces", serif;
            --serif: "Fraunces", serif;
            --body: "Hanken Grotesk", system-ui, sans-serif;
            --mono: "Space Mono", monospace;

            /* Wick core palette — warm candlelit near-black, umber/espresso. */
            --bg: #17120e;
            --surface: #221a13;
            --surface2: #2c2218;
            --line: #3a2d1f;
            --line2: #4e3d2a;
            --txt: #f2e8d8;
            --muted: #b3a48e;
            --faint: #8a795f;
            --label: #f0e7d4;
            --deep: #0f0a07;

            /* Dark aliases for the cloned light-token names so most existing
               var() references flip to the dark theme automatically. */
            --ink: var(--txt);
            --card: var(--surface);
            --soft: var(--surface2);
            --soft2: var(--line);
            /* Card-art fills: amber-glass jar gradients, lit from within. */
            --jar: linear-gradient(168deg, #5a4023 0, #33251a 52%, #1a120c 100%);
            --jar2: radial-gradient(120% 100% at 50% 108%, #45311d, #1a120c);

            --header-height: 76px;

            /* Legacy aliases so the shared partials (catalog-controls,
               variant-picker, collection-strips, pagination, stripe-payment…)
               render in the Wick palette. */
            --primary: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 16%, var(--surface));
            --primary-strong: color-mix(in srgb, var(--accent) 82%, #000);
            --secondary: var(--txt);
            --border: var(--line);
            --text: var(--txt);
            --text-muted: var(--muted);
            --text-soft: var(--faint);

            /* Variant picker: pill chip, accent outline when selected. */
            --vp-radius: 99px;
            --vp-fill: var(--accent);
            --vp-on-accent: var(--bg);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        body {
            /* Late night at the workbench: the canvas is never flat — layered
               candlelit umber glows breathe under the content. */
            background:
                radial-gradient(1100px 720px at 80% -12%, #2e2213 0%, rgba(46, 34, 19, 0) 62%),
                radial-gradient(900px 640px at -14% 28%, #261b10 0%, rgba(38, 27, 16, 0) 58%),
                radial-gradient(1300px 900px at 50% 118%, #241809 0%, rgba(36, 24, 9, 0) 62%),
                var(--bg);
            color: var(--txt); font-family: var(--body); line-height: 1.6; font-size: 16px; min-height: 100vh; overflow-x: hidden;
        }
        /* faint film grain over everything — data-URI noise, alpha capped in-SVG */
        body::before {
            content: ""; position: fixed; inset: 0; z-index: 2147483000; pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='2' stitchTiles='stitch'/%3E%3CfeColorMatrix type='saturate' values='0'/%3E%3CfeComponentTransfer%3E%3CfeFuncA type='table' tableValues='0 0.055'/%3E%3C/feComponentTransfer%3E%3C/filter%3E%3Crect width='180' height='180' filter='url(%23n)'/%3E%3C/svg%3E");
        }
        /* deep vignette — corners fall into the dark, center stays candlelit */
        body::after {
            content: ""; position: fixed; inset: 0; z-index: 3; pointer-events: none;
            background: radial-gradient(135% 105% at 50% 38%, rgba(9, 6, 3, 0) 58%, rgba(9, 6, 3, .38) 100%);
        }
        ::selection { background: var(--accent); color: var(--deep); }

        /* ===== Daylight bench — the visitor-toggle light mode. Token map
           lives in manifest.php ('modes'); these rules retune the dark-baked
           atmosphere layers that tokens alone can't flip. ===== */
        html[data-mode="light"] body {
            background:
                radial-gradient(1100px 720px at 80% -12%, #fff8ea 0%, rgba(255, 248, 234, 0) 62%),
                radial-gradient(1300px 900px at 50% 118%, #efe0c8 0%, rgba(239, 224, 200, 0) 62%),
                var(--bg);
        }
        html[data-mode="light"] body::after { background: radial-gradient(135% 105% at 50% 38%, rgba(60, 42, 20, 0) 66%, rgba(60, 42, 20, .12) 100%); }
        html[data-mode="light"] header.site { background: rgba(250, 243, 231, .86); }
        html[data-mode="light"] .hero .stage .glow { opacity: .55; }
        html[data-mode="light"] .hero::before { -webkit-text-stroke-color: rgba(155, 108, 42, .18); }
        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .wrap { max-width: 1240px; margin: 0 auto; padding: 0 38px; }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
        input:focus, select:focus, textarea:focus { box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 16%, transparent); }

        /* melted-wax ring — the signature decorative accent. A soft ring of
           cooled wax left on the workbench, faintly lit; pure CSS, reusable. */
        .halo { position: absolute; border-radius: 50%; pointer-events: none; border: 2px solid rgba(217, 154, 78, .22); box-shadow: inset 0 0 22px rgba(217, 154, 78, .16), 0 0 30px rgba(217, 154, 78, .08); }
        .halo::before { content: ""; position: absolute; inset: 5px; border-radius: 50%; border: 1px solid rgba(217, 154, 78, .12); }

        /* placeholder fills (used wherever a real image is missing) — amber
           glass gradients on a dark surface, lit like a candle from within. */
        .ph { position: relative; background: var(--jar2); overflow: hidden; border: 1px solid var(--line); }
        .bloomph { background: var(--jar); }
        .jar { background: var(--jar); }
        .ph img, .bloomph img, .bcard .pic img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .kicker, .mono { font-family: var(--mono); font-size: 12px; letter-spacing: .06em; color: var(--accent); text-transform: uppercase; }
        /* batch-slash prefix — the "// BATCH 24" mono voice from the jar labels */
        .kicker::before { content: "// "; color: var(--faint); }

        /* buttons — pill, accent fill / ghost outline (dark text on accent).
           Hover = foil: a warm sheen sweeps across while the brass glows. */
        .btn { position: relative; overflow: hidden; display: inline-flex; align-items: center; justify-content: center; gap: 9px; font-size: 14px; font-weight: 600; padding: 15px 30px; border: 1px solid var(--accent); background: var(--accent); color: var(--bg); border-radius: 99px; transition: filter .25s ease, transform .25s ease, background-color .25s ease, color .25s ease, box-shadow .3s ease; }
        .btn::after { content: ""; position: absolute; inset: 0; border-radius: inherit; background: linear-gradient(115deg, transparent 32%, rgba(255, 241, 222, .38) 48%, transparent 64%); transform: translateX(-130%); transition: transform .6s ease; pointer-events: none; }
        .btn:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 10px 30px -8px color-mix(in srgb, var(--accent) 55%, transparent), 0 0 0 1px color-mix(in srgb, var(--accent) 45%, transparent); }
        .btn:hover::after { transform: translateX(130%); }
        .btn.outline { background: transparent; color: var(--txt); border-color: var(--line2); }
        .btn.outline::after { display: none; }
        .btn.outline:hover { background: transparent; color: var(--txt); border-color: var(--txt); filter: none; box-shadow: none; }
        .btn.block { width: 100%; }
        .btn:disabled { opacity: .55; cursor: not-allowed; transform: none; filter: none; box-shadow: none; }
        @media (prefers-reduced-motion: reduce) { .btn:hover { transform: none; } .btn::after { display: none; } }

        /* pill label — Space Mono micro-tag (used by partials + cards) */
        .pill { display: inline-block; font-family: var(--mono); font-size: 10.5px; letter-spacing: .04em; padding: 4px 10px; border: 1px solid var(--line2); border-radius: 99px; color: var(--muted); text-transform: uppercase; }

        /* `.tape` kept as a no-op anchor so any legacy decorative strip markup
           stays invisible — Wick has no such accent. */
        .tape { display: none; }

        /* reveal on scroll — lines rise out of the dark */
        .reveal { opacity: 0; transform: translateY(34px); transition: opacity .9s ease, transform 1.05s cubic-bezier(.19, .7, .16, 1); }
        .reveal.in { opacity: 1; transform: none; }
        .reveal.s1 { transition-delay: .12s; } .reveal.s2 { transition-delay: .24s; } .reveal.s3 { transition-delay: .36s; } .reveal.s4 { transition-delay: .48s; }
        @media (prefers-reduced-motion: reduce) { .reveal { opacity: 1 !important; transform: none !important; transition: none !important; } .floaty, .tick .track { animation: none !important; } }

        /* ticker — the announcement bar, accent strip with mono caps */
        .tick { background: var(--accent); color: var(--bg); overflow: hidden; white-space: nowrap; font-weight: 700; }
        .tick .track { display: inline-flex; gap: 30px; padding: 8px 0; animation: tick var(--tick-dur, 28s) linear infinite; font-family: var(--mono); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; will-change: transform; }
        .tick .track .s { color: var(--deep); }
        .tick.link a { color: inherit; }
        @keyframes tick { to { transform: translateX(-50%); } }
        .tick[data-static="1"] .track { animation: none; }
        @media (prefers-reduced-motion: reduce) { .tick .track { animation-play-state: paused; } }

        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }

        /* header / nav */
        header.site { position: sticky; top: 0; z-index: 60; background: rgba(23, 18, 14, .84); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); }
        .nav { display: flex; align-items: center; gap: 32px; height: 70px; }
        .logo { font-family: var(--display); font-weight: 800; font-size: 24px; letter-spacing: -.02em; color: var(--txt); white-space: nowrap; }
        .logo b { color: var(--accent); }
        .logo img { height: 32px; width: auto; display: block; }
        .nav .links { display: flex; gap: 24px; font-size: 14px; align-items: center; color: var(--muted); }
        .nav .links a:hover { color: var(--txt); }
        .nav .right { margin-left: auto; display: flex; gap: 18px; align-items: center; font-size: 14px; color: var(--muted); }
        .nav .right a:hover { color: var(--txt); }
        .bag { color: var(--txt); }
        .bag .n { background: var(--accent); color: var(--bg); min-width: 19px; height: 19px; padding: 0 5px; border-radius: 99px; font-size: 11px; font-weight: 700; display: inline-grid; place-items: center; margin-left: 5px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; z-index: 80; color: var(--txt); }

        /* dropdown (lang / currency / nav groups) */
        .menu { position: relative; }
        .menu summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: 14px; user-select: none; }
        .menu summary::-webkit-details-marker, .menu summary::marker { display: none; content: none; }
        .menu summary:hover { color: var(--txt); }
        .menu .chev { width: 11px; height: 11px; fill: none; stroke: currentColor; stroke-width: 1.6; transition: transform .2s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items { position: absolute; top: calc(100% + 12px); right: 0; min-width: 200px; background: var(--surface); border: 1px solid var(--line2); border-radius: 12px; padding: 8px; z-index: 70; box-shadow: 0 24px 50px -22px rgba(0, 0, 0, .7); animation: menuIn .22s cubic-bezier(.19, .7, .16, 1); }
        @keyframes menuIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .menu-items { animation: none; } }
        .menu-items a { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border-radius: 9px; font-size: 14px; color: var(--txt); }
        .menu-items a:hover { background: var(--surface2); }
        .menu-items a.active { color: var(--accent); font-weight: 600; }
        .menu-items .check { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .menu-items a:not(.active) .check { visibility: hidden; }
        .menu.nav-menu .menu-items { left: 0; right: auto; min-width: 220px; }
        .menu.nav-menu .menu-items a[data-depth]:not([data-depth="0"]) { padding-left: calc(12px + 14px * var(--d, 0)); color: var(--muted); }
        .menu.nav-menu .menu-items a.view-all { color: var(--accent); font-weight: 600; }

        /* mobile drawer */
        .m-drawer { position: fixed; inset: 0; z-index: 70; background: var(--deep); display: flex; flex-direction: column; justify-content: center; padding: 0 38px; opacity: 0; visibility: hidden; transition: opacity .45s ease, visibility .45s; }
        .m-drawer.open { opacity: 1; visibility: visible; }
        .m-drawer .mclose { position: absolute; top: 20px; right: 30px; background: none; border: none; font-size: 26px; color: var(--txt); }
        .m-drawer .mlogo { position: absolute; top: 22px; left: 38px; font-family: var(--display); font-weight: 800; font-size: 20px; letter-spacing: -.02em; }
        .m-drawer nav { display: flex; flex-direction: column; gap: 2px; }
        .m-drawer nav a { font-family: var(--display); font-weight: 700; font-size: 38px; letter-spacing: -.02em; padding: 8px 0; display: flex; align-items: baseline; opacity: 0; transform: translateY(18px); transition: opacity .5s ease, transform .55s cubic-bezier(.19, .7, .16, 1); }
        .m-drawer.open nav a { opacity: 1; transform: none; }
        .m-drawer.open nav a:nth-child(1) { transition-delay: .06s; } .m-drawer.open nav a:nth-child(2) { transition-delay: .12s; }
        .m-drawer.open nav a:nth-child(3) { transition-delay: .18s; } .m-drawer.open nav a:nth-child(4) { transition-delay: .24s; }
        .m-drawer.open nav a:nth-child(5) { transition-delay: .30s; } .m-drawer.open nav a:nth-child(6) { transition-delay: .36s; }
        .m-drawer nav a em { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .m-drawer nav a .ix { font-family: var(--mono); font-size: 12px; font-weight: 400; color: var(--accent); margin-right: 14px; }
        .m-drawer .mlogo b { color: var(--accent); }
        .m-drawer .mfoot { position: absolute; bottom: 30px; left: 38px; font-family: var(--mono); font-size: 11px; letter-spacing: .04em; text-transform: uppercase; color: var(--faint); opacity: 0; transition: opacity .5s ease .4s; }
        .m-drawer.open .mfoot { opacity: 1; }
        @media (prefers-reduced-motion: reduce) { .m-drawer nav a, .m-drawer .mfoot { opacity: 1 !important; transform: none !important; transition: none !important; } }

        /* section + page heads (shared by inner pages) */
        .sec-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; border-bottom: 1px solid var(--line); padding-bottom: 14px; margin: 80px 0 30px; }
        .sec-head .kicker { display: block; }
        .sec-head h2 { font-family: var(--display); font-weight: 800; letter-spacing: -.02em; font-size: clamp(28px, 4vw, 50px); }
        .sec-head h2 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--accent); }
        .sec-head .more { font-family: var(--mono); font-size: 12px; color: var(--accent); text-transform: uppercase; letter-spacing: .04em; }
        .page-head { padding: 46px 0 26px; border-bottom: 1px solid var(--line); }
        .page-head .crumb { font-family: var(--mono); font-size: 12px; letter-spacing: .02em; color: var(--faint); }
        .page-head .crumb a:hover { color: var(--accent); }
        .page-head h1 { font-family: var(--display); font-weight: 800; letter-spacing: -.02em; font-size: clamp(38px, 4.6vw, 58px); margin-top: 8px; }
        .page-head h1 em { font-family: var(--serif); font-style: italic; font-weight: 400; color: var(--accent); }
        .page-head p { color: var(--muted); max-width: 48ch; margin: 8px 0 0; }

        /* product card — jar label (used on home, catalog, collection, related).
           Signature: every grid stamps its jars with a batch index (BATCH 01,
           BATCH 02…) via CSS counters — no markup, pure pour-ledger flavour. */
        .blooms { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px 20px; padding-top: 14px; counter-reset: batch; }
        .bcard { display: block; color: inherit; cursor: pointer; position: relative; transition: transform .25s cubic-bezier(.19, .7, .16, 1); }
        .blooms .bcard { counter-increment: batch; }
        .bcard:hover { transform: translateY(-6px); }
        .bcard .pic { height: 320px; border: 1px solid var(--line); border-radius: 8px; margin-bottom: 14px; position: relative; overflow: hidden; display: grid; place-items: center; transition: border-color .3s ease, box-shadow .3s ease; }
        /* candlelight falloff inside the frame */
        .bcard .pic::before { content: ""; position: absolute; inset: 0; z-index: 1; pointer-events: none; background: radial-gradient(120% 80% at 50% -5%, rgba(240, 231, 212, .05), transparent 45%), radial-gradient(150% 90% at 50% 115%, rgba(9, 6, 3, .5), transparent 55%); }
        .blooms .bcard .pic::after { content: var(--batch-label, "BATCH ") counter(batch, decimal-leading-zero); position: absolute; bottom: 10px; right: 12px; z-index: 2; font-family: var(--mono); font-size: 10px; letter-spacing: .12em; color: var(--faint); }
        .blooms.no-batch .bcard .pic::after { display: none; }
        .bcard:hover .pic { border-color: var(--line2); box-shadow: 0 26px 48px -30px rgba(0, 0, 0, .85), 0 0 0 1px color-mix(in srgb, var(--accent) 22%, transparent); }
        .bcard .badge { position: absolute; top: 12px; left: 12px; font-family: var(--mono); font-size: 10px; letter-spacing: .04em; padding: 4px 10px; border: 1px solid var(--line2); background: rgba(15, 10, 7, .6); border-radius: 99px; color: var(--accent); z-index: 2; text-transform: uppercase; }
        .bcard .pic .jar-mark { width: 120px; height: 170px; border-radius: 16px 16px 12px 12px; background: var(--jar); border: 1px solid var(--line2); transition: transform .4s cubic-bezier(.19, .7, .16, 1); position: relative; box-shadow: inset 0 -34px 40px -22px rgba(217, 154, 78, .28); }
        /* the hero's cream jar label, miniaturised — brass lid + paper with ruled ink lines */
        .bcard .pic .jar-mark::before { content: ""; position: absolute; top: -9px; left: 50%; transform: translateX(-50%); width: 108px; height: 16px; background: linear-gradient(180deg, #6b5232, #45331d); border-radius: 8px; }
        .bcard .pic .jar-mark::after { content: ""; position: absolute; left: 50%; top: 54%; transform: translate(-50%, -50%); width: 74px; height: 62px; background-color: var(--label); border-radius: 2px; box-shadow: 0 5px 12px -5px rgba(0, 0, 0, .55); background-image: linear-gradient(#8a5f36 0 0), linear-gradient(#d9c9ae 0 0), linear-gradient(#8a5f36 0 0); background-repeat: no-repeat; background-size: 38px 3px, 50px 1px, 24px 2px; background-position: 50% 30%, 50% 52%, 50% 70%; }
        .bcard:hover .pic .jar-mark { transform: rotate(-3deg); }
        .bcard .cat { font-family: var(--mono); font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .02em; }
        .bcard h3 { font-family: var(--serif); font-weight: 500; font-size: 20px; margin: 3px 0 2px; line-height: 1.15; transition: color .25s ease; }
        .bcard:hover h3 { color: #fff8ee; }
        .bcard .foot { display: flex; align-items: center; justify-content: space-between; margin-top: 8px; }
        .bcard .pr { font-family: var(--display); font-weight: 800; font-size: 18px; font-variant-numeric: tabular-nums; color: var(--txt); transition: color .25s ease; }
        .bcard:hover .pr { color: var(--accent); }
        .bcard .add { width: 34px; height: 34px; border: 1px solid var(--line2); color: var(--txt); border-radius: 99px; font-size: 18px; display: grid; place-items: center; transition: border-color .25s ease, color .25s ease, box-shadow .25s ease, transform .25s ease; }
        .bcard:hover .add { border-color: var(--accent); color: var(--accent); box-shadow: 0 0 14px -2px color-mix(in srgb, var(--accent) 45%, transparent); transform: rotate(90deg); }
        @media (prefers-reduced-motion: reduce) { .bcard, .bcard:hover, .bcard .pic .jar-mark, .bcard:hover .pic .jar-mark, .bcard:hover .add { transform: none; transition: none; } }

        /* footer — settles into the darkest end of the workbench */
        footer.site { position: relative; overflow: hidden; border-top: 1px solid var(--line); padding: 60px 0 34px; margin-top: 30px; background: linear-gradient(180deg, rgba(15, 10, 7, 0), rgba(15, 10, 7, .55)); }
        footer.site .halo.f1 { width: 190px; height: 190px; right: -60px; bottom: -70px; }
        footer.site .halo.f2 { width: 90px; height: 90px; right: 90px; bottom: 40px; opacity: .6; }
        .fgrid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; }
        .fgrid .logo { font-family: var(--display); font-weight: 800; font-size: 22px; letter-spacing: -.02em; }
        .fcol h4 { font-family: var(--mono); font-size: 11px; letter-spacing: .02em; text-transform: uppercase; color: var(--faint); margin-bottom: 16px; }
        .fcol a { display: block; font-size: 14px; margin-bottom: 10px; color: var(--muted); }
        .fcol a:hover { color: var(--accent); }
        .fbot { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 22px; border-top: 1px solid var(--line); font-family: var(--mono); font-size: 12px; color: var(--faint); gap: 16px; flex-wrap: wrap; }
        .fbot a { color: var(--accent); }

        /* toast */
        .toast { position: fixed; top: calc(var(--header-height) + 22px); right: 24px; background: var(--surface); color: var(--txt); padding: 14px 20px; z-index: 100; font-size: 14px; border: 1px solid var(--line2); border-radius: 12px; box-shadow: 0 24px 50px -22px rgba(0, 0, 0, .7); display: flex; align-items: center; gap: 10px; animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards; }
        .toast::before { content: "◆"; color: var(--accent); }
        @keyframes toastIn { from { transform: translateY(-8px); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-8px); } }

        @media (max-width: 1000px) {
            .blooms { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 680px) {
            .wrap { padding: 0 20px; }
            .nav .links { display: none; }
            .menu-toggle { display: block; }
            .blooms { grid-template-columns: repeat(2, 1fr); }
            .bcard .pic { height: 260px; }
            .fgrid { grid-template-columns: 1fr 1fr; }
            .nav .right .lbl { display: none; }
        }
    </style>
    {!! $theme->headExtras() !!}
    {{-- Storefront kit — motion (GSAP/Lenis) + cart drawer / quick-view (Alpine). --}}
    @vite(['resources/css/storefront.css', 'resources/js/storefront.js'])
</head>
{{-- Reveals stay slow and candlelit; SCROLL stays responsive — the two are
     separate feels (sluggish scrolling reads as jank, not mood). --}}
<body data-gv-motion='{"duration":1.35,"ease":"power2.out","distance":34,"stagger":0.12,"scroll":{"lerp":0.18,"wheelMultiplier":1.6}}'>
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
        // Split-accent wordmark (mockup: Wi<b>ck</b>) — second half tinted
        // with the brand accent. Falls back to the flat name for short words.
        $brandName = $tenant->name;
        $brandSplit = mb_strlen($brandName) > 4
            ? '<span>' . e(mb_substr($brandName, 0, (int) ceil(mb_strlen($brandName) / 2))) . '</span><b>' . e(mb_substr($brandName, (int) ceil(mb_strlen($brandName) / 2))) . '</b>'
            : '<b>' . e($brandName) . '</b>';
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
        <div class="wrap">
            <div class="nav">
                <button class="menu-toggle" aria-label="Menu">☰</button>
                <a class="logo" href="/">
                    @if ($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $tenant->name }}">@else{!! $brandSplit !!}@endif
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
                <div class="right">
                    @include('storefront.partials.mode-toggle')
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
                        <a href="{{ $customer ? '/account' : '/account/login' }}"><span class="lbl">{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</span></a>
                    @endif
                    <a class="bag" href="/cart">{{ __('site.common.cart') }}<span class="n">{{ $cartCount }}</span></a>
                </div>
            </div>
        </div>
    </header>

    <div class="m-drawer" id="mDrawer">
        <div class="mlogo">{!! $brandSplit !!}</div>
        <button class="mclose" id="mClose" aria-label="Close menu">✕</button>
        <nav>
            @php $mIx = 0; @endphp
            @if (! empty($csNavMenu))
                @foreach ($csNavMenu as $item)
                    <a href="{{ $item['url'] ?: '/' }}"><span class="ix">{{ sprintf('%02d', ++$mIx) }}</span>{{ $item['label'] }}</a>
                @endforeach
            @else
                <a href="/"><span class="ix">{{ sprintf('%02d', ++$mIx) }}</span>{{ __('site.storefront.nav.shop') }}</a>
                <a href="/#featured"><span class="ix">{{ sprintf('%02d', ++$mIx) }}</span>{{ __('site.storefront.nav.featured') }}</a>
            @endif
            @if ($store->showsAccountUi())
                <a href="{{ $customer ? '/account' : '/account/login' }}"><span class="ix">{{ sprintf('%02d', ++$mIx) }}</span>{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</a>
            @endif
            <a href="/cart"><span class="ix">{{ sprintf('%02d', ++$mIx) }}</span>{{ __('site.common.cart') }}</a>
        </nav>
        <div class="mfoot">{{ __('site.storefront.footer.tagline') }}</div>
    </div>

    @if (session('cart.flash'))
        <div class="toast">{{ session('cart.flash') }}</div>
    @endif

    @yield('content')

    <footer class="site">
        <div class="halo f1" aria-hidden="true"></div>
        <div class="halo f2" aria-hidden="true"></div>
        <div class="wrap">
            <div class="fgrid">
                <div>
                    <div class="logo">{!! $brandSplit !!}</div>
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
                    @if ($store->showsAccountUi())
                        <h4>{{ __('site.common.my_account') }}</h4>
                        <a href="{{ $customer ? '/account' : '/account/login' }}">{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</a>
                    @endif
                    <h4 style="margin-top: {{ $store->showsAccountUi() ? '22px' : '0' }};">{{ __('site.lang.switch') }}</h4>
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
    @include('storefront.partials.cart-drawer')
    @include('storefront.partials.quick-view')

    @stack('scripts')
</body>
</html>
