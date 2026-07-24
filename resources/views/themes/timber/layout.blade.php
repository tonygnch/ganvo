<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>
    @include('storefront.partials.mode-boot')

    {{-- Timber hard-codes its typography: Barlow Condensed (tall signage caps —
         the lumber-yard stencil voice) + Barlow (body) + IBM Plex Mono (spec
         labels, dimensions, grading stamps). The merchant's font_family setting
         is intentionally ignored, like the other curated themes. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Barlow:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            /* The one merchant-controllable knob: brand accent maps to
               primary_color. Default is the Timber resin-amber. */
            --accent: {{ $store->primary_color ?: '#b57a34' }};
            --accent-deep: color-mix(in srgb, var(--accent) 78%, #3a2a14);
            --display: "Barlow Condensed", sans-serif;
            --serif: "Barlow Condensed", sans-serif;
            --body: "Barlow", system-ui, sans-serif;
            --mono: "IBM Plex Mono", monospace;

            /* Timber core palette — sanded-pine paper, walnut ink, daylight yard. */
            --bg: #f3efe6;
            --surface: #faf7f0;
            --surface2: #ebe4d5;
            --line: #d9cfba;
            --line2: #bfb094;
            --txt: #2a2118;
            --muted: #6d5f4b;
            --faint: #94866c;
            --plate: #f7f1e2;
            --deep: #241c12;

            /* Aliases for the cloned token names so shared var() references
               resolve in the Timber palette. */
            --ink: var(--txt);
            --card: var(--surface);
            --soft: var(--surface2);
            --soft2: var(--line);
            /* Card-art fills: sawn-board gradients, planed face + end grain. */
            --board: radial-gradient(120% 100% at 30% 0%, #e8ddc6, #cdbb96);
            /* text that sits ON the accent fill. --deep (near-black) clears AA
               (4.64:1) where white did not (3.62:1), and the manifest's dark
               mode deliberately leaves --deep alone, so it holds in both modes. */
            --on-accent: var(--deep);
            --header-height: 74px;

            /* Legacy aliases so the shared partials (catalog-controls,
               variant-picker, collection-strips, pagination, stripe-payment…)
               render in the Timber palette. */
            --primary: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 14%, var(--surface));
            --primary-strong: var(--accent-deep);
            --secondary: var(--txt);
            --border: var(--line);
            --text: var(--txt);
            --text-muted: var(--muted);
            --text-soft: var(--faint);

            /* Variant picker: square dimension chip, accent when selected. */
            --vp-radius: 5px;
            --vp-fill: var(--accent);
            --vp-on-accent: var(--on-accent);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        body {
            /* Planed-board striations: whisper-faint vertical grain over the
               paper tone — the yard in daylight, never a flat white. */
            background:
                repeating-linear-gradient(92deg, rgba(122, 96, 58, .028) 0 2px, transparent 2px 9px, rgba(122, 96, 58, .016) 9px 11px, transparent 11px 26px),
                linear-gradient(180deg, #f6f2ea 0%, var(--bg) 340px),
                var(--bg);
            color: var(--txt); font-family: var(--body); line-height: 1.6; font-size: 16px; min-height: 100vh; overflow-x: hidden;
        }
        ::selection { background: var(--accent); color: var(--on-accent); }

        /* ===== Workshop night — the visitor-toggle dark mode. Token map lives
           in manifest.php ('modes'); these retune what tokens can't flip. ===== */
        html[data-mode="dark"] body {
            background:
                repeating-linear-gradient(92deg, rgba(0, 0, 0, .10) 0 2px, transparent 2px 9px, rgba(0, 0, 0, .05) 9px 11px, transparent 11px 26px),
                linear-gradient(180deg, #251d12 0%, var(--bg) 340px),
                var(--bg);
        }
        html[data-mode="dark"] header.site { background: color-mix(in srgb, var(--bg) 88%, transparent); }
        html[data-mode="dark"] ::selection { color: var(--deep); }
        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .wrap { max-width: 1240px; margin: 0 auto; padding: 0 38px; }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
        input:focus, select:focus, textarea:focus { box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 16%, transparent); }

        /* end-grain rings — the signature decorative accent: concentric growth
           rings of a sawn log end, pure CSS, reusable anywhere. */
        .ring { position: absolute; border-radius: 50%; pointer-events: none; border: 2px solid color-mix(in srgb, var(--accent) 30%, transparent); }
        .ring::before { content: ""; position: absolute; inset: 14%; border-radius: 50%; border: 1.5px solid color-mix(in srgb, var(--accent) 22%, transparent); }
        .ring::after { content: ""; position: absolute; inset: 32%; border-radius: 50%; border: 1px solid color-mix(in srgb, var(--accent) 16%, transparent); }

        /* dimension ruler — mm ticks along a rule; drop on any edge. */
        .rule-ticks { height: 8px; background: repeating-linear-gradient(90deg, var(--line2) 0 1px, transparent 1px 10px), repeating-linear-gradient(90deg, var(--line2) 0 1px, transparent 1px 50px); background-size: 100% 5px, 100% 8px; background-position: 0 bottom, 0 bottom; background-repeat: no-repeat; opacity: .7; }

        /* placeholder fills (used wherever a real image is missing) — sawn
           boards seen face-on, warm planed pine on the light surface. */
        .ph { position: relative; background: var(--board); overflow: hidden; border: 1px solid var(--line); }
        .ph img, .bcard .pic img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* plank-mark — the imageless-product artwork: a stack of five sawn
           boards with a treatment-amber band and an end-grain ring. */
        .plank-mark { position: relative; width: 150px; height: 150px; }
        .plank-mark i { position: absolute; left: 0; right: 0; height: 24px; border-radius: 3px; border: 1px solid rgba(90, 68, 38, .35); background: linear-gradient(94deg, #d9c9a4 0%, #c3ad82 55%, #cfbb93 100%); box-shadow: inset 0 -6px 10px -6px rgba(90, 68, 38, .45), 0 3px 5px -3px rgba(60, 44, 22, .35); }
        .plank-mark i:nth-child(1) { top: 0; }
        .plank-mark i:nth-child(2) { top: 30px; left: 6px; right: -4px; }
        .plank-mark i:nth-child(3) { top: 60px; left: -3px; right: 5px; }
        .plank-mark i:nth-child(4) { top: 90px; left: 4px; right: -2px; }
        .plank-mark i:nth-child(5) { top: 120px; }
        /* the treated band — bottom two boards drink the amber */
        .plank-mark i:nth-child(4), .plank-mark i:nth-child(5) { background: linear-gradient(94deg, color-mix(in srgb, var(--accent) 42%, #d9c9a4), color-mix(in srgb, var(--accent) 58%, #b09a72)); }
        .plank-mark::after { content: ""; position: absolute; right: -14px; top: -14px; width: 46px; height: 46px; border-radius: 50%; background: radial-gradient(circle, #e5d7b8 18%, #cdb98e 40%, #b7a074 62%, #a08a60 82%); border: 2px solid rgba(90, 68, 38, .4); box-shadow: 0 4px 10px -4px rgba(60, 44, 22, .4); }
        /* small stack — for line-item thumbnails (cart, checkout summary,
           order confirmation) where the full 150px block will not fit. Scaled
           rather than re-sized so the board proportions stay identical. */
        .plank-mark.sm { transform: scale(.42); }
        .plank-mark.xs { transform: scale(.3); }

        .kicker, .mono { font-family: var(--mono); font-size: 12px; letter-spacing: .06em; color: var(--accent-deep); text-transform: uppercase; }
        /* dimension-prefix — the "— 45×95" spec voice from the price lists */
        .kicker::before { content: "— "; color: var(--faint); }

        /* buttons — square-shouldered, stencil caps. Hover: the board shifts
           on its stack (translate + hard shadow), no glow. */
        .btn { position: relative; display: inline-flex; align-items: center; justify-content: center; gap: 9px; font-family: var(--display); font-size: 16px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; padding: 13px 28px; border: 1px solid var(--accent-deep); background: var(--accent); color: var(--on-accent); border-radius: 6px; box-shadow: 0 2px 0 0 var(--accent-deep); transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, color .18s ease; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 0 0 var(--accent-deep), 0 12px 22px -12px color-mix(in srgb, var(--accent) 55%, transparent); }
        .btn:active { transform: translateY(0); box-shadow: 0 2px 0 0 var(--accent-deep); }
        .btn.outline { background: transparent; color: var(--txt); border-color: var(--line2); box-shadow: 0 2px 0 0 var(--line2); }
        .btn.outline:hover { border-color: var(--txt); color: var(--txt); box-shadow: 0 4px 0 0 var(--line2); }
        .btn.block { width: 100%; }
        .btn:disabled { opacity: .55; cursor: not-allowed; transform: none; box-shadow: 0 2px 0 0 var(--accent-deep); }
        @media (prefers-reduced-motion: reduce) { .btn:hover, .btn:active { transform: none; } }

        /* pill label — mono micro-tag (used by partials + cards); square chip */
        .pill { display: inline-block; font-family: var(--mono); font-size: 10.5px; letter-spacing: .04em; padding: 4px 10px; border: 1px solid var(--line2); border-radius: 5px; color: var(--muted); text-transform: uppercase; background: var(--surface); }

        /* grading stamp — bordered mono caps, inked slightly off-square */
        .stamp-tag { display: inline-block; font-family: var(--mono); font-weight: 600; font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--accent-deep); border: 2px solid var(--accent-deep); border-radius: 4px; padding: 4px 10px; transform: rotate(-2deg); opacity: .85; }

        /* `.tape` kept as a no-op anchor so any legacy decorative strip markup
           stays invisible — Timber has no such accent. */
        .tape { display: none; }

        /* reveal on scroll — boards settle onto the stack */
        .reveal { opacity: 0; transform: translateY(26px); transition: opacity .7s ease, transform .8s cubic-bezier(.19, .7, .16, 1); }
        .reveal.in { opacity: 1; transform: none; }
        .reveal.s1 { transition-delay: .1s; } .reveal.s2 { transition-delay: .2s; } .reveal.s3 { transition-delay: .3s; } .reveal.s4 { transition-delay: .4s; }
        @media (prefers-reduced-motion: reduce) { .reveal { opacity: 1 !important; transform: none !important; transition: none !important; } }

        /* ticker — the announcement bar, deep walnut strip with mono caps */
        .tick { background: var(--deep); color: #f0e7d6; overflow: hidden; white-space: nowrap; font-weight: 700; }
        .tick .track { display: inline-flex; gap: 30px; padding: 8px 0; animation: tick var(--tick-dur, 28s) linear infinite; font-family: var(--mono); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; will-change: transform; }
        .tick .track .s { color: var(--accent); }
        .tick.link a { color: inherit; }
        @keyframes tick { to { transform: translateX(-50%); } }
        .tick[data-static="1"] .track { animation: none; }
        @media (prefers-reduced-motion: reduce) { .tick .track { animation-play-state: paused; } }

        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }

        /* header / nav — daylight office over the yard */
        header.site { position: sticky; top: 0; z-index: 60; background: color-mix(in srgb, #faf7f0 88%, transparent); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); }
        header.site::after { content: ""; display: block; height: 3px; background: repeating-linear-gradient(90deg, var(--line2) 0 1px, transparent 1px 12px); opacity: .5; }
        .nav { display: flex; align-items: center; gap: 32px; height: 70px; }
        .logo { font-family: var(--display); font-weight: 700; font-size: 26px; letter-spacing: .02em; text-transform: uppercase; color: var(--txt); white-space: nowrap; }
        .logo b { color: var(--accent-deep); }
        .logo img { height: 32px; width: auto; display: block; }
        .nav .links { display: flex; gap: 24px; font-family: var(--display); font-size: 16px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; align-items: center; color: var(--muted); }
        .nav .links a:hover { color: var(--txt); }
        .nav .right { margin-left: auto; display: flex; gap: 18px; align-items: center; font-size: 14px; color: var(--muted); }
        .nav .right a:hover { color: var(--txt); }
        .bag { color: var(--txt); font-weight: 600; }
        .bag .n { background: var(--accent); color: var(--on-accent); min-width: 19px; height: 19px; padding: 0 5px; border-radius: 5px; font-size: 11px; font-weight: 700; display: inline-grid; place-items: center; margin-left: 5px; font-family: var(--mono); }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; z-index: 80; color: var(--txt); }

        /* dropdown (lang / currency / nav groups) */
        .menu { position: relative; }
        .menu summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: 14px; user-select: none; }
        .menu summary::-webkit-details-marker, .menu summary::marker { display: none; content: none; }
        .menu summary:hover { color: var(--txt); }
        .menu .chev { width: 11px; height: 11px; fill: none; stroke: currentColor; stroke-width: 1.6; transition: transform .2s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items { position: absolute; top: calc(100% + 12px); right: 0; min-width: 200px; background: var(--surface); border: 1px solid var(--line2); border-radius: 8px; padding: 8px; z-index: 70; box-shadow: 0 22px 44px -22px rgba(60, 44, 22, .45); animation: menuIn .22s cubic-bezier(.19, .7, .16, 1); }
        @keyframes menuIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .menu-items { animation: none; } }
        .menu-items a { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border-radius: 5px; font-size: 14px; color: var(--txt); }
        .menu-items a:hover { background: var(--surface2); }
        .menu-items a.active { color: var(--accent-deep); font-weight: 600; }
        .menu-items .check { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .menu-items a:not(.active) .check { visibility: hidden; }
        .menu.nav-menu .menu-items { left: 0; right: auto; min-width: 220px; }
        .menu.nav-menu summary { font-family: var(--display); font-size: 16px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; }
        .menu.nav-menu .menu-items a[data-depth]:not([data-depth="0"]) { padding-left: calc(12px + 14px * var(--d, 0)); color: var(--muted); }
        .menu.nav-menu .menu-items a.view-all { color: var(--accent-deep); font-weight: 600; }

        /* mobile drawer */
        .m-drawer { position: fixed; inset: 0; z-index: 70; background: var(--deep); color: #f0e7d6; display: flex; flex-direction: column; justify-content: center; padding: 0 38px; opacity: 0; visibility: hidden; transition: opacity .45s ease, visibility .45s; }
        .m-drawer.open { opacity: 1; visibility: visible; }
        .m-drawer .mclose { position: absolute; top: 20px; right: 30px; background: none; border: none; font-size: 26px; color: #f0e7d6; }
        .m-drawer .mlogo { position: absolute; top: 22px; left: 38px; font-family: var(--display); font-weight: 700; font-size: 20px; letter-spacing: .02em; text-transform: uppercase; }
        .m-drawer .mlogo b { color: var(--accent); }
        .m-drawer nav { display: flex; flex-direction: column; gap: 2px; }
        .m-drawer nav a { font-family: var(--display); font-weight: 700; font-size: 40px; letter-spacing: .02em; text-transform: uppercase; padding: 8px 0; display: flex; align-items: baseline; opacity: 0; transform: translateY(18px); transition: opacity .5s ease, transform .55s cubic-bezier(.19, .7, .16, 1); }
        .m-drawer.open nav a { opacity: 1; transform: none; }
        .m-drawer.open nav a:nth-child(1) { transition-delay: .06s; } .m-drawer.open nav a:nth-child(2) { transition-delay: .12s; }
        .m-drawer.open nav a:nth-child(3) { transition-delay: .18s; } .m-drawer.open nav a:nth-child(4) { transition-delay: .24s; }
        .m-drawer.open nav a:nth-child(5) { transition-delay: .30s; } .m-drawer.open nav a:nth-child(6) { transition-delay: .36s; }
        .m-drawer nav a .ix { font-family: var(--mono); font-size: 12px; font-weight: 400; color: var(--accent); margin-right: 14px; }
        .m-drawer .mfoot { position: absolute; bottom: 30px; left: 38px; font-family: var(--mono); font-size: 11px; letter-spacing: .04em; text-transform: uppercase; color: #94866c; opacity: 0; transition: opacity .5s ease .4s; }
        .m-drawer.open .mfoot { opacity: 1; }
        @media (prefers-reduced-motion: reduce) { .m-drawer nav a, .m-drawer .mfoot { opacity: 1 !important; transform: none !important; transition: none !important; } }

        /* section + page heads (shared by inner pages) — ruled like a cutting
           list, with the ruler ticks under the rule. */
        .sec-head { position: relative; display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; border-bottom: 2px solid var(--txt); padding-bottom: 14px; margin: 80px 0 30px; }
        .sec-head .kicker { display: block; }
        .sec-head h2 { font-family: var(--display); font-weight: 700; letter-spacing: .01em; text-transform: uppercase; font-size: clamp(30px, 4vw, 52px); line-height: 1; }
        .sec-head h2 em { font-style: normal; color: var(--accent-deep); }
        .sec-head .more { font-family: var(--mono); font-size: 12px; color: var(--accent-deep); text-transform: uppercase; letter-spacing: .04em; }
        .page-head { padding: 46px 0 26px; border-bottom: 2px solid var(--txt); }
        .page-head .crumb { font-family: var(--mono); font-size: 12px; letter-spacing: .02em; color: var(--faint); }
        .page-head .crumb a:hover { color: var(--accent-deep); }
        .page-head h1 { font-family: var(--display); font-weight: 700; letter-spacing: .01em; text-transform: uppercase; font-size: clamp(40px, 4.6vw, 62px); line-height: 1; margin-top: 8px; }
        .page-head h1 em { font-style: normal; color: var(--accent-deep); }
        .page-head p { color: var(--muted); max-width: 48ch; margin: 8px 0 0; }

        /* product card — price-list entry (home, catalog, collection, related).
           Signature: every grid stamps its cards with a lot index (LOT 01,
           LOT 02…) via CSS counters — pure cutting-ledger flavour. */
        .racks { display: grid; grid-template-columns: repeat(4, 1fr); gap: 26px 20px; padding-top: 14px; counter-reset: lot; }
        .bcard { display: block; color: inherit; cursor: pointer; position: relative; transition: transform .22s cubic-bezier(.19, .7, .16, 1); }
        .racks .bcard { counter-increment: lot; }
        .bcard:hover { transform: translateY(-5px); }
        .bcard .pic { height: 300px; border: 1px solid var(--line); border-radius: 8px; margin-bottom: 12px; position: relative; overflow: hidden; display: grid; place-items: center; background: var(--surface); box-shadow: 0 2px 0 0 var(--line); transition: border-color .3s ease, box-shadow .3s ease; }
        .racks .bcard .pic::after { content: var(--lot-label, "LOT ") counter(lot, decimal-leading-zero); position: absolute; bottom: 10px; right: 12px; z-index: 2; font-family: var(--mono); font-size: 10px; letter-spacing: .12em; color: var(--faint); background: color-mix(in srgb, var(--surface) 80%, transparent); border: 1px solid var(--line); border-radius: 4px; padding: 2px 7px; }
        .racks.no-lot .bcard .pic::after { display: none; }
        .bcard:hover .pic { border-color: var(--line2); box-shadow: 0 4px 0 0 var(--line2), 0 20px 34px -24px rgba(60, 44, 22, .5); }
        .bcard .badge { position: absolute; top: 12px; left: 12px; font-family: var(--mono); font-size: 10px; letter-spacing: .04em; padding: 4px 10px; border: 1px solid var(--accent-deep); background: var(--plate); border-radius: 4px; color: var(--accent-deep); z-index: 2; text-transform: uppercase; }
        .bcard .cat { font-family: var(--mono); font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .02em; }
        .bcard h3 { font-family: var(--display); font-weight: 600; text-transform: uppercase; letter-spacing: .02em; font-size: 21px; margin: 3px 0 2px; line-height: 1.1; transition: color .25s ease; }
        .bcard:hover h3 { color: var(--accent-deep); }
        .bcard .foot { display: flex; align-items: center; justify-content: space-between; margin-top: 8px; }
        .bcard .pr { font-family: var(--display); font-weight: 700; font-size: 20px; font-variant-numeric: tabular-nums; color: var(--txt); }
        .bcard:hover .pr { color: var(--accent-deep); }
        .bcard .add { width: 34px; height: 34px; border: 1px solid var(--line2); color: var(--txt); border-radius: 6px; font-size: 18px; display: grid; place-items: center; box-shadow: 0 2px 0 0 var(--line2); transition: border-color .22s ease, color .22s ease, box-shadow .22s ease, transform .22s ease, background-color .22s ease; }
        .bcard:hover .add { border-color: var(--accent-deep); background: var(--accent); color: var(--on-accent); box-shadow: 0 2px 0 0 var(--accent-deep); }
        @media (prefers-reduced-motion: reduce) { .bcard, .bcard:hover { transform: none; transition: none; } }

        /* footer — the loading dock at the end of the yard */
        footer.site { position: relative; overflow: hidden; border-top: 2px solid var(--txt); padding: 0 0 34px; margin-top: 60px; background: var(--surface); }
        footer.site .rule-ticks { margin-bottom: 50px; }
        footer.site .ring.f1 { width: 190px; height: 190px; right: -60px; bottom: -70px; opacity: .5; }
        footer.site .ring.f2 { width: 90px; height: 90px; right: 100px; bottom: 30px; opacity: .35; }
        .fgrid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; }
        .fgrid .logo { font-family: var(--display); font-weight: 700; font-size: 24px; letter-spacing: .02em; text-transform: uppercase; }
        .fcol h4 { font-family: var(--mono); font-size: 11px; letter-spacing: .02em; text-transform: uppercase; color: var(--faint); margin-bottom: 16px; }
        .fcol a { display: block; font-size: 14px; margin-bottom: 10px; color: var(--muted); }
        .fcol a:hover { color: var(--accent-deep); }
        .fbot { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 22px; border-top: 1px solid var(--line); font-family: var(--mono); font-size: 12px; color: var(--faint); gap: 16px; flex-wrap: wrap; }
        .fbot a { color: var(--accent-deep); }

        /* toast */
        .toast { position: fixed; top: calc(var(--header-height) + 22px); right: 24px; background: var(--surface); color: var(--txt); padding: 14px 20px; z-index: 100; font-size: 14px; border: 1px solid var(--line2); border-radius: 8px; box-shadow: 0 22px 44px -20px rgba(60, 44, 22, .4); display: flex; align-items: center; gap: 10px; animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards; }
        .toast::before { content: "▮"; color: var(--accent); }
        @keyframes toastIn { from { transform: translateY(-8px); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-8px); } }

        @media (max-width: 1000px) {
            .racks { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 680px) {
            .wrap { padding: 0 20px; }
            .nav .links { display: none; }
            .menu-toggle { display: block; }
            .racks { grid-template-columns: repeat(2, 1fr); gap: 18px 14px; }
            .bcard .pic { height: 240px; }
            .fgrid { grid-template-columns: 1fr 1fr; }
            /* hide the whole account link, not just its label — a label-only
               anchor would leave an empty, unlabelled link in the tab order.
               The mobile drawer carries the account entry instead. */
            .nav .right .acct { display: none; }
            /* the header row is the tightest thing on a phone: a long wordmark
               plus the mode/lang/currency/cart cluster overran the viewport.
               Shrink the gaps and let the wordmark ellipsise rather than push. */
            .nav { gap: 12px; }
            .nav .right { gap: 12px; }
            .logo { font-size: 20px; min-width: 0; overflow: hidden; text-overflow: ellipsis; }
            .logo img { height: 26px; }
        }
    </style>
    {!! $theme->headExtras() !!}
    {{-- Storefront kit — motion (GSAP/Lenis) + cart drawer / quick-view (Alpine). --}}
    @vite(['resources/css/storefront.css', 'resources/js/storefront.js'])
</head>
{{-- Motion is brisk and mechanical — boards drop onto the stack; scroll stays
     near-native (a materials catalog should feel like a tool, not a mood). --}}
<body data-gv-motion='{"duration":0.9,"ease":"power3.out","distance":26,"stagger":0.08,"scroll":{"lerp":0.24,"wheelMultiplier":1.3}}'>
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
        // Stencil wordmark — last word inked in the accent (TIMBER <b>CO</b>).
        // Falls back to the flat name for single-word brands.
        $brandName = $tenant->name;
        $brandWords = preg_split('/\s+/', trim($brandName));
        $brandSplit = count($brandWords) > 1
            ? '<span>' . e(implode(' ', array_slice($brandWords, 0, -1))) . '</span> <b>' . e(end($brandWords)) . '</b>'
            : '<b>' . e($brandName) . '</b>';
    @endphp

    @if ($csAnnouncement['enabled'] && $csAnnouncement['text'] !== '')
        @php
            $tape = trim($csAnnouncement['text']);
            $tapeUnit = e($tape) . ' &nbsp;<span class="s">▮</span>&nbsp; ';
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
                        <a class="acct" href="{{ $customer ? '/account' : '/account/login' }}">{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</a>
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
        <div class="rule-ticks" aria-hidden="true"></div>
        <div class="ring f1" aria-hidden="true"></div>
        <div class="ring f2" aria-hidden="true"></div>
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
