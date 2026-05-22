<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? __('site.onboarding.title')) }} — Ganvo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563EB;
            --primary-soft: color-mix(in srgb, #2563EB 12%, white);
            --primary-strong: color-mix(in srgb, #2563EB 80%, black);
            --bg: #fafaf9;
            --surface: #ffffff;
            --muted: #f5f5f4;
            --hair: #e7e5e4;
            --text: #1c1917;
            --text-muted: #57534e;
            --text-soft: #a8a29e;
            --danger: #b91c1c;
            --danger-bg: #fef2f2;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: flex;
            flex-direction: column;
        }
        a { color: inherit; text-decoration: none; }

        /* ---- Top bar ---- */
        header.bar {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--hair);
            background: var(--surface);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }
        .bar .brand {
            font-weight: 800;
            font-size: 1.125rem;
            letter-spacing: -0.02em;
            color: var(--text);
        }
        .bar .brand .dot { color: var(--primary); }
        .bar .right { display: flex; align-items: center; gap: 1rem; font-size: 0.875rem; color: var(--text-muted); }
        .bar .right a { color: var(--text-muted); transition: color .15s ease; }
        .bar .right a:hover { color: var(--text); }
        .bar form { margin: 0; }
        .bar button.linkbtn {
            background: transparent; border: 0; padding: 0;
            font: inherit; color: var(--text-muted); cursor: pointer;
        }
        .bar button.linkbtn:hover { color: var(--text); }

        /* ---- Progress strip ---- */
        .progress {
            display: flex;
            gap: .5rem;
            padding: .75rem 1.5rem;
            background: var(--surface);
            border-bottom: 1px solid var(--hair);
            justify-content: center;
            flex-wrap: wrap;
        }
        .step-pill {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .25rem .75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-soft);
            background: var(--muted);
            letter-spacing: 0.01em;
        }
        .step-pill .num {
            width: 18px; height: 18px;
            border-radius: 50%;
            background: var(--surface);
            border: 1px solid var(--hair);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6875rem;
            font-weight: 700;
        }
        .step-pill.done {
            color: var(--primary-strong);
            background: var(--primary-soft);
        }
        .step-pill.done .num { background: var(--primary); color: white; border-color: var(--primary); }
        .step-pill.current {
            color: var(--text);
            background: white;
            border: 1px solid var(--text);
        }
        .step-pill.current .num { background: var(--text); color: white; border-color: var(--text); }

        /* ---- Main content card ---- */
        main {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 3rem 1.5rem;
        }
        .panel {
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 1.25rem;
            box-shadow: 0 30px 60px -30px rgba(0,0,0,.08);
            max-width: 520px;
            width: 100%;
            padding: 2.5rem;
        }
        .panel.wide { max-width: 920px; }
        .panel-eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 .5rem;
        }
        .panel h1 {
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0 0 .5rem;
        }
        .panel .lead {
            color: var(--text-muted);
            margin: 0 0 2rem;
            font-size: 0.9375rem;
        }

        /* ---- Form primitives ---- */
        .field { margin-bottom: 1.25rem; }
        .field:last-of-type { margin-bottom: 0; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        label.lbl {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            margin-bottom: .375rem;
            color: var(--text);
        }
        input.input, select.input, textarea.input {
            width: 100%;
            padding: .75rem .875rem;
            border: 1px solid var(--hair);
            border-radius: .625rem;
            background: var(--surface);
            color: var(--text);
            font: inherit;
            font-size: 0.9375rem;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        input.input:focus, select.input:focus, textarea.input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 18%, transparent);
        }
        input.input::placeholder { color: var(--text-soft); }
        .help { font-size: 0.75rem; color: var(--text-soft); margin: .375rem 0 0; }

        .errors {
            background: var(--danger-bg);
            color: var(--danger);
            padding: .875rem 1rem;
            border: 1px solid color-mix(in srgb, var(--danger) 24%, transparent);
            border-radius: .625rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        .errors ul { margin: 0; padding-left: 1.125rem; }

        .actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .875rem 1.5rem;
            border-radius: .625rem;
            font-size: 0.9375rem;
            font-weight: 600;
            border: 0;
            cursor: pointer;
            transition: background-color .15s ease, transform .12s ease, box-shadow .15s ease, color .15s ease;
            text-decoration: none;
        }
        .btn-primary {
            background: var(--text);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary);
            transform: translateY(-1px);
            box-shadow: 0 12px 24px -8px color-mix(in srgb, var(--primary) 50%, transparent);
        }
        .btn-ghost {
            background: transparent;
            color: var(--text-muted);
            padding-left: 0;
        }
        .btn-ghost:hover { color: var(--text); }

        .muted-line {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-top: 1.5rem;
        }
        .muted-line a {
            color: var(--text);
            font-weight: 600;
            border-bottom: 1px solid currentColor;
        }
        .muted-line a:hover { color: var(--primary); border-color: var(--primary); }

        footer.foot {
            padding: 2rem 1.5rem;
            font-size: 0.75rem;
            color: var(--text-soft);
            text-align: center;
        }

        @media (max-width: 560px) {
            .panel { padding: 1.75rem; }
            .field-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="bar">
        <a href="/" class="brand">Ganvo<span class="dot">.</span></a>
        <div class="right">
            @auth
                <span>{{ auth()->user()->email }}</span>
                <form method="post" action="{{ route('onboarding.logout') }}">
                    @csrf
                    <button type="submit" class="linkbtn">{{ __('site.common.sign_out') }}</button>
                </form>
            @else
                <a href="/onboarding/login">{{ __('site.common.sign_in') }}</a>
            @endauth
        </div>
    </header>

    @isset($progressSteps)
        <div class="progress">
            @foreach ($progressSteps as $i => $step)
                <span class="step-pill {{ $step['state'] }}">
                    <span class="num">{{ $i + 1 }}</span>
                    {{ $step['label'] }}
                </span>
            @endforeach
        </div>
    @endisset

    <main>
        @yield('content')
    </main>

    <footer class="foot">
        © {{ date('Y') }} Ganvo
    </footer>
</body>
</html>
