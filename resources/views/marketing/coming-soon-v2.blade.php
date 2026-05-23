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
            --bg-deep:   #020310;
            --grid:      rgba(120, 180, 255, .04);
            --hair:      rgba(140, 180, 255, .08);
            --hair-soft: rgba(140, 180, 255, .04);
            --text:      #e8edff;
            --text-dim:  #92a0c8;
            --text-faint:#5b6789;
            --cyan:      #00f0ff;
            --magenta:   #ff2dd0;
            --violet:    #7c3aed;
            --primary-gradient: linear-gradient(135deg, var(--cyan), var(--violet) 50%, var(--magenta));
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
                radial-gradient(ellipse 600px 400px at 20% 30%, rgba(124, 58, 237, .35), transparent 60%),
                radial-gradient(ellipse 700px 500px at 80% 70%, rgba(255, 45, 208, .25), transparent 60%),
                radial-gradient(ellipse 500px 400px at 50% 100%, rgba(0, 240, 255, .25), transparent 60%);
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
            position: absolute; inset: 0; z-index: 0; pointer-events: none; opacity: .3;
            background-image: repeating-linear-gradient(0deg,
                rgba(255, 255, 255, .015) 0px, rgba(255, 255, 255, .015) 1px,
                transparent 1px, transparent 3px);
        }
        .bg-spotlight {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
            background: radial-gradient(circle 400px at var(--mx, 50%) var(--my, 50%), rgba(0, 240, 255, .08), transparent 70%);
        }

        /* -------- WebGL scene canvas (z-index: 1, behind text) -------- */
        #scene {
            position: absolute; inset: 0; z-index: 1; pointer-events: none;
            opacity: 0;            /* fades in once Three.js boots */
            transition: opacity .8s ease;
        }
        #scene.ready { opacity: 1; }

        /* -------- Static SVG fallback (shown if WebGL unavailable / no JS) -------- */
        .fallback-wireframe {
            position: absolute; z-index: 1; pointer-events: none;
            top: 50%; left: 50%;
            width: 320px; height: 320px;
            transform: translate(-50%, -50%);
            opacity: .35;
            animation: rotateSlow 30s linear infinite;
        }
        @keyframes rotateSlow {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to   { transform: translate(-50%, -50%) rotate(360deg); }
        }
        .fallback-wireframe svg { display: block; width: 100%; height: 100%; }
        .fallback-wireframe svg polygon { fill: none; stroke: var(--cyan); stroke-width: 1; }
        /* Hide the fallback once the WebGL scene reports ready. */
        #scene.ready ~ .fallback-wireframe { display: none; }

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
            background: rgba(5, 8, 23, .3);
        }
        .statusbar .left  { display: flex; gap: 1.25rem; align-items: center; }
        .statusbar .right { display: flex; gap: 1rem; align-items: center; }
        .statusbar .sep   { color: rgba(140, 180, 255, .15); }
        .statusbar .dot   { width: 6px; height: 6px; border-radius: 50%; background: var(--cyan); box-shadow: 0 0 8px var(--cyan); animation: livePulse 2s ease-in-out infinite; }
        @keyframes livePulse { 0%, 100% { opacity: 1; } 50% { opacity: .4; } }
        .statusbar a { color: var(--text-faint); transition: color .15s; }
        .statusbar a:hover { color: var(--cyan); }
        .statusbar a.active { color: var(--text); }

        /* -------- Hero (centered, in front of the 3D scene) -------- */
        .hero {
            position: relative; z-index: 2;
            flex: 1; min-height: 0;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 1.5rem 1.5rem 5rem;
            text-align: center;
        }
        .lockup-wrap { margin: 0 0 1.75rem; }
        .lockup-wrap img { filter: drop-shadow(0 0 24px rgba(0, 240, 255, .25)); }

        /* Staggered reveal animation — everything starts hidden + below
           its final position; each piece slides up + fades in on its
           own delay. animation-fill-mode: forwards holds the final state. */
        .stagger { opacity: 0; transform: translateY(12px); animation: revealUp .7s cubic-bezier(.2, .7, .2, 1) forwards; }
        @keyframes revealUp { to { opacity: 1; transform: translateY(0); } }
        .stagger.d0 { animation-delay: .0s; }
        .stagger.d1 { animation-delay: .12s; }
        .stagger.d2 { animation-delay: .24s; }
        .stagger.d3 { animation-delay: .36s; }
        .stagger.d4 { animation-delay: .48s; }
        .stagger.d5 { animation-delay: .60s; }
        .stagger.d6 { animation-delay: .72s; }

        .eyebrow {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .375rem .875rem;
            background: rgba(0, 240, 255, .08);
            border: 1px solid rgba(0, 240, 255, .25);
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
            font-size: clamp(2.25rem, 5.5vw, 4rem);
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -0.03em;
            margin: 0 0 1.25rem;
            max-width: 720px;
        }
        h1 .gradient {
            background: var(--primary-gradient);
            background-clip: text; -webkit-background-clip: text;
            color: transparent;
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
            line-height: 1.6;
            max-width: 540px;
            margin: 0 auto 2rem;
        }

        /* Form */
        .form {
            display: flex; gap: .5rem;
            width: 100%; max-width: 480px;
            padding: 5px;
            background: rgba(255, 255, 255, .03);
            border: 1px solid var(--hair);
            border-radius: 14px;
            position: relative;
            margin: 0 auto;
            transition: border-color .2s, box-shadow .2s, background .2s;
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
        .form:focus-within { background: rgba(255, 255, 255, .05); box-shadow: 0 0 24px rgba(0, 240, 255, .35); }
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

        /* -------- Footer -------- */
        footer.foot {
            position: absolute; bottom: 0; left: 0; right: 0; z-index: 3;
            padding: 1rem 1.5rem;
            text-align: center;
            font: 500 0.75rem/1 ui-monospace, monospace;
            color: var(--text-faint);
            letter-spacing: 0.06em;
            border-top: 1px solid var(--hair-soft);
            background: rgba(5, 8, 23, .3);
            backdrop-filter: blur(8px);
        }
        footer.foot a { color: var(--text-faint); transition: color .15s; }
        footer.foot a:hover, footer.foot a.active { color: var(--cyan); }
        footer.foot .sep { color: rgba(140, 180, 255, .15); margin: 0 .5rem; }

        @media (max-width: 720px) {
            .hero { padding: 1rem 1rem 6rem; }
            .form input { font-size: 16px; padding: .625rem .875rem; }
            .form button { padding: 0 1rem; font-size: 0.875rem; }
            .fallback-wireframe { width: 220px; height: 220px; }
        }
        @media (max-height: 620px) {
            html, body { height: auto; overflow: auto; }
            footer.foot { position: relative; }
            #scene, .fallback-wireframe { position: fixed; }
        }

        /* Reduced-motion: kill the staggered reveal + flatten Three.js
           animation in JS (handled below). The hero shows fully immediately. */
        @media (prefers-reduced-motion: reduce) {
            .stagger { opacity: 1 !important; transform: none !important; animation: none !important; }
            *, *::before, *::after { animation-duration: 0.001s !important; animation-iteration-count: 1 !important; transition-duration: 0.001s !important; }
            .fallback-wireframe { animation: none !important; }
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

    {{-- 3D scene canvas. Hidden until Three.js boots (transitions opacity in). --}}
    <canvas id="scene" aria-hidden="true"></canvas>

    {{-- Static SVG fallback: shown if WebGL is unavailable or JS is off.
         Hidden via #scene.ready ~ .fallback-wireframe once Three.js boots. --}}
    <div class="fallback-wireframe" aria-hidden="true">
        <svg viewBox="-110 -110 220 220" xmlns="http://www.w3.org/2000/svg">
            {{-- Icosahedron-ish wireframe; pre-projected so we don't ship a 3D lib for the fallback. --}}
            <polygon points="0,-100  87,-50  87,50  0,100  -87,50  -87,-50"/>
            <polygon points="0,-100  87,50  -87,50"/>
            <polygon points="87,-50  87,50  -87,-50"/>
            <polygon points="0,100  87,-50  -87,-50"/>
            <polygon points="-87,50  87,-50  0,-100"/>
            <polygon points="-87,-50  -87,50  87,-50"/>
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
        <div class="lockup-wrap stagger d0"><a href="/" aria-label="Ganvo"><x-brand-lockup size="lg" /></a></div>

        <div class="eyebrow stagger d1">{{ $cs['eyebrow'] ?? __('site.marketing.coming_soon.eyebrow') }}</div>

        <h1>
            <span class="stagger d2" style="display: inline-block">{{ $cs['headline_1'] ?? __('site.marketing.coming_soon.headline_1') }}</span>
            <br>
            <span class="stagger d3 gradient" style="display: inline-block">{{ $cs['headline_2'] ?? __('site.marketing.coming_soon.headline_2') }}</span>
        </h1>

        <p class="lead stagger d4">{{ $cs['lead'] ?? __('site.marketing.coming_soon.lead') }}</p>

        <div class="stagger d5" style="width: 100%; max-width: 480px;">
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
    </section>

    <footer class="foot">
        © {{ date('Y') }} GANVO
        <span class="sep">·</span>
        <a href="/preview/coming-soon-v1">← classic version</a>
    </footer>

    {{-- Cursor spotlight + clock + form handler (no module needed). --}}
    <script>
        const spotlight = document.getElementById('spotlight');
        let rafId = null, mx = 50, my = 50;
        document.addEventListener('mousemove', (e) => {
            mx = (e.clientX / window.innerWidth) * 100;
            my = (e.clientY / window.innerHeight) * 100;
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

    {{-- Three.js scene as ES module. Loaded from esm.sh which resolves
         peer deps for us. Total ~150KB gzipped, cached after first load.
         All scene logic + the prefers-reduced-motion check live here.
         If the import fails (offline, CSP, ad blocker), the static SVG
         fallback stays visible. --}}
    <script type="module">
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const canvas = document.getElementById('scene');

        // Quick WebGL feature detection. If unavailable, bail and leave the
        // SVG fallback visible.
        function hasWebGL() {
            try {
                const c = document.createElement('canvas');
                return !!(c.getContext('webgl2') || c.getContext('webgl'));
            } catch (e) { return false; }
        }
        if (! hasWebGL()) {
            console.info('[coming-soon-v2] WebGL unavailable — using SVG fallback');
        } else {
            try {
                const THREE = await import('https://esm.sh/three@0.160.0');

                // ---- Renderer ------------------------------------------------
                const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                renderer.setSize(window.innerWidth, window.innerHeight, false);
                renderer.setClearColor(0x000000, 0);

                // ---- Scene + camera -----------------------------------------
                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(50, window.innerWidth / window.innerHeight, 0.1, 100);
                camera.position.z = 10;

                // ---- Main icosahedron (cyan wireframe) ----------------------
                // EdgesGeometry collapses overlapping coplanar edges, so the
                // wireframe reads as actual edges rather than a noisy triangle mesh.
                const icoGeo = new THREE.IcosahedronGeometry(2.4, 0);
                const icoEdges = new THREE.EdgesGeometry(icoGeo);
                const icoMat = new THREE.LineBasicMaterial({
                    color: 0x00f0ff, transparent: true, opacity: 0.85,
                });
                const icosahedron = new THREE.LineSegments(icoEdges, icoMat);
                scene.add(icosahedron);

                // ---- Smaller dodecahedron behind it (magenta, lower opacity) -
                const dodGeo = new THREE.DodecahedronGeometry(3.6, 0);
                const dodEdges = new THREE.EdgesGeometry(dodGeo);
                const dodMat = new THREE.LineBasicMaterial({
                    color: 0xff2dd0, transparent: true, opacity: 0.35,
                });
                const dodecahedron = new THREE.LineSegments(dodEdges, dodMat);
                scene.add(dodecahedron);

                // ---- Particle dust around them ------------------------------
                // Random points distributed in a sphere shell. Additive
                // blending gives the dust a soft glow without a real
                // post-processing pass.
                const PARTICLE_COUNT = 320;
                const positions = new Float32Array(PARTICLE_COUNT * 3);
                for (let i = 0; i < PARTICLE_COUNT; i++) {
                    // Spherical shell, radius 4–6, random direction
                    const r = 4 + Math.random() * 2;
                    const theta = Math.random() * Math.PI * 2;
                    const phi = Math.acos(2 * Math.random() - 1);
                    positions[i * 3]     = r * Math.sin(phi) * Math.cos(theta);
                    positions[i * 3 + 1] = r * Math.sin(phi) * Math.sin(theta);
                    positions[i * 3 + 2] = r * Math.cos(phi);
                }
                const particleGeo = new THREE.BufferGeometry();
                particleGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
                const particleMat = new THREE.PointsMaterial({
                    color: 0xa8b6ff, size: 0.04,
                    transparent: true, opacity: 0.7,
                    blending: THREE.AdditiveBlending,
                    depthWrite: false,
                });
                const particles = new THREE.Points(particleGeo, particleMat);
                scene.add(particles);

                // ---- Mouse parallax — camera follows the cursor a little ----
                // Smoothed via lerp so the camera doesn't jitter on every event.
                const target = { x: 0, y: 0 };
                document.addEventListener('mousemove', (e) => {
                    target.x = (e.clientX / window.innerWidth - 0.5) * 1.2;
                    target.y = (e.clientY / window.innerHeight - 0.5) * 0.8;
                });

                // ---- Resize handling ---------------------------------------
                window.addEventListener('resize', () => {
                    camera.aspect = window.innerWidth / window.innerHeight;
                    camera.updateProjectionMatrix();
                    renderer.setSize(window.innerWidth, window.innerHeight, false);
                });

                // ---- Animation loop ----------------------------------------
                const clock = new THREE.Clock();
                function animate() {
                    requestAnimationFrame(animate);
                    const dt = clock.getDelta();

                    if (! reducedMotion) {
                        // Slow auto-rotation: ico rotates one way, dodec the other,
                        // particles drift slowly. dt-based so it's framerate-agnostic.
                        icosahedron.rotation.x += dt * 0.15;
                        icosahedron.rotation.y += dt * 0.25;
                        dodecahedron.rotation.x -= dt * 0.10;
                        dodecahedron.rotation.y -= dt * 0.18;
                        particles.rotation.y += dt * 0.06;

                        // Camera parallax — ease toward the mouse-driven target.
                        camera.position.x += (target.x - camera.position.x) * 0.05;
                        camera.position.y += (-target.y - camera.position.y) * 0.05;
                        camera.lookAt(0, 0, 0);
                    }

                    renderer.render(scene, camera);
                }
                animate();

                // Fade the canvas in once it's drawing — avoids a flash of
                // black or a single frame of static scene.
                requestAnimationFrame(() => canvas.classList.add('ready'));
            } catch (err) {
                console.warn('[coming-soon-v2] Three.js failed to load — using SVG fallback', err);
            }
        }
    </script>
</body>
</html>
