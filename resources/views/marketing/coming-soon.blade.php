<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('site.marketing.coming_soon.title') }} — Ganvo</title>
    <meta name="description" content="{{ __('site.marketing.coming_soon.meta_description') }}">
    <meta name="robots" content="noindex, nofollow">

    {{-- Set theme before paint, same approach the main marketing page uses. --}}
    <script>
        (function () {
            var stored = localStorage.getItem('ganvo-theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', stored || (prefersDark ? 'dark' : 'light'));
        })();
    </script>

    <style>
        /* -------- Palette: shared with marketing/home.blade.php so the
                    "Launching soon" splash feels like the same product. -------- */
        :root, [data-theme="light"] {
            --bg:           #ffffff;
            --bg-elevated:  #ffffff;
            --bg-muted:     #f8fafc;
            --bg-subtle:    #f1f5f9;
            --border:       #e2e8f0;
            --border-strong:#cbd5e1;
            --text:         #0f172a;
            --text-muted:   #475569;
            --text-soft:    #64748b;
            --brand:        #2563eb;
            --brand-hover:  #1d4ed8;
            --brand-soft:   #eff6ff;
            --accent:       #0ea5e9;
            --accent-3:     #06b6d4;
            --dot-color:    rgba(37, 99, 235, 0.22);
            --shape-stroke: rgba(37, 99, 235, 0.30);
            --gradient-cta: linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #06b6d4 100%);
            --card-shadow:  0 1px 2px rgba(15,23,42,0.04), 0 4px 12px rgba(15,23,42,0.04);
        }
        [data-theme="dark"] {
            --bg:           #0b1020;
            --bg-elevated:  #131a2f;
            --bg-muted:     #0f1426;
            --bg-subtle:    #1a2240;
            --border:       #1f2942;
            --border-strong:#2a3556;
            --text:         #e7ecf5;
            --text-muted:   #a3aac4;
            --text-soft:    #818bab;
            --brand:        #60a5fa;
            --brand-hover:  #93c5fd;
            --brand-soft:   #16224a;
            --accent:       #38bdf8;
            --accent-3:     #22d3ee;
            --dot-color:    rgba(96, 165, 250, 0.28);
            --shape-stroke: rgba(96, 165, 250, 0.45);
            --gradient-cta: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #06b6d4 100%);
            --card-shadow:  0 1px 2px rgba(0,0,0,0.4), 0 4px 12px rgba(0,0,0,0.25);
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        /* Lock the splash to the viewport — no scrollbar, no overflow.
           Uses dvh so iOS Safari's chrome doesn't cause a sudden resize
           when it hides/shows; falls back to vh on browsers that don't
           support dvh. The nav + hero + footer flex-children below
           must add up to fit, which is why hero gets `min-height: 0`
           and `overflow: hidden`. */
        html, body {
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            /* 300ms matches the logo crossfade in the brand-lockup component,
               so theme toggle animates the bg, text, and lockup in lockstep. */
            transition: background-color .3s ease, color .3s ease;
        }
        a { color: var(--brand); text-decoration: none; }
        a:hover { text-decoration: none; }

        /* -------- Brand lockup theme switch --------
                    The lockup component stacks BOTH logo variants in the
                    same grid cell. This rule fades out whichever one
                    doesn't match :root[data-theme]. Opacity (not display)
                    so the transition the component declares on each img
                    (.3s ease) actually animates, giving us a crossfade
                    that lines up with the body bg/color transition. */
        :root[data-theme="light"] .brand-lockup-img-dark,
        :root[data-theme="dark"]  .brand-lockup-img-light { opacity: 0; }

        /* -------- Hero lockup + footer utility links --------
           The previous nav bar (with brand text + language menu + theme
           toggle) was deleted in favor of placing the brand lockup at the
           top of the hero. Language and theme utilities now live as tiny
           inline links in the footer instead. */
        .cs-lockup {
            margin: 0 0 2rem;
            display: inline-flex;
        }
        .cs-foot-links {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            margin-left: .5rem;
        }
        .cs-foot-links .sep { color: var(--border-strong); }
        .cs-foot-links a,
        .cs-foot-links button {
            background: transparent;
            border: 0;
            padding: 0;
            color: var(--text-soft);
            font: inherit;
            font-size: 0.75rem;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: color .15s ease;
            text-decoration: none;
        }
        .cs-foot-links a:hover,
        .cs-foot-links button:hover { color: var(--text); }
        .cs-foot-links a.active { color: var(--text); font-weight: 600; }
        [data-theme="light"] .cs-foot-links .moon,
        [data-theme="dark"]  .cs-foot-links .sun { display: none; }

        /* -------- Hero -------- */
        .cs-hero {
            /* flex: 1 + min-height: 0 lets the hero shrink to fill exactly
               the space left over by nav + footer in the 100dvh shell.
               overflow: hidden keeps the floating browser/phone mockups
               clipped to the hero rather than pushing the page taller. */
            flex: 1;
            min-height: 0;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background:
                radial-gradient(ellipse 80% 60% at 50% 0%, var(--brand-soft), transparent 60%),
                radial-gradient(circle 700px at 90% 110%, color-mix(in srgb, var(--accent) 12%, transparent), transparent 60%);
        }
        /* Soft dot pattern — same vocabulary as the marketing hero. */
        .cs-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(var(--dot-color) 1px, transparent 1px);
            background-size: 22px 22px;
            mask-image: radial-gradient(ellipse 60% 60% at 50% 50%, black 30%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse 60% 60% at 50% 50%, black 30%, transparent 70%);
            pointer-events: none;
            opacity: .9;
        }
        .cs-hero-inner {
            position: relative;
            text-align: center;
            max-width: 720px;
            width: 100%;
            z-index: 2;
        }

        /* -------- COMING SOON loading bar --------
                    Sits directly under the brand lockup with comfortable
                    breathing room before the headline below it.

                    Animation: the bar fills from 0% → 100% over 1.6s with
                    an easing curve, then a moving highlight shimmer slides
                    across the full track on repeat — so the page lands
                    showing the bar mid-fill (no "empty bar" first frame
                    on slow connections) and continues to feel alive after. */
        .cs-progress {
            margin: 1.25rem auto 3rem;  /* extra bottom space — was crowding the headline */
            max-width: 460px;
            width: 100%;
        }
        .cs-progress-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--brand);
            margin: 0 0 .625rem;
        }
        .cs-progress-label .pulse {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--brand);
            box-shadow: 0 0 0 0 color-mix(in srgb, var(--brand) 60%, transparent);
            animation: csPulse 1.8s ease-out infinite;
        }
        @keyframes csPulse {
            0%   { box-shadow: 0 0 0 0 color-mix(in srgb, var(--brand) 60%, transparent); }
            70%  { box-shadow: 0 0 0 8px transparent; }
            100% { box-shadow: 0 0 0 0 transparent; }
        }
        .cs-progress-track {
            position: relative;
            height: 8px;
            border-radius: 999px;
            background: var(--brand-soft);
            overflow: hidden;
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--brand) 18%, transparent);
        }
        .cs-progress-fill {
            height: 100%;
            width: 100%;
            background: var(--gradient-cta);
            border-radius: 999px;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .35),
                0 1px 6px -2px color-mix(in srgb, var(--brand) 50%, transparent);
            /* Load-in animation: width grows from 0 → 100% on first paint,
               then forwards holds the bar full so the rest of the loop is
               just the highlight shimmer below. */
            transform-origin: left center;
            animation: csProgressLoad 1.6s cubic-bezier(.2, .7, .2, 1) both;
        }
        @keyframes csProgressLoad {
            0%   { transform: scaleX(0); }
            100% { transform: scaleX(1); }
        }
        /* Moving highlight on the full bar — slides left → right on repeat
           after the load-in completes. Uses a separate pseudo-element so
           the shimmer doesn't get clipped by the fill's scale animation. */
        .cs-progress-track::after {
            content: "";
            position: absolute;
            top: 0; bottom: 0;
            left: -35%;
            width: 35%;
            background: linear-gradient(90deg,
                transparent 0%,
                rgba(255, 255, 255, .75) 50%,
                transparent 100%);
            animation: csProgressShimmer 2.4s ease-in-out 1.4s infinite;
            pointer-events: none;
        }
        [data-theme="dark"] .cs-progress-track::after {
            background: linear-gradient(90deg,
                transparent 0%,
                color-mix(in srgb, var(--brand) 75%, white) 50%,
                transparent 100%);
        }
        @keyframes csProgressShimmer {
            0%   { left: -35%; }
            100% { left: 135%; }
        }

        .cs-hero h1 {
            font-size: clamp(2.25rem, 5.5vw, 3.75rem);
            font-weight: 800;
            letter-spacing: -0.025em;
            line-height: 1.1;
            margin: 0 0 1rem;
        }
        .cs-hero h1 .accent {
            background: var(--gradient-cta);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .cs-hero p.lead {
            font-size: clamp(1rem, 1.6vw, 1.125rem);
            color: var(--text-muted);
            max-width: 540px;
            margin: 0 auto 2rem;
        }

        /* -------- Email capture form — pill input + gradient CTA matching marketing -------- */
        .cs-form {
            display: flex;
            gap: .5rem;
            max-width: 480px;
            margin: 0 auto;
            padding: 6px;
            border: 1px solid var(--border);
            background: var(--bg-elevated);
            border-radius: 9999px;
            box-shadow: var(--card-shadow);
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .cs-form:focus-within {
            border-color: var(--brand);
            box-shadow: 0 0 0 4px var(--brand-soft);
        }
        .cs-form input {
            flex: 1;
            border: 0;
            background: transparent;
            color: var(--text);
            font: inherit;
            font-size: 0.9375rem;
            padding: .625rem 1rem;
        }
        .cs-form input::placeholder { color: var(--text-soft); }
        .cs-form input:focus { outline: none; }
        .cs-form button {
            border: 0;
            background: var(--gradient-cta);
            color: white;
            font-weight: 600;
            font-size: 0.9375rem;
            padding: .625rem 1.25rem;
            border-radius: 9999px;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .2s ease, filter .2s ease;
            box-shadow: 0 6px 16px -6px rgba(37, 99, 235, 0.45);
        }
        .cs-form button:hover { transform: translateY(-1px); filter: brightness(1.05); box-shadow: 0 10px 22px -6px rgba(37, 99, 235, 0.55); }

        .cs-form-thanks {
            display: none;
            color: var(--brand);
            font-weight: 600;
            margin-top: 1rem;
        }
        .cs-form:has(input.submitted) { display: none; }
        .cs-form:has(input.submitted) + .cs-form-thanks { display: block; }

        .cs-helper { margin-top: 1rem; color: var(--text-soft); font-size: 0.8125rem; }

        /* -------- macOS browser mockup -- lifted from marketing/home.blade.php
                    so this page reads as part of the same product family. -------- */
        .cs-browser {
            position: absolute;
            top: 6%;
            right: 4%;
            width: 280px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 18px 40px -12px rgba(15, 23, 42, 0.18), 0 4px 10px -2px rgba(15, 23, 42, 0.08);
            overflow: hidden;
            transform: rotate(-4deg);
            pointer-events: none;
            animation: cs-browser-drift 12s ease-in-out infinite;
            z-index: 1;
        }
        [data-theme="dark"] .cs-browser {
            box-shadow: 0 18px 40px -12px rgba(0, 0, 0, 0.55), 0 4px 10px -2px rgba(0, 0, 0, 0.4);
        }
        .cs-browser-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: var(--bg-muted);
            border-bottom: 1px solid var(--border);
        }
        .cs-browser-dot {
            width: 11px; height: 11px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .cs-browser-dot.red    { background: #ff5f57; }
        .cs-browser-dot.yellow { background: #febc2e; }
        .cs-browser-dot.green  { background: #28c840; }
        .cs-url-pill {
            flex: 1;
            margin-left: 10px;
            height: 16px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 4px;
            display: flex;
            align-items: center;
            padding: 0 6px;
            color: var(--text-soft);
            font: 500 9px/1 ui-monospace, SFMono-Regular, Menlo, monospace;
            letter-spacing: 0.02em;
            overflow: hidden;
            white-space: nowrap;
        }
        .cs-browser-content {
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .cs-browser-hero {
            height: 36px;
            border-radius: 6px;
            background: var(--gradient-cta);
            background-size: 200% auto;
            animation: cs-gradient-shift 6s linear infinite;
        }
        .cs-browser-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .cs-browser-tile { aspect-ratio: 1; background: var(--bg-subtle); border-radius: 6px; }
        .cs-browser-line { height: 6px; border-radius: 3px; background: var(--bg-subtle); }
        .cs-browser-line.short { width: 55%; }
        @keyframes cs-browser-drift {
            0%, 100% { transform: rotate(-4deg) translateY(0); }
            50%      { transform: rotate(-4deg) translateY(-12px); }
        }
        @keyframes cs-gradient-shift {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        /* -------- Phone mockup -- same provenance as the browser frame above. -------- */
        .cs-phone {
            position: absolute;
            bottom: 8%;
            left: 4%;
            width: 130px;
            height: 250px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 6px;
            box-shadow: 0 18px 36px -12px rgba(15, 23, 42, 0.18), 0 4px 10px -2px rgba(15, 23, 42, 0.08);
            transform: rotate(6deg);
            pointer-events: none;
            animation: cs-phone-drift 14s ease-in-out infinite;
            animation-delay: -2s;
            z-index: 1;
        }
        [data-theme="dark"] .cs-phone {
            box-shadow: 0 18px 36px -12px rgba(0, 0, 0, 0.55), 0 4px 10px -2px rgba(0, 0, 0, 0.4);
        }
        .cs-phone-screen {
            width: 100%;
            height: 100%;
            background: var(--bg);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 8px 10px;
            gap: 6px;
            position: relative;
        }
        .cs-phone-notch {
            width: 36px;
            height: 4px;
            background: var(--border-strong);
            border-radius: 999px;
            margin: 2px auto 4px;
        }
        .cs-phone-title {
            font-family: Georgia, serif;
            font-style: italic;
            color: var(--text);
            text-align: center;
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border);
        }
        .cs-phone-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9px;
            color: var(--text-muted);
            padding: 4px 0;
            border-bottom: 1px solid var(--border);
        }
        .cs-phone-row:last-child { border-bottom: 0; }
        .cs-phone-row .name {
            flex: 1;
            height: 6px;
            background: var(--bg-subtle);
            border-radius: 3px;
            margin-right: 8px;
        }
        .cs-phone-row .name.short { max-width: 40%; }
        .cs-phone-row .price {
            font: 600 8px ui-monospace, SFMono-Regular, Menlo, monospace;
            color: var(--text-soft);
        }
        @keyframes cs-phone-drift {
            0%, 100% { transform: rotate(6deg) translateY(0); }
            50%      { transform: rotate(6deg) translateY(-10px); }
        }

        /* Tablet + phone (~420–900px): shrink the mockups so they don't crowd
           the narrower headline column, lower their opacity so they read as
           background decoration rather than competing with the form. */
        @media (max-width: 900px) {
            .cs-browser { width: 220px; top: 4%; right: 3%; opacity: .55; }
            .cs-phone   { width: 110px; height: 210px; bottom: 6%; left: 3%; opacity: .55; }
        }
        @media (max-width: 600px) {
            .cs-browser { width: 170px; top: 2%; right: 2%; opacity: .4; }
            .cs-phone   { width: 90px; height: 170px; bottom: 4%; left: 2%; opacity: .4; }
        }
        /* Below ~420px the headline runs edge-to-edge — at that point the
           mockups crash into the form regardless of size. Hide them. */
        @media (max-width: 420px) {
            .cs-browser, .cs-phone { display: none; }
        }

        /* -------- Footer -------- */
        footer.cs-foot {
            /* flex-shrink: 0 so the footer keeps its natural height instead
               of being squeezed when the hero wants more room. */
            flex-shrink: 0;
            padding: 1rem 1.5rem;
            text-align: center;
            color: var(--text-soft);
            font-size: 0.75rem;
            letter-spacing: 0.04em;
            border-top: 1px solid var(--border);
        }

        /* On very short viewports (landscape phones, etc.) free the page to
           scroll so nothing gets clipped. The visual lock is still in place
           for any reasonably tall screen — laptops, desktops, tablets. */
        @media (max-height: 620px) {
            html, body { height: auto; overflow: auto; }
            .cs-hero { padding: 2rem 1.5rem 3rem; }
        }

        /* -------- Mobile (≤ 720px) --------
           Portrait phones can't fit lockup + progress + 2-line headline +
           lead + form + helper inside 100dvh once we account for the OS
           chrome and the footer. Release the viewport lock and let the page
           scroll naturally; tighten vertical spacing so the content still
           feels intentional rather than padded. */
        @media (max-width: 720px) {
            /* Release the desktop viewport lock. The body becomes the thing
               that holds the viewport minimum — so footer sits at the
               bottom of a tall phone, and the page can grow past the
               viewport (and scroll) when content needs more room. The hero
               keeps its flex:1 from the base rule so it eats the leftover
               space above the footer without itself being forced to 100dvh
               (which is what was pushing the footer off-screen). */
            html, body { height: auto; overflow: auto; }
            body { min-height: 100dvh; }
            .cs-hero {
                padding: 2rem 1.25rem 2.5rem;
                /* Squashed-ellipse background fits the narrower hero. */
                background:
                    radial-gradient(ellipse 100% 50% at 50% 0%, var(--brand-soft), transparent 65%),
                    radial-gradient(circle 500px at 100% 110%, color-mix(in srgb, var(--accent) 12%, transparent), transparent 65%);
            }
            .cs-lockup    { margin: 0 0 1.25rem; }
            .cs-progress  { margin: .75rem auto 1.75rem; max-width: 320px; }
            .cs-hero h1   { margin: 0 0 .75rem; line-height: 1.15; }
            .cs-hero p.lead { margin: 0 auto 1.5rem; padding: 0 .25rem; }
            .cs-form      { padding: 5px; max-width: 100%; }
            .cs-form input  { font-size: 16px; /* prevents iOS zoom-on-focus */ padding: .625rem .75rem; }
            .cs-form button { padding: .625rem 1rem; font-size: 0.875rem; }
            .cs-helper    { font-size: 0.75rem; margin-top: .875rem; }
            footer.cs-foot {
                padding: .875rem 1rem;
                /* Wrap the brand line + links onto two lines on tiny widths
                   instead of squishing them into the same row. */
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: .25rem .5rem;
            }
            .cs-foot-links { margin-left: 0; flex-wrap: wrap; justify-content: center; gap: .5rem; }
        }

        /* -------- Tiny phones (≤ 380px) — iPhone SE-class --------
           One more notch tighter so nothing feels cramped at the smallest
           realistic viewport widths. */
        @media (max-width: 380px) {
            .cs-hero        { padding: 1.5rem 1rem 2rem; }
            .cs-hero h1     { font-size: 2rem; }
            .cs-hero p.lead { font-size: 0.9375rem; }
            .cs-progress    { margin: .5rem auto 1.25rem; max-width: 280px; }
            .cs-progress-label { font-size: 0.625rem; letter-spacing: 0.18em; }
            .cs-lockup .brand-lockup-stack img,
            .cs-lockup img { height: 48px !important; }
            .cs-form input  { padding: .5rem .625rem; }
            .cs-form button { padding: .5rem .875rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.001s !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001s !important;
            }
        }
    </style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $languages = \App\Http\Middleware\SetLocale::available();
        $brandedHost = str_replace(':8000', '', config('ganvo.central_domain'));
    @endphp

    <section class="cs-hero">
        {{-- Decorative storefront preview floating in the top-right --}}
        <div class="cs-browser" aria-hidden="true">
            <div class="cs-browser-bar">
                <span class="cs-browser-dot red"></span>
                <span class="cs-browser-dot yellow"></span>
                <span class="cs-browser-dot green"></span>
                <div class="cs-url-pill">acme.{{ $brandedHost }}</div>
            </div>
            <div class="cs-browser-content">
                <div class="cs-browser-hero"></div>
                <div class="cs-browser-grid">
                    <div class="cs-browser-tile"></div>
                    <div class="cs-browser-tile"></div>
                    <div class="cs-browser-tile"></div>
                </div>
                <div class="cs-browser-line"></div>
                <div class="cs-browser-line short"></div>
            </div>
        </div>

        {{-- Mobile storefront mock floating in the bottom-left --}}
        <div class="cs-phone" aria-hidden="true">
            <div class="cs-phone-screen">
                <div class="cs-phone-notch"></div>
                <div class="cs-phone-title">Aurora</div>
                <div class="cs-phone-row"><span class="name"></span><span class="price">$29</span></div>
                <div class="cs-phone-row"><span class="name short"></span><span class="price">$48</span></div>
                <div class="cs-phone-row"><span class="name"></span><span class="price">$74</span></div>
                <div class="cs-phone-row"><span class="name short"></span><span class="price">$19</span></div>
                <div class="cs-phone-row"><span class="name"></span><span class="price">$32</span></div>
            </div>
        </div>

        <div class="cs-hero-inner">
            {{-- Brand lockup at the top — uses the user-supplied image
                 at public/images/brand/logo-lockup.png. The Blade
                 component falls back to a text-only wordmark when the
                 file isn't on disk. --}}
            <a href="/" class="cs-lockup" aria-label="Ganvo">
                <x-brand-lockup size="lg" />
            </a>

            {{-- COMING SOON loading bar — sits directly under the lockup
                 with the bar 100% filled (brand gradient), so it reads as
                 "almost there" rather than mid-loading. --}}
            <div class="cs-progress" role="status" aria-live="polite">
                <div class="cs-progress-label">
                    <span class="pulse" aria-hidden="true"></span>
                    {{ __('site.marketing.coming_soon.eyebrow') }}
                </div>
                <div class="cs-progress-track" aria-hidden="true">
                    <div class="cs-progress-fill"></div>
                </div>
            </div>

            <h1>
                {{ __('site.marketing.coming_soon.headline_1') }}
                <br>
                <span class="accent">{{ __('site.marketing.coming_soon.headline_2') }}</span>
            </h1>
            <p class="lead">{{ __('site.marketing.coming_soon.lead') }}</p>

            <form class="cs-form"
                  onsubmit="event.preventDefault(); var i = this.querySelector('input'); i.classList.add('submitted'); i.setAttribute('readonly','');">
                <input type="email" required placeholder="{{ __('site.marketing.coming_soon.email_placeholder') }}">
                <button type="submit">{{ __('site.marketing.coming_soon.notify') }}</button>
            </form>
            <p class="cs-form-thanks">✓ {{ __('site.marketing.coming_soon.thanks') }}</p>

            <p class="cs-helper">{{ __('site.marketing.coming_soon.helper') }}</p>
        </div>
    </section>

    <footer class="cs-foot">
        © {{ date('Y') }} Ganvo
        <span class="cs-foot-links">
            <span class="sep">·</span>
            @foreach ($languages as $code => $name)
                <a href="/lang/{{ $code }}"
                   class="@if($currentLocale === $code) active @endif"
                   aria-label="{{ $name }}">{{ strtoupper($code) }}</a>@if(! $loop->last) <span style="color: var(--border)">/</span>@endif
            @endforeach
            <span class="sep">·</span>
            <button type="button" id="csThemeToggle" aria-label="Toggle theme">
                <span class="sun" aria-hidden="true">☀</span>
                <span class="moon" aria-hidden="true">☾</span>
            </button>
        </span>
    </footer>

    <script>
        // Theme toggle — preference is shared with the main marketing
        // page via localStorage so the choice roundtrips when the site
        // launches.
        document.getElementById('csThemeToggle').addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-theme') || 'light';
            var next = current === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('ganvo-theme', next); } catch (e) { /* private mode */ }
        });
    </script>
</body>
</html>
