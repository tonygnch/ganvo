<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ $cs['page_title'] ?? __('site.marketing.title') }}</title>
    <meta name="description" content="{{ $cs['meta_description'] ?? __('site.marketing.meta_description') }}">
    @include('partials.social-meta', [
        'title'       => $cs['page_title'] ?? __('site.marketing.title'),
        'description' => $cs['meta_description'] ?? __('site.marketing.meta_description'),
    ])

    {{-- Set theme before paint to avoid a flash --}}
    <script>
        (function () {
            const stored = localStorage.getItem('ganvo-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = stored || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <style>
        /* -------- Palette: light -------- */
        :root,
        [data-theme="light"] {
            --bg:           #ffffff;
            --bg-elevated:  #ffffff;
            --bg-muted:     #f8fafc;
            --bg-subtle:    #f1f5f9;
            --border:       #e2e8f0;
            --border-strong:#cbd5e1;
            --text:         #0f172a;
            --text-muted:   #475569;
            --text-soft:    #64748b;

            --brand:        #2563eb;        /* blue */
            --brand-hover:  #1d4ed8;
            --brand-soft:   #eff6ff;
            --accent:       #0ea5e9;        /* sky */
            --accent-2:     #3b82f6;        /* bright blue */
            --accent-3:     #06b6d4;        /* cyan */
            --success:      #10b981;        /* emerald */

            --dot-color:    rgba(37, 99, 235, 0.22);    /* hero pattern dot */
            --shape-stroke: rgba(37, 99, 235, 0.30);    /* hero decorative ring */

            --card-shadow:  0 1px 2px rgba(15,23,42,0.04), 0 4px 12px rgba(15,23,42,0.04);
            --card-glow:    0 18px 40px -10px rgba(37, 99, 235, 0.30);

            --gradient-text: linear-gradient(90deg, #1d4ed8, #2563eb, #0ea5e9, #22d3ee, #1d4ed8);
            --gradient-cta:  linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #06b6d4 100%);
        }

        /* -------- Palette: dark -------- */
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

            --brand:        #60a5fa;        /* blue-400 */
            --brand-hover:  #93c5fd;
            --brand-soft:   #16224a;
            --accent:       #38bdf8;        /* sky-400 */
            --accent-2:     #3b82f6;        /* blue-500 */
            --accent-3:     #22d3ee;        /* cyan-400 */
            --success:      #34d399;

            --dot-color:    rgba(96, 165, 250, 0.28);
            --shape-stroke: rgba(96, 165, 250, 0.45);

            --card-shadow:  0 1px 2px rgba(0,0,0,0.4), 0 4px 12px rgba(0,0,0,0.25);
            --card-glow:    0 18px 40px -10px rgba(59, 130, 246, 0.50);

            --gradient-text: linear-gradient(90deg, #93c5fd, #60a5fa, #38bdf8, #22d3ee, #93c5fd);
            --gradient-cta:  linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #06b6d4 100%);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
            transition: background-color .25s ease, color .25s ease;
        }
        a { color: var(--brand); text-decoration: none; }
        a:hover { text-decoration: underline; }
        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            background: var(--bg-subtle);
            padding: .15rem .35rem;
            border-radius: .25rem;
            font-size: 0.9em;
        }

        /* -------- Nav -------- */
        .nav {
            position: sticky;
            top: 0;
            background: color-mix(in srgb, var(--bg) 80%, transparent);
            backdrop-filter: saturate(180%) blur(12px);
            border-bottom: 1px solid var(--border);
            z-index: 50;
        }
        .nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: -0.02em;
            color: var(--text);
        }
        .brand-dot {
            background: var(--gradient-cta);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .nav-links { display: flex; gap: 1.5rem; align-items: center; font-size: 0.925rem; }
        .nav-links a:not(.btn) { color: var(--text-muted); }
        .nav-links a:not(.btn):hover { color: var(--text); text-decoration: none; }

        /* -------- Language dropdown -------- */
        .lang-menu {
            position: relative;
        }
        .lang-menu summary {
            list-style: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            padding: .375rem .75rem;
            background: var(--bg-subtle);
            border: 1px solid var(--border);
            border-radius: 9999px;
            color: var(--text-muted);
            font-size: 0.8125rem;
            font-weight: 600;
            transition: color .15s ease, background-color .2s ease, border-color .2s ease;
            user-select: none;
        }
        .lang-menu summary::-webkit-details-marker,
        .lang-menu summary::marker { display: none; content: none; }
        .lang-menu summary:hover { color: var(--text); border-color: var(--border-strong); }
        .lang-menu[open] summary { color: var(--text); background: var(--bg-elevated); border-color: var(--border-strong); }
        .lang-menu .globe {
            width: 16px; height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.6;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .lang-menu .chevron {
            width: 12px; height: 12px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            transition: transform .15s ease;
        }
        .lang-menu[open] .chevron { transform: rotate(180deg); }
        .lang-menu-items {
            position: absolute;
            top: calc(100% + .375rem);
            right: 0;
            min-width: 160px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: .625rem;
            box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.18), 0 2px 6px rgba(15, 23, 42, 0.06);
            padding: .375rem;
            z-index: 60;
        }
        [data-theme="dark"] .lang-menu-items {
            box-shadow: 0 12px 28px -8px rgba(0,0,0,0.55), 0 2px 6px rgba(0,0,0,0.4);
        }
        .lang-menu-items a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .5rem .75rem;
            border-radius: .375rem;
            color: var(--text);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color .12s ease;
        }
        .lang-menu-items a:hover { background: var(--bg-subtle); text-decoration: none; }
        .lang-menu-items a.active { color: var(--brand); font-weight: 600; }
        .lang-menu-items .check {
            width: 14px; height: 14px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }
        .lang-menu-items a:not(.active) .check { visibility: hidden; }
        .theme-toggle {
            background: var(--bg-subtle);
            border: 1px solid var(--border);
            color: var(--text-muted);
            border-radius: 9999px;
            width: 36px; height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform .15s ease, background-color .2s ease, color .2s ease;
        }
        .theme-toggle:hover { color: var(--text); transform: rotate(15deg); }
        .theme-toggle .sun { display: none; }
        .theme-toggle .moon { display: inline; }
        [data-theme="dark"] .theme-toggle .sun { display: inline; }
        [data-theme="dark"] .theme-toggle .moon { display: none; }

        /* -------- Buttons -------- */
        .btn {
            display: inline-block;
            padding: .625rem 1.125rem;
            border-radius: .625rem;
            font-weight: 600;
            font-size: 0.925rem;
            text-decoration: none;
            transition: transform .15s ease, box-shadow .15s ease, background-color .2s ease;
            cursor: pointer;
            border: 0;
        }
        .btn:hover { text-decoration: none; transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
        .btn-primary {
            background: var(--gradient-cta);
            color: white;
            box-shadow: 0 4px 14px -2px color-mix(in srgb, var(--accent-2) 50%, transparent);
            background-size: 200% auto;
            background-position: 0 0;
        }
        .btn-primary:hover {
            box-shadow: 0 8px 22px -2px color-mix(in srgb, var(--accent) 60%, transparent);
            background-position: 100% 0;
        }
        .btn-ghost { color: var(--text); padding: .625rem .875rem; background: transparent; }
        .btn-ghost:hover { background: var(--bg-subtle); }
        .btn-outline {
            color: var(--text);
            border: 1px solid var(--border-strong);
            background: transparent;
        }
        .btn-outline:hover { background: var(--bg-subtle); border-color: var(--brand); }
        .btn-lg { padding: .875rem 1.5rem; font-size: 1rem; }

        /* -------- Hero -------- */
        .hero {
            position: relative;
            padding: 5rem 1.5rem 6rem;
            overflow: hidden;
            background-color: var(--bg);
            background-image: radial-gradient(circle, var(--dot-color) 1px, transparent 1.4px);
            background-size: 22px 22px;
            background-position: 0 0;
        }
        .hero .shape {
            position: absolute;
            border: 1.5px solid var(--shape-stroke);
            pointer-events: none;
            opacity: .9;
        }
        .hero .shape.s3 {
            width: 64px; height: 64px;
            top: 38%; right: 14%;
            border-radius: 14px;
            transform: rotate(-12deg);
            animation: drift 12s ease-in-out infinite;
            animation-delay: -4s;
        }
        @keyframes drift {
            0%, 100% { transform: translate(0, 0) rotate(var(--rot, 0deg)); }
            50%      { transform: translate(0, -14px) rotate(var(--rot, 0deg)); }
        }
        .hero .shape.s3 { --rot: -12deg; }

        /* macOS-style browser window mockup */
        .hero .browser {
            position: absolute;
            top: 24px;
            right: 5%;
            width: 280px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 18px 40px -12px rgba(15, 23, 42, 0.18),
                        0 4px 10px -2px rgba(15, 23, 42, 0.08);
            overflow: hidden;
            pointer-events: none;
            transform: rotate(-4deg);
            animation: browserDrift 12s ease-in-out infinite;
        }
        [data-theme="dark"] .hero .browser {
            box-shadow: 0 18px 40px -12px rgba(0, 0, 0, 0.55),
                        0 4px 10px -2px rgba(0, 0, 0, 0.4);
        }
        .browser-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: var(--bg-muted);
            border-bottom: 1px solid var(--border);
        }
        .browser-dot {
            width: 11px; height: 11px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .browser-dot.red    { background: #ff5f57; }
        .browser-dot.yellow { background: #febc2e; }
        .browser-dot.green  { background: #28c840; }
        .url-pill {
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
        .browser-content {
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .browser-hero {
            height: 36px;
            border-radius: 6px;
            background: var(--gradient-cta);
            background-size: 200% auto;
            animation: gradientShift 6s linear infinite;
        }
        .browser-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .browser-tile {
            aspect-ratio: 1;
            background: var(--bg-subtle);
            border-radius: 6px;
        }
        .browser-line {
            height: 6px;
            border-radius: 3px;
            background: var(--bg-subtle);
        }
        .browser-line.short { width: 55%; }

        @keyframes browserDrift {
            0%, 100% { transform: rotate(-4deg) translateY(0); }
            50%      { transform: rotate(-4deg) translateY(-12px); }
        }

        /* Phone mockup */
        .hero .phone {
            position: absolute;
            bottom: -10px;
            left: 6%;
            width: 130px;
            height: 250px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 6px;
            box-shadow: 0 18px 36px -12px rgba(15, 23, 42, 0.18),
                        0 4px 10px -2px rgba(15, 23, 42, 0.08);
            transform: rotate(6deg);
            pointer-events: none;
            animation: phoneDrift 14s ease-in-out infinite;
            animation-delay: -2s;
        }
        [data-theme="dark"] .hero .phone {
            box-shadow: 0 18px 36px -12px rgba(0, 0, 0, 0.55),
                        0 4px 10px -2px rgba(0, 0, 0, 0.4);
        }
        .phone-screen {
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
        .phone-notch {
            width: 36px;
            height: 4px;
            background: var(--border-strong);
            border-radius: 999px;
            margin: 2px auto 4px;
        }
        .phone-title {
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
        .phone-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9px;
            color: var(--text-muted);
            padding: 4px 0;
            border-bottom: 1px solid var(--border);
        }
        .phone-row:last-child { border-bottom: 0; }
        .phone-row .name {
            flex: 1;
            height: 6px;
            background: var(--bg-subtle);
            border-radius: 3px;
            margin-right: 8px;
        }
        .phone-row .name.short { max-width: 40%; }
        .phone-row .price {
            font: 600 8px ui-monospace, SFMono-Regular, Menlo, monospace;
            color: var(--text-soft);
        }

        @keyframes phoneDrift {
            0%, 100% { transform: rotate(6deg) translateY(0); }
            50%      { transform: rotate(6deg) translateY(-10px); }
        }

        @media (max-width: 900px) {
            .hero .browser, .hero .phone { display: none; }
        }

        .hero-inner {
            max-width: 1100px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .badge-pill {
            display: inline-block;
            background: var(--brand-soft);
            color: var(--brand);
            padding: .375rem 1rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border: 1px solid color-mix(in srgb, var(--brand) 20%, transparent);
            animation: bob 4s ease-in-out infinite;
        }
        @keyframes bob {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-4px); }
        }
        h1 {
            font-size: clamp(2.25rem, 5vw, 3.875rem);
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin: 0 0 1.25rem;
            color: var(--text);
        }
        h1 .accent {
            background: var(--gradient-text);
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: gradientShift 6s linear infinite;
        }
        @keyframes gradientShift {
            to { background-position: 200% 0; }
        }
        .hero p.sub {
            font-size: 1.125rem;
            color: var(--text-muted);
            max-width: 640px;
            margin: 0 auto 2rem;
        }
        .cta-row { display: flex; gap: .875rem; justify-content: center; flex-wrap: wrap; }

        /* -------- Sections -------- */
        section { position: relative; }
        .features {
            padding: 5rem 1.5rem;
            max-width: 1100px;
            margin: 0 auto;
        }
        .section-eyebrow {
            text-align: center;
            color: var(--brand);
            font-weight: 700;
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: .5rem;
        }
        h2 {
            text-align: center;
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0 0 3rem;
            color: var(--text);
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
        }
        .feature-card {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.75rem;
            box-shadow: var(--card-shadow);
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-glow);
            border-color: color-mix(in srgb, var(--brand) 35%, var(--border));
        }
        .feature-icon {
            width: 44px; height: 44px;
            background: var(--gradient-cta);
            background-size: 200% auto;
            color: white;
            border-radius: .625rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            box-shadow: 0 4px 14px -2px color-mix(in srgb, var(--accent-2) 50%, transparent);
            transition: background-position .4s ease;
        }
        .feature-card:hover .feature-icon { background-position: 100% 0; }
        .feature-card h3 { font-size: 1.125rem; margin: 0 0 .5rem; color: var(--text); }
        .feature-card p { color: var(--text-muted); font-size: 0.95rem; margin: 0; }

        /* -------- Themes section -------- */
        .themes {
            background: var(--bg-muted);
            padding: 5rem 1.5rem;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }
        .themes-inner { max-width: 1000px; margin: 0 auto; }
        .theme-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
        }
        .theme-card {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 1rem;
            overflow: hidden;
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .theme-card:hover { transform: translateY(-4px); box-shadow: var(--card-glow); }
        .theme-preview {
            aspect-ratio: 16/10;
            background: linear-gradient(135deg, var(--brand-soft), var(--bg-elevated));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--brand);
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 0.1em;
        }
        .theme-preview.minimal {
            background: var(--bg-elevated);
            color: var(--text-muted);
            font-family: Georgia, serif;
            font-weight: 300;
            font-size: 1.75rem;
            font-style: italic;
            border-bottom: 1px solid var(--border);
        }
        .theme-body { padding: 1.25rem; }
        .theme-body h3 { margin: 0 0 .25rem; font-size: 1.0625rem; color: var(--text); }
        .theme-body p { margin: 0; color: var(--text-muted); font-size: 0.875rem; }

        /* -------- Pricing -------- */
        .pricing {
            padding: 5rem 1.5rem;
            max-width: 1100px;
            margin: 0 auto;
        }
        .price-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
        }
        .price-card {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 2rem;
            position: relative;
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .price-card:hover { transform: translateY(-4px); }
        .price-card.featured {
            border-color: var(--brand);
            box-shadow: var(--card-glow);
            transform: scale(1.02);
        }
        .price-card.featured:hover { transform: scale(1.02) translateY(-4px); }
        .price-card .tag {
            position: absolute;
            top: -.625rem;
            right: 1.25rem;
            background: var(--gradient-cta);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: .25rem .75rem;
            border-radius: 9999px;
            letter-spacing: 0.05em;
        }
        .price-card h3 { font-size: 1.25rem; margin: 0 0 .25rem; color: var(--text); }
        .price-card .sub { color: var(--text-muted); margin: 0; font-size: 0.925rem; }
        .price-card .price {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text);
            margin: 1rem 0;
        }
        .price-card .price small { font-size: 0.875rem; color: var(--text-soft); font-weight: 500; }
        .price-card .tbd {
            font-size: 0.75rem;
            color: var(--text-soft);
            font-style: italic;
            margin-top: .25rem;
        }
        .price-card ul {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0;
            border-top: 1px solid var(--border);
            padding-top: 1.5rem;
        }
        .price-card li {
            padding: .375rem 0;
            color: var(--text);
            font-size: 0.925rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .price-card li::before {
            content: "✓";
            color: var(--success);
            font-weight: 700;
        }

        /* -------- Monthly/Yearly toggle (CSS-only via :has(:checked)) -------- */
        .pricing-toggle-wrap {
            display: flex;
            justify-content: center;
            margin: 1.25rem 0 2.5rem;
        }
        .pricing-toggle {
            display: inline-flex;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 9999px;
            padding: 4px;
        }
        .pricing-toggle label {
            position: relative;
            padding: .5rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            border-radius: 9999px;
            transition: color .2s ease, background-color .2s ease;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            user-select: none;
        }
        .pricing-toggle input { position: absolute; opacity: 0; pointer-events: none; }
        .pricing-toggle label:has(input:checked) {
            background: var(--text);
            color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .pricing-toggle-savings {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            padding: 2px 8px;
            border-radius: 6px;
            background: rgba(34, 197, 94, 0.18);
            color: #15803d;
        }
        .pricing-toggle label:has(input:checked) .pricing-toggle-savings {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }

        /* Each price card has both period blocks rendered; toggle swaps visibility */
        .pricing-period-block.for-monthly { display: block; }
        .pricing-period-block.for-yearly  { display: none; }
        .pricing-grid-host:has(input[name="mkt_billing_period"][value="yearly"]:checked) .for-monthly { display: none; }
        .pricing-grid-host:has(input[name="mkt_billing_period"][value="yearly"]:checked) .for-yearly  { display: block; }
        /* The grid sits next to the toggle, so :has() on the section wraps both. */
        .pricing:has(input[name="mkt_billing_period"][value="yearly"]:checked) .for-monthly { display: none; }
        .pricing:has(input[name="mkt_billing_period"][value="yearly"]:checked) .for-yearly  { display: block; }

        .pricing-row {
            display: flex;
            align-items: baseline;
            gap: .625rem;
            flex-wrap: wrap;
        }
        .pricing-row .price { margin: 1rem 0 0; }
        .pricing-strike {
            color: var(--text-soft);
            font-size: 0.9375rem;
            text-decoration: line-through;
            text-decoration-thickness: 1.5px;
            font-weight: 600;
        }
        .pricing-discount-tag {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(34, 197, 94, 0.16);
            color: #15803d;
            font-size: 0.6875rem;
            font-weight: 700;
            padding: .25rem .5rem;
            border-radius: 6px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .price-card.featured .pricing-discount-tag {
            top: 1.5rem;
        }

        /* -------- CTA strip -------- */
        .cta-strip {
            background: var(--gradient-cta);
            color: white;
            padding: 4rem 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .cta-strip h2 { color: white; margin-bottom: .75rem; }
        .cta-strip p { color: rgba(255,255,255,0.9); font-size: 1.0625rem; margin: 0 0 2rem; }
        .btn-on-dark {
            background: white;
            color: var(--brand-hover);
        }
        .btn-on-dark:hover {
            background: rgba(255,255,255,0.92);
            box-shadow: 0 8px 22px -2px rgba(0,0,0,.18);
        }

        /* -------- Footer -------- */
        footer {
            padding: 2.5rem 1.5rem;
            text-align: center;
            color: var(--text-soft);
            font-size: 0.875rem;
            border-top: 1px solid var(--border);
        }
        footer .links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: .75rem;
            flex-wrap: wrap;
        }
        footer a { color: var(--text-muted); }

        /* -------- Scroll reveal -------- */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .7s ease, transform .7s ease;
        }
        .reveal.in-view { opacity: 1; transform: translateY(0); }

        @media (max-width: 640px) {
            .nav-links { gap: .5rem; }
            .nav-links .nav-link-hide-mobile { display: none; }
            .hero { padding: 3rem 1rem 4rem; }
            .features, .themes, .pricing { padding: 3rem 1rem; }
            h2 { margin-bottom: 2rem; }
            .price-card.featured, .price-card.featured:hover { transform: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .001ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>
<body>
    <nav class="nav">
        <div class="nav-inner">
            <a href="/" class="brand" aria-label="Ganvo" style="display: inline-flex; align-items: center; text-decoration: none;">
                <x-brand-lockup size="sm" />
            </a>
            <div class="nav-links">
                <a href="#features" class="nav-link-hide-mobile">{{ $cs['nav_features'] ?? __('site.marketing.nav.features') }}</a>
                <a href="#themes" class="nav-link-hide-mobile">{{ $cs['nav_themes'] ?? __('site.marketing.nav.themes') }}</a>
                <a href="#pricing" class="nav-link-hide-mobile">{{ $cs['nav_pricing'] ?? __('site.marketing.nav.pricing') }}</a>
                @php
                    $currentLocale = app()->getLocale();
                    $languages = \App\Http\Middleware\SetLocale::available();
                @endphp
                <details class="lang-menu">
                    <summary aria-label="{{ __('site.lang.switch') }}">
                        <svg class="globe" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M3 12h18"/>
                            <path d="M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>
                        </svg>
                        <span>{{ strtoupper($currentLocale) }}</span>
                        <svg class="chevron" viewBox="0 0 12 12" aria-hidden="true">
                            <path d="M3 4.5L6 7.5L9 4.5"/>
                        </svg>
                    </summary>
                    <div class="lang-menu-items" role="menu">
                        @foreach ($languages as $code => $name)
                            <a role="menuitem" href="{{ route('lang.switch', ['locale' => $code]) }}" class="@if($currentLocale===$code) active @endif">
                                <span>{{ $name }}</span>
                                <svg class="check" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M4 10l4 4 8-8"/>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </details>
                <button class="theme-toggle" type="button" aria-label="Toggle theme" id="themeToggle">
                    <span class="sun" aria-hidden="true">☀</span>
                    <span class="moon" aria-hidden="true">☾</span>
                </button>
                <a href="/onboarding/login" class="btn btn-ghost">{{ __('site.common.sign_in') }}</a>
                <a href="/onboarding/signup" class="btn btn-primary">{{ __('site.common.start_free') }}</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="browser" aria-hidden="true">
            <div class="browser-bar">
                <span class="browser-dot red"></span>
                <span class="browser-dot yellow"></span>
                <span class="browser-dot green"></span>
                <div class="url-pill">acme.{{ str_replace(':8000', '', config('ganvo.central_domain')) }}</div>
            </div>
            <div class="browser-content">
                <div class="browser-hero"></div>
                <div class="browser-grid">
                    <div class="browser-tile"></div>
                    <div class="browser-tile"></div>
                    <div class="browser-tile"></div>
                </div>
                <div class="browser-line"></div>
                <div class="browser-line short"></div>
            </div>
        </div>
        <div class="phone" aria-hidden="true">
            <div class="phone-screen">
                <div class="phone-notch"></div>
                <div class="phone-title">Aurora</div>
                <div class="phone-row"><span class="name"></span><span class="price">$29</span></div>
                <div class="phone-row"><span class="name short"></span><span class="price">$48</span></div>
                <div class="phone-row"><span class="name"></span><span class="price">$74</span></div>
                <div class="phone-row"><span class="name short"></span><span class="price">$19</span></div>
                <div class="phone-row"><span class="name"></span><span class="price">$32</span></div>
            </div>
        </div>
        <span class="shape s3" aria-hidden="true"></span>
        <div class="hero-inner">
            <span class="badge-pill">{{ $cs['hero_pill'] ?? __('site.marketing.hero.pill') }}</span>
            <h1>{{ $cs['hero_headline_1'] ?? __('site.marketing.hero.headline_1') }}<br><span class="accent">{{ $cs['hero_headline_2'] ?? __('site.marketing.hero.headline_2') }}</span></h1>
            <p class="sub">{{ $cs['hero_sub'] ?? __('site.marketing.hero.sub') }}</p>
            <div class="cta-row">
                <a href="/onboarding/signup" class="btn btn-primary btn-lg">{{ $cs['hero_cta_primary'] ?? __('site.marketing.hero.cta_primary') }}</a>
                <a href="#features" class="btn btn-outline btn-lg">{{ $cs['hero_cta_secondary'] ?? __('site.marketing.hero.cta_secondary') }}</a>
            </div>
        </div>
    </section>

    @php
        $featureIcons = ['◧', '⚐', '$', '◫', '◑', '⚙'];
        $features = __('site.marketing.features.items');
    @endphp
    <section class="features" id="features">
        <div class="section-eyebrow reveal">{{ $cs['features_eyebrow'] ?? __('site.marketing.features.eyebrow') }}</div>
        <h2 class="reveal">{{ $cs['features_h2'] ?? __('site.marketing.features.h2') }}</h2>
        <div class="feature-grid">
            @foreach ($features as $i => $f)
                <div class="feature-card reveal">
                    <div class="feature-icon">{{ $featureIcons[$i] ?? '◧' }}</div>
                    <h3>{{ $f['title'] }}</h3>
                    <p>{!! $f['body'] !!}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="themes" id="themes">
        <div class="themes-inner">
            <div class="section-eyebrow reveal">{{ $cs['themes_eyebrow'] ?? __('site.marketing.themes.eyebrow') }}</div>
            <h2 class="reveal">{{ $cs['themes_h2'] ?? __('site.marketing.themes.h2') }}</h2>
            <div class="theme-grid">
                <div class="theme-card reveal">
                    <div class="theme-preview">{{ Str::upper(__('site.marketing.themes.default_name')) }}</div>
                    <div class="theme-body">
                        <h3>{{ __('site.marketing.themes.default_name') }}</h3>
                        <p>{{ __('site.marketing.themes.default_desc') }}</p>
                    </div>
                </div>
                <div class="theme-card reveal">
                    <div class="theme-preview minimal">{{ __('site.marketing.themes.minimal_name') }}</div>
                    <div class="theme-body">
                        <h3>{{ __('site.marketing.themes.minimal_name') }}</h3>
                        <p>{{ __('site.marketing.themes.minimal_desc') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @php
        // Pull plans from the controller if provided (set on the marketing.home
        // route) — older callers may not pass them, in which case fall back to
        // an empty collection so the section quietly renders empty rather than
        // erroring.
        $plans = $plans ?? collect();
        $maxSavingsPct = 0;
        foreach ($plans as $p) { $maxSavingsPct = max($maxSavingsPct, $p->yearlySavingsPercent()); }
    @endphp
    @if ($plans->isNotEmpty())
    <section class="pricing" id="pricing">
        <div class="section-eyebrow reveal">{{ $cs['pricing_eyebrow'] ?? __('site.marketing.pricing.eyebrow') }}</div>
        <h2 class="reveal">{{ $cs['pricing_h2'] ?? __('site.marketing.pricing.h2') }}</h2>

        <div class="pricing-toggle-wrap reveal">
            <div class="pricing-toggle" role="tablist">
                <label>
                    <input type="radio" name="mkt_billing_period" value="monthly" checked>
                    {{ __('site.onboarding.plan.billing_monthly') }}
                </label>
                <label>
                    <input type="radio" name="mkt_billing_period" value="yearly">
                    {{ __('site.onboarding.plan.billing_yearly') }}
                    @if ($maxSavingsPct > 0)
                        <span class="pricing-toggle-savings">−{{ $maxSavingsPct }}%</span>
                    @endif
                </label>
            </div>
        </div>

        <div class="price-grid pricing-grid-host">
            @foreach ($plans as $plan)
                @php
                    $hasDiscount     = $plan->hasActiveDiscount();
                    $monthlyEff      = $plan->effectivePriceCentsFor('monthly');
                    $yearlyEff       = $plan->effectivePriceCentsFor('yearly');
                    // See onboarding/plan.blade.php for the rationale —
                    // yearly headline is the per-month equivalent so savings
                    // is the immediate story.
                    $yearlyAsMonthly = $yearlyEff > 0 ? (int) round($yearlyEff / 12) : 0;
                    $strikeAnchor    = $plan->price_monthly_cents;
                @endphp
                <div class="price-card @if($plan->is_popular) featured @endif reveal">
                    @if ($plan->is_popular)
                        <span class="tag">{{ __('site.marketing.pricing.popular') }}</span>
                    @endif
                    @if ($hasDiscount)
                        <span class="pricing-discount-tag">
                            −{{ $plan->discount_percent }}%@if ($plan->discount_label) · {{ $plan->discount_label }} @endif
                        </span>
                    @endif
                    <h3>{{ $plan->translated('name') }}</h3>
                    @if ($plan->translated('tagline'))
                        <p class="sub">{{ $plan->translated('tagline') }}</p>
                    @endif

                    {{-- Monthly: effective price headline + struck original when discounted --}}
                    <div class="pricing-period-block for-monthly">
                        @if ($plan->isFree())
                            <div class="price">{{ __('site.onboarding.plan.free') }}</div>
                        @else
                            <div class="pricing-row">
                                <div class="price">
                                    {{ \App\Services\Money::format($monthlyEff, $plan->currency) }}<small>{{ __('site.onboarding.plan.per_month') }}</small>
                                </div>
                                @if ($monthlyEff < $strikeAnchor)
                                    <div class="pricing-strike">{{ \App\Services\Money::format($strikeAnchor, $plan->currency) }}{{ __('site.onboarding.plan.per_month') }}</div>
                                @endif
                            </div>
                            @if ($hasDiscount && $plan->discount_ends_at)
                                <div class="tbd">{{ __('site.onboarding.plan.promo_ends', ['date' => $plan->discount_ends_at->isoFormat('LL')]) }}</div>
                            @endif
                        @endif
                    </div>

                    {{-- Yearly: per-month equivalent headline, struck original-monthly beside it,
                         "Billed annually · $TOTAL" subtitle, savings line. --}}
                    <div class="pricing-period-block for-yearly">
                        @if ($plan->isFree())
                            <div class="price">{{ __('site.onboarding.plan.free') }}</div>
                        @else
                            <div class="pricing-row">
                                <div class="price">
                                    {{ \App\Services\Money::format($yearlyAsMonthly, $plan->currency) }}<small>{{ __('site.onboarding.plan.per_month') }}</small>
                                </div>
                                @if ($yearlyAsMonthly < $strikeAnchor)
                                    <div class="pricing-strike">{{ \App\Services\Money::format($strikeAnchor, $plan->currency) }}{{ __('site.onboarding.plan.per_month') }}</div>
                                @endif
                            </div>
                            <div class="tbd">{{ __('site.onboarding.plan.billed_annually_as', ['total' => \App\Services\Money::format($yearlyEff, $plan->currency)]) }}</div>
                            @if ($plan->yearlySavingsCents() > 0)
                                <div class="tbd" style="color: var(--success); font-style: normal; font-weight: 600;">{{ __('site.onboarding.plan.you_save', ['amount' => \App\Services\Money::format($plan->yearlySavingsCents(), $plan->currency)]) }}</div>
                            @elseif ($hasDiscount && $plan->discount_ends_at)
                                <div class="tbd">{{ __('site.onboarding.plan.promo_ends', ['date' => $plan->discount_ends_at->isoFormat('LL')]) }}</div>
                            @endif
                        @endif
                    </div>

                    <ul>
                        @foreach ((array) ($plan->translated('features') ?? []) as $feat)
                            <li>{{ $feat }}</li>
                        @endforeach
                    </ul>
                    {{-- Two CTA links — one per period — only one shows at a
                         time depending on which toggle is active. Avoids
                         needing JS to update the href as the user clicks the
                         toggle. --}}
                    <a href="/onboarding/signup?plan={{ $plan->slug }}&billing_period=monthly" class="btn @if($plan->is_popular) btn-primary @else btn-outline @endif btn-lg pricing-period-block for-monthly" style="text-align:center;">
                        @if ($plan->isFree())
                            {{ __('site.marketing.pricing.cta_free') }}
                        @else
                            {{ __('site.marketing.pricing.cta_choose', ['name' => $plan->name]) }}
                        @endif
                    </a>
                    <a href="/onboarding/signup?plan={{ $plan->slug }}&billing_period=yearly" class="btn @if($plan->is_popular) btn-primary @else btn-outline @endif btn-lg pricing-period-block for-yearly" style="text-align:center;">
                        @if ($plan->isFree())
                            {{ __('site.marketing.pricing.cta_free') }}
                        @else
                            {{ __('site.marketing.pricing.cta_choose', ['name' => $plan->name]) }}
                        @endif
                    </a>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    <section class="cta-strip">
        <h2>{{ $cs['cta_strip_h2'] ?? __('site.marketing.cta_strip.h2') }}</h2>
        <p>{{ $cs['cta_strip_p'] ?? __('site.marketing.cta_strip.p') }}</p>
        <a href="/onboarding/signup" class="btn btn-on-dark btn-lg">{{ $cs['cta_strip_btn'] ?? __('site.marketing.cta_strip.btn') }}</a>
    </section>

    <footer>
        <div class="links">
            <a href="#features">{{ __('site.marketing.nav.features') }}</a>
            <a href="#themes">{{ __('site.marketing.nav.themes') }}</a>
            <a href="#pricing">{{ __('site.marketing.nav.pricing') }}</a>
            <a href="/onboarding/login">{{ __('site.common.sign_in') }}</a>
            <a href="/onboarding/signup">{{ __('site.common.create_account') }}</a>
        </div>
        <div>© {{ date('Y') }} Ganvo. {{ __('site.common.all_rights') }}</div>
    </footer>

    <script>
        // Theme toggle
        const toggle = document.getElementById('themeToggle');
        toggle.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            const next = current === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('ganvo-theme', next);
        });

        // Scroll reveal
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -10% 0px' });

            document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
        } else {
            document.querySelectorAll('.reveal').forEach(el => el.classList.add('in-view'));
        }
    </script>
</body>
</html>
