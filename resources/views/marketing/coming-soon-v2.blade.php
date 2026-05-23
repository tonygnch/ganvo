<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.favicon')
    <title>{{ $cs['page_title'] ?? __('site.marketing.coming_soon.title') }} — Ganvo</title>
    <meta name="description" content="{{ $cs['meta_description'] ?? __('site.marketing.coming_soon.meta_description') }}">
    <meta name="robots" content="noindex, nofollow">

    <style>
        :root {
            --bg-deep:   #020a18;
            --bg:        #050f26;
            --hair:      rgba(120, 180, 255, .08);
            --hair-soft: rgba(120, 180, 255, .04);
            --grid:      rgba(120, 180, 255, .04);
            --text:      #e8edff;
            --text-dim:  #8aa0c8;
            --text-faint:#5b6f99;
            --cyan:      #00d4ff;
            --blue-mid:  #4a9eff;
            --blue-deep: #1a3a8a;
            --ice:       #cbe8ff;
            --navy:      #0a1430;
            --primary-gradient: linear-gradient(135deg, var(--cyan), var(--blue-mid) 60%, var(--ice));
        }

        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            height: 100vh; height: 100dvh;
            overflow: hidden;
            background: var(--bg-deep);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, 'Inter', sans-serif;
        }
        body { display: flex; flex-direction: column; position: relative; }
        a { color: var(--cyan); text-decoration: none; }

        /* -------- Background atmospherics (z-index: 0) -------- */
        .bg-mesh {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
            background:
                radial-gradient(ellipse 700px 500px at 20% 25%, rgba(74, 158, 255, .35), transparent 60%),
                radial-gradient(ellipse 800px 600px at 80% 75%, rgba(0, 212, 255, .25), transparent 60%),
                radial-gradient(ellipse 600px 500px at 50% 100%, rgba(26, 58, 138, .3),  transparent 60%);
            filter: blur(2px);
            animation: meshShift 24s ease-in-out infinite alternate;
        }
        @keyframes meshShift {
            0%   { transform: translate(0, 0) scale(1); }
            50%  { transform: translate(-30px, 20px) scale(1.05); }
            100% { transform: translate(30px, -20px) scale(.98); }
        }
        .bg-grid {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(var(--grid) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 40%, transparent 85%);
            -webkit-mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 40%, transparent 85%);
        }
        .bg-scanlines {
            position: absolute; inset: 0; z-index: 0; pointer-events: none; opacity: .25;
            background-image: repeating-linear-gradient(0deg,
                rgba(255, 255, 255, .015) 0px, rgba(255, 255, 255, .015) 1px,
                transparent 1px, transparent 3px);
        }
        .bg-spotlight {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
            background: radial-gradient(circle 400px at var(--mx, 50%) var(--my, 50%), rgba(0, 212, 255, .10), transparent 70%);
        }

        /* -------- Full-page robot canvas (z-index: 1, behind text) --------
           Bo lives here. Pointer-events off so clicks pass through to the
           form. Hidden until Three.js boots; CSS fallback is a static SVG
           if WebGL is unavailable. */
        #boCanvas {
            position: fixed; inset: 0; z-index: 1;
            pointer-events: none;
            opacity: 0;
            transition: opacity .6s ease;
        }
        #boCanvas.ready { opacity: 1; }
        .bo-fallback {
            position: fixed; left: 50%; top: 15%;
            transform: translateX(-50%);
            z-index: 1; pointer-events: none;
            width: 140px; height: 200px;
            opacity: .45;
        }
        .bo-fallback svg { width: 100%; height: 100%; }
        #boCanvas.ready ~ .bo-fallback { display: none; }

        /* -------- Top status bar -------- */
        .statusbar {
            position: relative; z-index: 3; flex-shrink: 0;
            display: flex; align-items: center; justify-content: space-between;
            padding: .875rem 1.5rem;
            border-bottom: 1px solid var(--hair-soft);
            font: 500 0.6875rem/1 ui-monospace, 'JetBrains Mono', monospace;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-faint);
            backdrop-filter: blur(8px);
            background: rgba(2, 10, 24, .3);
        }
        .statusbar .left  { display: flex; gap: 1.25rem; align-items: center; }
        .statusbar .right { display: flex; gap: 1rem; align-items: center; }
        .statusbar .sep   { color: rgba(120, 180, 255, .15); }
        .statusbar .dot   { width: 6px; height: 6px; border-radius: 50%; background: var(--cyan); box-shadow: 0 0 8px var(--cyan); animation: livePulse 2s ease-in-out infinite; }
        @keyframes livePulse { 0%, 100% { opacity: 1; } 50% { opacity: .4; } }
        .statusbar a { color: var(--text-faint); transition: color .15s; }
        .statusbar a:hover { color: var(--cyan); }
        .statusbar a.active { color: var(--text); }

        /* -------- Hero (centered) -------- */
        .hero {
            position: relative; z-index: 2;
            flex: 1; min-height: 0;
            display: flex; flex-direction: column; align-items: center; justify-content: flex-end;
            padding: 0 1.5rem 5rem;
            text-align: center;
        }
        /* Pushes the hero content to the bottom half of the viewport, so
           Bo has room to land in the top half + reach down to point at
           the lockup. */
        .hero-content { width: 100%; max-width: 720px; padding-bottom: 1rem; }

        .lockup-wrap { margin: 0 0 1.5rem; }
        .lockup-wrap img { filter: drop-shadow(0 0 24px rgba(0, 212, 255, .25)); }

        /* Staggered reveal */
        .stagger { opacity: 0; transform: translateY(12px); animation: revealUp .7s cubic-bezier(.2, .7, .2, 1) forwards; }
        @keyframes revealUp { to { opacity: 1; transform: translateY(0); } }
        /* Delays start AFTER Bo's hop-in (~1.2s) so the text reveals as
           a natural follow-through to his landing. */
        .stagger.d0 { animation-delay: 1.0s; }
        .stagger.d1 { animation-delay: 1.12s; }
        .stagger.d2 { animation-delay: 1.24s; }
        .stagger.d3 { animation-delay: 1.36s; }
        .stagger.d4 { animation-delay: 1.48s; }
        .stagger.d5 { animation-delay: 1.60s; }
        .stagger.d6 { animation-delay: 1.72s; }

        .eyebrow {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .375rem .875rem;
            background: rgba(0, 212, 255, .08);
            border: 1px solid rgba(0, 212, 255, .25);
            border-radius: 999px;
            font: 600 0.6875rem/1 ui-monospace, monospace;
            color: var(--cyan);
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin: 0 0 1.25rem;
        }
        .eyebrow::before {
            content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--cyan);
            box-shadow: 0 0 8px var(--cyan); animation: livePulse 1.5s ease-in-out infinite;
        }

        h1 {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 800; line-height: 1.05; letter-spacing: -0.03em;
            margin: 0 0 1rem; max-width: 720px;
        }
        h1 .gradient {
            background: var(--primary-gradient);
            background-clip: text; -webkit-background-clip: text; color: transparent;
            background-size: 200% auto;
            animation: gradientSlide 6s linear infinite;
        }
        @keyframes gradientSlide {
            0%   { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        .lead {
            color: var(--text-dim);
            font-size: clamp(0.9375rem, 1.4vw, 1.0625rem);
            line-height: 1.6; max-width: 540px;
            margin: 0 auto 1.5rem;
        }

        .form {
            display: flex; gap: .5rem;
            width: 100%; max-width: 480px;
            padding: 5px;
            background: rgba(5, 15, 38, .65);
            border: 1px solid var(--hair);
            border-radius: 14px;
            position: relative; margin: 0 auto;
            transition: border-color .2s, box-shadow .2s, background .2s;
            backdrop-filter: blur(4px);
        }
        .form::before {
            content: ""; position: absolute; inset: -1px; border-radius: 14px;
            padding: 1px; pointer-events: none;
            background: var(--primary-gradient); opacity: 0;
            transition: opacity .25s;
            -webkit-mask: linear-gradient(black, black) content-box, linear-gradient(black, black);
            -webkit-mask-composite: xor; mask-composite: exclude;
        }
        .form:focus-within::before { opacity: 1; }
        .form:focus-within { background: rgba(255, 255, 255, .05); box-shadow: 0 0 24px rgba(0, 212, 255, .35); }
        .form input {
            flex: 1; border: 0; background: transparent;
            color: var(--text); font: inherit; font-size: 0.9375rem;
            padding: .75rem 1rem;
        }
        .form input::placeholder { color: var(--text-faint); }
        .form input:focus { outline: none; }
        .form button {
            border: 0; cursor: pointer;
            background: var(--primary-gradient); background-size: 200% auto;
            color: white; font: 600 0.9375rem/1 inherit;
            padding: 0 1.25rem; border-radius: 9px;
            transition: background-position .3s, transform .15s;
        }
        .form button:hover { background-position: 100% 50%; transform: translateY(-1px); }
        .form-helper { margin: .875rem 0 0; font-size: 0.8125rem; color: var(--text-faint); }
        .form-thanks { display: none; margin: 1rem 0 0; color: var(--cyan); font-weight: 600; }
        .form-error { display: none; margin: .625rem 0 0; color: #ff6b9d; font-size: 0.8125rem; }
        .form.hidden { display: none; }
        .form-thanks.visible, .form-error.visible { display: block; }
        .cs-honeypot { position: absolute; left: -9999px; opacity: 0; pointer-events: none; }

        footer.foot {
            position: absolute; bottom: 0; left: 0; right: 0; z-index: 3;
            padding: 1rem 1.5rem; text-align: center;
            font: 500 0.75rem/1 ui-monospace, monospace;
            color: var(--text-faint); letter-spacing: 0.06em;
            border-top: 1px solid var(--hair-soft);
            background: rgba(2, 10, 24, .3);
            backdrop-filter: blur(8px);
        }
        footer.foot a { color: var(--text-faint); transition: color .15s; }
        footer.foot a:hover, footer.foot a.active { color: var(--cyan); }
        footer.foot .sep { color: rgba(120, 180, 255, .15); margin: 0 .5rem; }

        @media (max-width: 720px) {
            .hero { padding: 0 1rem 6rem; }
            .form input { font-size: 16px; padding: .625rem .875rem; }
            .form button { padding: 0 1rem; font-size: 0.875rem; }
        }
        @media (max-height: 620px) {
            html, body { height: auto; overflow: auto; }
            footer.foot { position: relative; }
            #boCanvas { position: absolute; }
        }
        @media (prefers-reduced-motion: reduce) {
            .stagger { opacity: 1 !important; transform: none !important; animation: none !important; }
            *, *::before, *::after { animation-duration: 0.001s !important; animation-iteration-count: 1 !important; transition-duration: 0.001s !important; }
        }
    </style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $languages = \App\Http\Middleware\SetLocale::available();
    @endphp

    <div class="bg-mesh"></div>
    <div class="bg-grid"></div>
    <div class="bg-scanlines"></div>
    <div class="bg-spotlight" id="spotlight"></div>

    {{-- Full-page WebGL canvas — Bo lives here. --}}
    <canvas id="boCanvas" aria-hidden="true"></canvas>
    {{-- Static SVG fallback shown when WebGL is unavailable or the
         dynamic import fails. Hidden via #boCanvas.ready ~ .bo-fallback. --}}
    <div class="bo-fallback" aria-hidden="true">
        <svg viewBox="0 0 140 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="boBody" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%"  stop-color="#4a9eff"/>
                    <stop offset="100%" stop-color="#1a3a8a"/>
                </linearGradient>
            </defs>
            <ellipse cx="70" cy="58" rx="38" ry="38" fill="url(#boBody)" stroke="#00d4ff" stroke-width="1.5"/>
            <circle cx="56" cy="55" r="9" fill="#cbe8ff"/>
            <circle cx="84" cy="55" r="9" fill="#cbe8ff"/>
            <circle cx="56" cy="55" r="4" fill="#00d4ff"/>
            <circle cx="84" cy="55" r="4" fill="#00d4ff"/>
            <line x1="70" y1="20" x2="70" y2="6" stroke="#4a9eff" stroke-width="2"/>
            <circle cx="70" cy="6" r="4" fill="#00d4ff"/>
            <rect x="40" y="100" width="60" height="60" rx="14" fill="url(#boBody)" stroke="#00d4ff" stroke-width="1.5"/>
            <ellipse cx="70" cy="175" rx="36" ry="6" fill="none" stroke="#00d4ff" stroke-width="1.5" opacity=".6"/>
        </svg>
    </div>

    <div class="statusbar">
        <div class="left">
            <span><span class="dot"></span> SYSTEM READY</span>
            <span class="sep">·</span>
            <span id="clockOut">--:--:-- UTC</span>
        </div>
        <div class="right">
            @foreach ($languages as $code => $name)
                <a href="/lang/{{ $code }}" class="@if($currentLocale === $code) active @endif">{{ strtoupper($code) }}</a>
                @if(! $loop->last)<span class="sep">/</span>@endif
            @endforeach
        </div>
    </div>

    <section class="hero">
        <div class="hero-content">
            <div class="lockup-wrap stagger d0" id="brandLockup"><a href="/" aria-label="Ganvo"><x-brand-lockup size="md" /></a></div>

            <div class="eyebrow stagger d1">{{ $cs['eyebrow'] ?? __('site.marketing.coming_soon.eyebrow') }}</div>

            <h1>
                <span class="stagger d2" style="display: inline-block">{{ $cs['headline_1'] ?? __('site.marketing.coming_soon.headline_1') }}</span>
                <br>
                <span class="stagger d3 gradient" style="display: inline-block">{{ $cs['headline_2'] ?? __('site.marketing.coming_soon.headline_2') }}</span>
            </h1>

            <p class="lead stagger d4">{{ $cs['lead'] ?? __('site.marketing.coming_soon.lead') }}</p>

            <div class="stagger d5" style="width: 100%; max-width: 480px; margin: 0 auto;">
                <form class="form @if(session('signup_status') === 'ok') hidden @endif"
                      method="post" action="{{ route('marketing.signup') }}" id="csNotifyForm" novalidate>
                    @csrf
                    <input class="cs-honeypot" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <input type="email" name="email" required value="{{ old('email') }}"
                           placeholder="{{ $cs['email_placeholder'] ?? __('site.marketing.coming_soon.email_placeholder') }}"
                           aria-label="{{ $cs['email_placeholder'] ?? __('site.marketing.coming_soon.email_placeholder') }}">
                    <button type="submit">{{ $cs['notify_button'] ?? __('site.marketing.coming_soon.notify') }} →</button>
                </form>
                <p class="form-thanks @if(session('signup_status') === 'ok') visible @endif" id="csNotifyThanks">
                    ✓ {{ $cs['thanks_message'] ?? __('site.marketing.coming_soon.thanks') }}
                </p>
                <p class="form-error @if(session('signup_error')) visible @endif" id="csNotifyError" role="alert">{{ session('signup_error') }}</p>
            </div>

            <p class="form-helper stagger d6">{{ $cs['helper_text'] ?? __('site.marketing.coming_soon.helper') }}</p>
        </div>
    </section>

    <footer class="foot">
        © {{ date('Y') }} GANVO
        <span class="sep">·</span>
        <a href="/preview/coming-soon-v1">← classic version</a>
    </footer>

    {{-- Cursor spotlight + clock + form handler --}}
    <script>
        const spotlight = document.getElementById('spotlight');
        let rafId = null, mx = 50, my = 50;
        window.__cursor = { x: 0, y: 0 };
        document.addEventListener('mousemove', (e) => {
            mx = (e.clientX / window.innerWidth) * 100;
            my = (e.clientY / window.innerHeight) * 100;
            window.__cursor.x = (e.clientX / window.innerWidth) * 2 - 1;
            window.__cursor.y = -((e.clientY / window.innerHeight) * 2 - 1);
            if (rafId) return;
            rafId = requestAnimationFrame(() => {
                spotlight.style.setProperty('--mx', mx + '%');
                spotlight.style.setProperty('--my', my + '%');
                rafId = null;
            });
        });

        const clockOut = document.getElementById('clockOut');
        function tick() {
            const d = new Date();
            clockOut.textContent = [d.getUTCHours(), d.getUTCMinutes(), d.getUTCSeconds()]
                .map(n => String(n).padStart(2, '0')).join(':') + ' UTC';
        }
        tick(); setInterval(tick, 1000);

        (function () {
            const form = document.getElementById('csNotifyForm');
            if (! form) return;
            const thanks = document.getElementById('csNotifyThanks');
            const errorEl = document.getElementById('csNotifyError');
            const button = form.querySelector('button[type="submit"]');
            const input = form.querySelector('input[name="email"]');
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                errorEl.classList.remove('visible'); errorEl.textContent = '';
                button.disabled = true;
                fetch(form.action, {
                    method: 'POST', body: new FormData(form),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                }).then(r => r.json().then(b => ({ status: r.status, body: b })))
                  .then(({ status, body }) => {
                      if (status >= 200 && status < 300 && body && body.ok) {
                          form.classList.add('hidden');
                          thanks.classList.add('visible');
                      } else {
                          errorEl.textContent = (body && body.message) || @json(__('site.marketing.coming_soon.error_generic'));
                          errorEl.classList.add('visible');
                          button.disabled = false; input.focus();
                      }
                  }).catch(() => {
                      errorEl.textContent = @json(__('site.marketing.coming_soon.error_network'));
                      errorEl.classList.add('visible');
                      button.disabled = false;
                  });
            });
        })();
    </script>

    {{-- Bo — full-body robot. Hops in from above the viewport, lands
         in the top quarter of the page, then animates his right arm to
         point down at the brand lockup. After the entrance, idles with
         hover bob, breathing, occasional yaw, eye-tracking on cursor.

         Structure (parent → children):
           body (root group, positions whole robot in world)
             head (sphere) — has eyes (groups with sclera+pupil) + antenna
             torso (rounded cylinder)
             leftArm.shoulder (group, attached to torso L-shoulder)
               upperArm → elbow (group) → forearm → hand
             rightArm.shoulder (group, attached to torso R-shoulder)
               upperArm → elbow → forearm → hand
             hoverBase (torus disc under torso)

         State machine: 'hop-in' → 'point-at-brand' → 'idle' (loops).
         All transitions dt-based + reduced-motion safe. --}}
    <script type="module">
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const canvas = document.getElementById('boCanvas');
        const lockupEl = document.getElementById('brandLockup');

        function hasWebGL() {
            try {
                const c = document.createElement('canvas');
                return !!(c.getContext('webgl2') || c.getContext('webgl'));
            } catch (e) { return false; }
        }
        if (! hasWebGL()) {
            console.info('[bo] WebGL unavailable — SVG fallback stays');
        } else {
            try {
                const THREE = await import('https://esm.sh/three@0.160.0');

                // ---- Renderer ------------------------------------------------
                const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                renderer.setClearColor(0x000000, 0);

                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 100);
                camera.position.set(0, 0, 10);

                function sizeRenderer() {
                    const w = window.innerWidth, h = window.innerHeight;
                    renderer.setSize(w, h, false);
                    camera.aspect = w / h;
                    camera.updateProjectionMatrix();
                }
                sizeRenderer();
                window.addEventListener('resize', sizeRenderer);

                // ---- Lighting ----------------------------------------------
                scene.add(new THREE.AmbientLight(0xffffff, 0.4));
                const key = new THREE.DirectionalLight(0xcbe8ff, 1.2);
                key.position.set(3, 4, 5);
                scene.add(key);
                const rim = new THREE.DirectionalLight(0x00d4ff, 0.8);
                rim.position.set(-3, -2, -4);
                scene.add(rim);

                // ---- Materials (reused across body parts) ------------------
                const matBlueMetal = new THREE.MeshStandardMaterial({
                    color: 0x1a3a8a, metalness: 0.55, roughness: 0.35,
                    emissive: 0x0a1430, emissiveIntensity: 0.4,
                });
                const matBlueLight = new THREE.MeshStandardMaterial({
                    color: 0x4a9eff, metalness: 0.4, roughness: 0.4,
                    emissive: 0x1a3a8a, emissiveIntensity: 0.3,
                });
                const matGlowCyan = new THREE.MeshStandardMaterial({
                    color: 0x00d4ff, emissive: 0x00d4ff, emissiveIntensity: 1.5,
                });
                const matChrome = new THREE.MeshStandardMaterial({
                    color: 0x4a6fa5, metalness: 0.8, roughness: 0.3,
                });

                // ===== ROBOT BODY ==========================================
                // Root group — the whole robot positions/animates via this.
                const body = new THREE.Group();
                scene.add(body);

                // ---- Head -------------------------------------------------
                const head = new THREE.Group();
                head.position.y = 1.65;
                body.add(head);

                const skull = new THREE.Mesh(
                    new THREE.SphereGeometry(0.65, 48, 48), matBlueMetal
                );
                head.add(skull);
                // Glow halo around the head
                head.add(new THREE.Mesh(
                    new THREE.SphereGeometry(0.78, 32, 32),
                    new THREE.MeshBasicMaterial({
                        color: 0x00d4ff, transparent: true, opacity: 0.12,
                        side: THREE.BackSide, depthWrite: false,
                    })
                ));

                // ---- Eyes (groups that lookAt cursor each frame) ----------
                const makeEye = () => {
                    const eye = new THREE.Group();
                    eye.add(new THREE.Mesh(
                        new THREE.SphereGeometry(0.18, 24, 24),
                        new THREE.MeshStandardMaterial({
                            color: 0xeaf4ff, roughness: 0.35, metalness: 0.1,
                            emissive: 0xcbe8ff, emissiveIntensity: 0.25,
                        })
                    ));
                    const pupil = new THREE.Mesh(
                        new THREE.SphereGeometry(0.08, 16, 16),
                        new THREE.MeshStandardMaterial({
                            color: 0x020a18, emissive: 0x00d4ff, emissiveIntensity: 0.8,
                        })
                    );
                    pupil.position.z = 0.14;
                    eye.add(pupil);
                    return eye;
                };
                const leftEye  = makeEye(); leftEye.position.set(-0.24, 0.06, 0.55);
                const rightEye = makeEye(); rightEye.position.set(0.24, 0.06, 0.55);
                head.add(leftEye, rightEye);

                // ---- Antenna ----------------------------------------------
                const antennaStem = new THREE.Mesh(
                    new THREE.CylinderGeometry(0.02, 0.02, 0.35, 8), matChrome
                );
                antennaStem.position.y = 0.82;
                head.add(antennaStem);
                const antennaTip = new THREE.Mesh(
                    new THREE.SphereGeometry(0.08, 16, 16), matGlowCyan
                );
                antennaTip.position.y = 1.02;
                head.add(antennaTip);

                // ---- Neck -------------------------------------------------
                const neck = new THREE.Mesh(
                    new THREE.CylinderGeometry(0.18, 0.22, 0.22, 16), matChrome
                );
                neck.position.y = 1.05;
                body.add(neck);

                // ---- Torso ------------------------------------------------
                // A slightly squashed sphere reads as a rounded chest plate
                // without needing a custom shape. Scaled non-uniformly.
                const torso = new THREE.Mesh(
                    new THREE.SphereGeometry(0.7, 32, 32), matBlueMetal
                );
                torso.scale.set(1, 1.25, 0.7);
                torso.position.y = 0.4;
                body.add(torso);
                // A glowing chest dot — brand accent
                const chestDot = new THREE.Mesh(
                    new THREE.SphereGeometry(0.10, 16, 16), matGlowCyan
                );
                chestDot.position.set(0, 0.45, 0.52);
                body.add(chestDot);

                // ---- Arm factory ------------------------------------------
                // Returns the shoulder group + handles to subgroups so the
                // animation code can rotate joints directly. Arms hang down
                // by default; rotating the shoulder.x raises them forward.
                const makeArm = (side /* -1 = left, +1 = right */) => {
                    const shoulder = new THREE.Group();
                    shoulder.position.set(side * 0.65, 0.85, 0);
                    body.add(shoulder);

                    const upperArm = new THREE.Mesh(
                        new THREE.CylinderGeometry(0.10, 0.08, 0.7, 16), matBlueLight
                    );
                    upperArm.position.y = -0.35;
                    shoulder.add(upperArm);

                    const elbow = new THREE.Group();
                    elbow.position.y = -0.7;
                    shoulder.add(elbow);

                    const forearm = new THREE.Mesh(
                        new THREE.CylinderGeometry(0.08, 0.07, 0.65, 16), matBlueLight
                    );
                    forearm.position.y = -0.32;
                    elbow.add(forearm);

                    const hand = new THREE.Mesh(
                        new THREE.SphereGeometry(0.12, 16, 16), matChrome
                    );
                    hand.position.y = -0.7;
                    elbow.add(hand);
                    // Tiny glowing fingertip — sells the "pointing" gesture
                    const fingertip = new THREE.Mesh(
                        new THREE.SphereGeometry(0.045, 12, 12), matGlowCyan
                    );
                    fingertip.position.y = -0.12;
                    hand.add(fingertip);

                    return { shoulder, elbow, hand, fingertip };
                };
                const leftArm  = makeArm(-1);
                const rightArm = makeArm(+1);

                // ---- Hover base (a glowing disc instead of legs) ----------
                const hoverBase = new THREE.Group();
                hoverBase.position.y = -0.5;
                body.add(hoverBase);
                // Inner solid disc
                hoverBase.add(new THREE.Mesh(
                    new THREE.CylinderGeometry(0.55, 0.45, 0.08, 32), matBlueMetal
                ));
                // Outer glowing ring (additive blend for halo)
                const baseRing = new THREE.Mesh(
                    new THREE.TorusGeometry(0.65, 0.025, 8, 64),
                    new THREE.MeshBasicMaterial({ color: 0x00d4ff, transparent: true, opacity: 0.7 })
                );
                baseRing.rotation.x = Math.PI / 2;
                baseRing.position.y = 0;
                hoverBase.add(baseRing);
                // Soft glow underneath the base — sells the "hovering" feel
                const baseGlow = new THREE.Mesh(
                    new THREE.CircleGeometry(0.95, 32),
                    new THREE.MeshBasicMaterial({
                        color: 0x00d4ff, transparent: true, opacity: 0.18,
                        side: THREE.DoubleSide, blending: THREE.AdditiveBlending, depthWrite: false,
                    })
                );
                baseGlow.rotation.x = -Math.PI / 2;
                baseGlow.position.y = -0.1;
                hoverBase.add(baseGlow);

                // ---- Particle dust around the whole body ------------------
                const PARTICLES = 120;
                const positions = new Float32Array(PARTICLES * 3);
                for (let i = 0; i < PARTICLES; i++) {
                    const r = 1.5 + Math.random() * 1.5;
                    const theta = Math.random() * Math.PI * 2;
                    const phi = Math.acos(2 * Math.random() - 1);
                    positions[i * 3]     = r * Math.sin(phi) * Math.cos(theta);
                    positions[i * 3 + 1] = r * Math.sin(phi) * Math.sin(theta);
                    positions[i * 3 + 2] = r * Math.cos(phi);
                }
                const pGeo = new THREE.BufferGeometry();
                pGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
                const particles = new THREE.Points(pGeo, new THREE.PointsMaterial({
                    color: 0xcbe8ff, size: 0.035, transparent: true, opacity: 0.5,
                    blending: THREE.AdditiveBlending, depthWrite: false,
                }));
                body.add(particles);

                // ===== POSITIONING + LOCKUP TARGET ==========================
                // Bo's "home" position above the brand lockup. Computed from
                // the DOM rect so he visually sits over the wordmark on any
                // viewport size.
                let homePos = new THREE.Vector3(0, 1.5, 0);
                let lockupWorldPos = new THREE.Vector3(0, -0.5, 0);

                // Convert a screen-space point (px) to a world-space point
                // on the camera's z=0 plane.
                function screenToWorld(sx, sy) {
                    const ndc = new THREE.Vector2(
                        (sx / window.innerWidth) * 2 - 1,
                        -(sy / window.innerHeight) * 2 + 1
                    );
                    const v = new THREE.Vector3(ndc.x, ndc.y, 0.5).unproject(camera);
                    const dir = v.sub(camera.position).normalize();
                    const distance = -camera.position.z / dir.z;
                    return camera.position.clone().add(dir.multiplyScalar(distance));
                }
                function recomputeTargets() {
                    if (! lockupEl) return;
                    const r = lockupEl.getBoundingClientRect();
                    const cx = r.left + r.width / 2;
                    const cy = r.top + r.height / 2;
                    lockupWorldPos = screenToWorld(cx, cy);
                    // Bo lands a fixed distance above the lockup. Scale that
                    // distance with viewport so he isn't too cramped on phones.
                    const liftPx = Math.max(180, window.innerHeight * 0.30);
                    const aboveWorld = screenToWorld(cx, cy - liftPx);
                    homePos = aboveWorld.clone();
                }
                recomputeTargets();
                window.addEventListener('resize', recomputeTargets);

                // ===== STATE MACHINE ========================================
                // 'hop-in' → 'point-at-brand' → 'idle' (loops with periodic re-points)
                let state = 'hop-in';
                let stateStart = 0;
                const HOP_DURATION = 1.2;
                const POINT_DURATION = 0.6;
                const POINT_HOLD = 1.4;        // hold the point pose this long before relaxing
                const IDLE_BEFORE_REPOINT = 7.0; // every 7s in idle, point again
                // Pose targets (rotations applied via lerp toward these)
                const armIdle  = { shoulderX: 0,           elbowX: 0 };
                const armPoint = { shoulderX: -Math.PI*0.6, elbowX: Math.PI*0.05 }; // arm raised + slightly extended
                const armRelax = { shoulderX: 0,           elbowX: 0 };

                // Helper: smoothly approach a target value via lerp at given
                // smoothing factor (per-frame). Used for joint angles.
                function approach(current, target, factor) {
                    return current + (target - current) * factor;
                }

                // Easing for the hop entrance — fall-with-overshoot. Returns
                // y-offset in world units to ADD to the home Y (start above,
                // settle to home, slight squish on landing).
                function hopOffset(p) {
                    // p in [0, 1]. Curve:
                    //   0.0 → +5 (start high above home)
                    //   0.6 → -0.3 (slight overshoot below home — squish frame)
                    //   1.0 → 0 (settle)
                    if (p < 0.6) {
                        const t = p / 0.6;
                        // ease-in (gravity acceleration)
                        const eased = t * t;
                        return 5 + (-0.3 - 5) * eased;
                    } else {
                        const t = (p - 0.6) / 0.4;
                        // spring back up
                        const eased = Math.sin(t * Math.PI * 0.5);
                        return -0.3 + (0 - -0.3) * eased;
                    }
                }

                // ===== CURSOR TARGET (eye tracking) =========================
                const cursorWorld = new THREE.Vector3();
                function updateCursorTarget() {
                    cursorWorld.set(window.__cursor.x * 3, window.__cursor.y * 2 + 1.5, 3);
                }

                // ===== ANIMATION LOOP =======================================
                const clock = new THREE.Clock();
                clock.start();
                stateStart = 0;

                function animate() {
                    requestAnimationFrame(animate);
                    const t = clock.getElapsedTime();
                    const dt = Math.min(clock.getDelta(), 0.05);

                    if (reducedMotion) {
                        // Skip animation: settle Bo directly at home with arm idle.
                        body.position.copy(homePos);
                        leftArm.shoulder.rotation.x = armIdle.shoulderX;
                        rightArm.shoulder.rotation.x = armIdle.shoulderX;
                        renderer.render(scene, camera);
                        return;
                    }

                    const stateTime = t - stateStart;

                    // ---- HOP IN -------------------------------------------
                    if (state === 'hop-in') {
                        const p = Math.min(1, stateTime / HOP_DURATION);
                        const yOffset = hopOffset(p);
                        body.position.set(homePos.x, homePos.y + yOffset, homePos.z);
                        // Slight forward tumble during the fall, settles to upright
                        body.rotation.x = (1 - p) * -0.15;
                        // Squish-on-impact: scale Y a bit when bouncing
                        const squishP = Math.max(0, Math.min(1, (p - 0.55) / 0.15));
                        const squish = 1 - Math.sin(squishP * Math.PI) * 0.10;
                        body.scale.set(1 / squish, squish, 1 / squish);

                        if (p >= 1) {
                            body.rotation.x = 0;
                            body.scale.set(1, 1, 1);
                            state = 'point-at-brand';
                            stateStart = t;
                        }
                    }
                    // ---- POINT AT BRAND -----------------------------------
                    else if (state === 'point-at-brand') {
                        const p = Math.min(1, stateTime / POINT_DURATION);
                        const eased = 1 - Math.pow(1 - p, 3);
                        // Animate right arm from idle to point pose
                        rightArm.shoulder.rotation.x = armIdle.shoulderX + (armPoint.shoulderX - armIdle.shoulderX) * eased;
                        rightArm.elbow.rotation.x    = armIdle.elbowX    + (armPoint.elbowX    - armIdle.elbowX)    * eased;
                        // Lean body slightly toward the lockup for emphasis
                        body.rotation.z = eased * 0.05;
                        body.position.set(homePos.x, homePos.y + Math.sin(t * 1.4) * 0.05, homePos.z);

                        if (stateTime >= POINT_DURATION + POINT_HOLD) {
                            state = 'relax-arm';
                            stateStart = t;
                        }
                    }
                    // ---- RELAX ARM BACK TO IDLE ---------------------------
                    else if (state === 'relax-arm') {
                        const p = Math.min(1, stateTime / POINT_DURATION);
                        const eased = 1 - Math.pow(1 - p, 2);
                        rightArm.shoulder.rotation.x = armPoint.shoulderX + (armRelax.shoulderX - armPoint.shoulderX) * eased;
                        rightArm.elbow.rotation.x    = armPoint.elbowX    + (armRelax.elbowX    - armPoint.elbowX)    * eased;
                        body.rotation.z = 0.05 * (1 - eased);
                        body.position.set(homePos.x, homePos.y + Math.sin(t * 1.4) * 0.08, homePos.z);

                        if (p >= 1) {
                            state = 'idle';
                            stateStart = t;
                        }
                    }
                    // ---- IDLE (loops; periodically re-points) -------------
                    else if (state === 'idle') {
                        // Bob + breathing scale + subtle yaw
                        body.position.set(homePos.x, homePos.y + Math.sin(t * 1.4) * 0.10, homePos.z);
                        const breath = 1 + Math.sin(t * 1.8) * 0.012;
                        torso.scale.set(1 * breath, 1.25 * breath, 0.7);
                        body.rotation.y = Math.sin(t * 0.4) * 0.10;
                        body.rotation.z = Math.sin(t * 0.7) * 0.02;

                        // Re-point at the brand every IDLE_BEFORE_REPOINT seconds
                        if (stateTime > IDLE_BEFORE_REPOINT) {
                            state = 'point-at-brand';
                            stateStart = t;
                        }
                    }

                    // ---- Always-on: antenna sway, base ring spin, particles
                    antennaStem.rotation.z = Math.sin(t * 2) * 0.08;
                    antennaTip.position.x = Math.sin(t * 2) * 0.04;
                    baseRing.rotation.z += dt * 0.7;
                    particles.rotation.y += dt * 0.06;

                    // ---- Eye tracking — pupils follow the cursor ---------
                    updateCursorTarget();
                    // Need world coords for lookAt; eyes are nested deep in
                    // the body group, so getWorldPosition is more reliable
                    // than passing the cursor target directly.
                    leftEye.lookAt(cursorWorld);
                    rightEye.lookAt(cursorWorld);

                    renderer.render(scene, camera);
                }
                animate();

                // Fade canvas in after first frame
                requestAnimationFrame(() => canvas.classList.add('ready'));
            } catch (err) {
                console.warn('[bo] Three.js failed — SVG fallback stays', err);
            }
        }
    </script>
</body>
</html>
