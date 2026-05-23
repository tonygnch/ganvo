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
            --cyan:      #00d4ff;        /* primary accent */
            --blue-mid:  #4a9eff;
            --blue-deep: #1a3a8a;
            --ice:       #cbe8ff;        /* highlights */
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

        /* -------- Background atmospherics (z-index: 0) - all blue now -------- */
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
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 1rem 1.5rem 5rem;
            text-align: center;
        }

        /* -------- Character canvas wrapper -------- */
        /* Lives above the lockup. Fixed size (in px) so the Three.js
           renderer has a stable viewport to draw into. Falls back to a
           pulsing CSS orb if WebGL is unavailable. */
        .character {
            width: 240px; height: 240px;
            margin: 0 0 .5rem;
            position: relative;
        }
        .character canvas { display: block; width: 100%; height: 100%; }
        /* Pulsing CSS-only fallback orb. Visible until Three.js takes over,
           or stays forever if WebGL is unavailable / the import fails. */
        .character::before {
            content: ""; position: absolute; inset: 25% 25% 25% 25%;
            border-radius: 50%;
            background: radial-gradient(circle, var(--cyan), var(--blue-deep) 70%, transparent 100%);
            opacity: .8;
            animation: orbPulse 2.4s ease-in-out infinite;
        }
        .character.ready::before { display: none; }
        @keyframes orbPulse {
            0%, 100% { transform: scale(1);    opacity: .8; }
            50%      { transform: scale(1.05); opacity: 1; }
        }
        @media (max-width: 720px) { .character { width: 180px; height: 180px; } }
        @media (max-height: 720px) { .character { width: 180px; height: 180px; } }
        @media (max-height: 620px) { .character { width: 140px; height: 140px; } }

        .lockup-wrap { margin: 0 0 1.5rem; }
        .lockup-wrap img { filter: drop-shadow(0 0 24px rgba(0, 212, 255, .25)); }

        /* Staggered reveal */
        .stagger { opacity: 0; transform: translateY(12px); animation: revealUp .7s cubic-bezier(.2, .7, .2, 1) forwards; }
        @keyframes revealUp { to { opacity: 1; transform: translateY(0); } }
        .stagger.d0 { animation-delay: .0s; }
        .stagger.d1 { animation-delay: .12s; }
        .stagger.d2 { animation-delay: .24s; }
        .stagger.d3 { animation-delay: .36s; }
        .stagger.d4 { animation-delay: .48s; }
        .stagger.d5 { animation-delay: .60s; }
        .stagger.d6 { animation-delay: .72s; }
        .stagger.d7 { animation-delay: .84s; }

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
            background: rgba(255, 255, 255, .03);
            border: 1px solid var(--hair);
            border-radius: 14px;
            position: relative; margin: 0 auto;
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
            .hero { padding: 0.5rem 1rem 6rem; }
            .form input { font-size: 16px; padding: .625rem .875rem; }
            .form button { padding: 0 1rem; font-size: 0.875rem; }
        }
        @media (max-height: 620px) {
            html, body { height: auto; overflow: auto; }
            footer.foot { position: relative; }
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
        {{-- Bo — the Ganvo droid. Fixed-size container; canvas inside fills it.
             The ::before pseudo on .character renders a pulsing CSS orb that
             stays visible if WebGL/Three.js doesn't boot. --}}
        <div class="character stagger d0" id="character" title="Hi, I'm Bo">
            <canvas id="characterCanvas" aria-hidden="true"></canvas>
        </div>

        <div class="lockup-wrap stagger d1"><a href="/" aria-label="Ganvo"><x-brand-lockup size="md" /></a></div>

        <div class="eyebrow stagger d2">{{ $cs['eyebrow'] ?? __('site.marketing.coming_soon.eyebrow') }}</div>

        <h1>
            <span class="stagger d3" style="display: inline-block">{{ $cs['headline_1'] ?? __('site.marketing.coming_soon.headline_1') }}</span>
            <br>
            <span class="stagger d4 gradient" style="display: inline-block">{{ $cs['headline_2'] ?? __('site.marketing.coming_soon.headline_2') }}</span>
        </h1>

        <p class="lead stagger d5">{{ $cs['lead'] ?? __('site.marketing.coming_soon.lead') }}</p>

        <div class="stagger d6" style="width: 100%; max-width: 480px;">
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

        <p class="form-helper stagger d7">{{ $cs['helper_text'] ?? __('site.marketing.coming_soon.helper') }}</p>
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
        // mouseTarget is exported globally so the Three.js module can read it.
        window.__cursor = { x: 0, y: 0 };
        document.addEventListener('mousemove', (e) => {
            mx = (e.clientX / window.innerWidth) * 100;
            my = (e.clientY / window.innerHeight) * 100;
            // NDC range, roughly. The Three.js loop will scale this to world coords.
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

    {{-- Bo, the Ganvo droid — built from Three.js primitives.
         Spherical body with soft cyan glow, oversized white eyes whose
         pupils track the cursor, a glowing antenna, and a slowly-rotating
         orbital ring. All animations dt-based so framerate doesn't change
         the speed. WebGL/JS missing = pulsing CSS orb stays visible. --}}
    <script type="module">
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const wrapper = document.getElementById('character');
        const canvas = document.getElementById('characterCanvas');

        function hasWebGL() {
            try {
                const c = document.createElement('canvas');
                return !!(c.getContext('webgl2') || c.getContext('webgl'));
            } catch (e) { return false; }
        }
        if (! hasWebGL()) {
            console.info('[bo] WebGL unavailable — CSS orb fallback stays visible');
        } else {
            try {
                const THREE = await import('https://esm.sh/three@0.160.0');

                // ---- Renderer (sized to the wrapper div, not the viewport) --
                const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                const sizeRenderer = () => {
                    const w = wrapper.clientWidth, h = wrapper.clientHeight;
                    renderer.setSize(w, h, false);
                    camera.aspect = w / h;
                    camera.updateProjectionMatrix();
                };

                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(40, 1, 0.1, 100);
                camera.position.set(0, 0, 5.5);

                // ---- Lighting -----------------------------------------------
                // Three lights: ambient for base fill, a cool key light coming
                // from front-right, and a cyan rim from behind to halo the body.
                scene.add(new THREE.AmbientLight(0xffffff, 0.35));
                const key = new THREE.DirectionalLight(0xcbe8ff, 1.2);
                key.position.set(2, 3, 4);
                scene.add(key);
                const rim = new THREE.DirectionalLight(0x00d4ff, 0.9);
                rim.position.set(-2, -1, -3);
                scene.add(rim);

                // ---- Body (root group; everything else attaches to it) ------
                // Group lets us bob + breathe the whole character at once.
                const body = new THREE.Group();
                scene.add(body);

                // Main body sphere — slightly metallic blue
                const bodyMesh = new THREE.Mesh(
                    new THREE.SphereGeometry(1, 64, 64),
                    new THREE.MeshStandardMaterial({
                        color: 0x1a3a8a, metalness: 0.5, roughness: 0.35,
                        emissive: 0x0a1430, emissiveIntensity: 0.5,
                    })
                );
                body.add(bodyMesh);

                // Outer glow shell — backside-rendered larger sphere with
                // a translucent cyan material. Cheap halo without a real shader.
                const glow = new THREE.Mesh(
                    new THREE.SphereGeometry(1.18, 32, 32),
                    new THREE.MeshBasicMaterial({
                        color: 0x00d4ff, transparent: true, opacity: 0.12,
                        side: THREE.BackSide, depthWrite: false,
                    })
                );
                body.add(glow);

                // ---- Eyes (two groups, each containing a sclera + pupil) ----
                // The whole eye GROUP lookAt()s the cursor target each frame —
                // since the pupil is offset along the group's +Z axis, the
                // pupil visually slides around the sclera surface to face
                // the cursor. This is what gives Bo the "looking at you" feel.
                const makeEye = () => {
                    const eye = new THREE.Group();
                    const sclera = new THREE.Mesh(
                        new THREE.SphereGeometry(0.26, 32, 32),
                        new THREE.MeshStandardMaterial({
                            color: 0xeaf4ff, roughness: 0.4, metalness: 0.1,
                            emissive: 0xcbe8ff, emissiveIntensity: 0.25,
                        })
                    );
                    const pupil = new THREE.Mesh(
                        new THREE.SphereGeometry(0.11, 16, 16),
                        new THREE.MeshStandardMaterial({
                            color: 0x020a18, emissive: 0x00d4ff, emissiveIntensity: 0.8,
                        })
                    );
                    pupil.position.z = 0.20;     // sits on the sclera's surface
                    eye.add(sclera, pupil);
                    return eye;
                };
                const leftEye  = makeEye();  leftEye.position.set(-0.36, 0.12, 0.85);
                const rightEye = makeEye();  rightEye.position.set(0.36, 0.12, 0.85);
                body.add(leftEye, rightEye);

                // ---- Antenna — short cylinder + glowing tip -----------------
                const antennaStem = new THREE.Mesh(
                    new THREE.CylinderGeometry(0.025, 0.025, 0.55, 8),
                    new THREE.MeshStandardMaterial({ color: 0x4a6fa5, roughness: 0.5, metalness: 0.7 })
                );
                antennaStem.position.y = 1.25;
                body.add(antennaStem);
                const antennaTip = new THREE.Mesh(
                    new THREE.SphereGeometry(0.10, 16, 16),
                    new THREE.MeshStandardMaterial({
                        color: 0x00d4ff, emissive: 0x00d4ff, emissiveIntensity: 1.5,
                    })
                );
                antennaTip.position.y = 1.55;
                body.add(antennaTip);

                // ---- Orbital ring (horizontal torus around the body) --------
                // Rendered as a wireframe so it reads as a holographic line
                // rather than a solid donut. Slowly counter-rotates.
                const ring = new THREE.Mesh(
                    new THREE.TorusGeometry(1.55, 0.015, 8, 96),
                    new THREE.MeshBasicMaterial({
                        color: 0x00d4ff, transparent: true, opacity: 0.6,
                    })
                );
                ring.rotation.x = Math.PI / 2.2;
                scene.add(ring);

                // ---- Tiny particle dust around Bo ---------------------------
                const PARTICLES = 80;
                const positions = new Float32Array(PARTICLES * 3);
                for (let i = 0; i < PARTICLES; i++) {
                    const r = 1.8 + Math.random() * 1.2;
                    const theta = Math.random() * Math.PI * 2;
                    const phi = Math.acos(2 * Math.random() - 1);
                    positions[i * 3]     = r * Math.sin(phi) * Math.cos(theta);
                    positions[i * 3 + 1] = r * Math.sin(phi) * Math.sin(theta);
                    positions[i * 3 + 2] = r * Math.cos(phi);
                }
                const pGeo = new THREE.BufferGeometry();
                pGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
                const particles = new THREE.Points(pGeo, new THREE.PointsMaterial({
                    color: 0xcbe8ff, size: 0.035, transparent: true, opacity: 0.6,
                    blending: THREE.AdditiveBlending, depthWrite: false,
                }));
                scene.add(particles);

                // ---- Cursor target in world space ---------------------------
                // window.__cursor is set in the page-level script above as NDC.
                // Project it to a point at z = 3 in front of the camera so the
                // eyes have something stable to lookAt().
                const cursorWorld = new THREE.Vector3();
                function updateCursorTarget() {
                    cursorWorld.set(
                        window.__cursor.x * 3,   // wider X mapping → eyes turn further
                        window.__cursor.y * 2,
                        3
                    );
                }

                // ---- Resize handling ----------------------------------------
                sizeRenderer();
                window.addEventListener('resize', sizeRenderer);
                // Observe wrapper too — small viewports change the size via CSS.
                if (typeof ResizeObserver !== 'undefined') {
                    new ResizeObserver(sizeRenderer).observe(wrapper);
                }

                // ---- Animation loop -----------------------------------------
                const clock = new THREE.Clock();
                function animate() {
                    requestAnimationFrame(animate);
                    const t  = clock.getElapsedTime();
                    const dt = clock.getDelta();

                    if (! reducedMotion) {
                        // Bob: subtle vertical sine, slow breathing scale on body
                        body.position.y = Math.sin(t * 1.4) * 0.10;
                        const breath = 1 + Math.sin(t * 1.8) * 0.015;
                        bodyMesh.scale.setScalar(breath);

                        // Slow yaw so Bo "looks around"
                        body.rotation.y = Math.sin(t * 0.4) * 0.18;
                        body.rotation.z = Math.sin(t * 0.7) * 0.04;

                        // Antenna sway
                        antennaStem.rotation.z = Math.sin(t * 2) * 0.08;
                        antennaTip.position.x = Math.sin(t * 2) * 0.04;

                        // Ring spin
                        ring.rotation.z += dt * 0.4;

                        // Particle drift
                        particles.rotation.y += dt * 0.08;

                        // Eye tracking — pupil follows cursor
                        updateCursorTarget();
                        leftEye.lookAt(cursorWorld);
                        rightEye.lookAt(cursorWorld);
                    }

                    renderer.render(scene, camera);
                }
                animate();

                // Hide the CSS-orb fallback now that WebGL is drawing.
                wrapper.classList.add('ready');
            } catch (err) {
                console.warn('[bo] Three.js failed to load — CSS orb stays', err);
            }
        }
    </script>
</body>
</html>
