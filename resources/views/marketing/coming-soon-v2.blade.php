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

    {{-- Bo — RobotExpressive model by Tomás Laulhé (CC0/MIT, shipped
         with Three.js examples). Loaded via GLTFLoader; animations
         driven by AnimationMixer using the model's named clips
         (Jump, ThumbsUp, Wave, Yes, Idle, Dance, etc.).

         State machine: 'hop-in' → 'thumbs-up' → 'idle' → periodic
         gesture (Wave / Yes / ThumbsUp / Dance) → back to 'idle'.

         During hop-in, the model's world Y is lerped from above the
         viewport down to home + the "Jump" clip plays so the character
         looks like it leapt onto the screen. Fallbacks (no WebGL,
         GLTF load failure, prefers-reduced-motion) leave the inline
         SVG bo-fallback visible. --}}
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
                // Pin the three version + load GLTFLoader from the same
                // build to avoid two different THREE namespaces (Three.js
                // throws hard if mixed).
                const THREE_VERSION = '0.160.0';
                const THREE = await import(`https://esm.sh/three@${THREE_VERSION}`);
                const { GLTFLoader } = await import(
                    `https://esm.sh/three@${THREE_VERSION}/examples/jsm/loaders/GLTFLoader.js`
                );

                // ---- Renderer -------------------------------------------
                const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                renderer.setClearColor(0x000000, 0);
                renderer.outputColorSpace = THREE.SRGBColorSpace;
                renderer.toneMapping = THREE.ACESFilmicToneMapping;
                renderer.toneMappingExposure = 1.0;

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

                // ---- Lighting -------------------------------------------
                // Three-point lighting tuned for a stylized model: warm-ish
                // key, cyan rim to match the brand palette, broad ambient.
                scene.add(new THREE.HemisphereLight(0xcbe8ff, 0x0a1430, 1.4));
                const key = new THREE.DirectionalLight(0xffffff, 1.8);
                key.position.set(3, 5, 4);
                scene.add(key);
                const rim = new THREE.DirectionalLight(0x00d4ff, 1.2);
                rim.position.set(-4, -2, -3);
                scene.add(rim);

                // ---- DOM → world position projection --------------------
                // Bo's home Y in world space is computed from the brand
                // lockup's screen rect, then offset up so the model lands
                // above the wordmark on any viewport.
                let homePos = new THREE.Vector3(0, 1.5, 0);
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
                function recomputeHome() {
                    if (! lockupEl) return;
                    const r = lockupEl.getBoundingClientRect();
                    const cx = r.left + r.width / 2;
                    // Place Bo's pivot ~28% of viewport height above the
                    // lockup's top edge so his upper body is centered there.
                    const aboveY = r.top - window.innerHeight * 0.28;
                    homePos = screenToWorld(cx, aboveY);
                }
                recomputeHome();
                window.addEventListener('resize', recomputeHome);

                // ---- Load the GLB ---------------------------------------
                const loader = new GLTFLoader();
                const gltf = await new Promise((resolve, reject) => {
                    loader.load('/models/RobotExpressive.glb', resolve, undefined, reject);
                });

                const model = gltf.scene;
                // Scale: model is ~2 units tall in its own coords; bump up
                // a bit so it reads well in the camera frame.
                model.scale.setScalar(0.55);
                // Default model faces +Z (away from camera at z=10 looking
                // at origin); flip 180° to face the viewer.
                model.rotation.y = Math.PI;
                scene.add(model);

                // ---- Animation mixer + clip catalog ---------------------
                // RobotExpressive ships with these clips (by name): Idle,
                // Walking, Running, Dance, Death, Sitting, Standing, Jump,
                // No, Punch, ThumbsUp, Wave, Yes. We keep references to a
                // few so we can fade between them.
                const mixer = new THREE.AnimationMixer(model);
                const actions = {};
                for (const clip of gltf.animations) {
                    actions[clip.name] = mixer.clipAction(clip);
                }

                let currentAction = null;
                function fadeTo(name, duration = 0.35, loop = true) {
                    const next = actions[name];
                    if (! next) {
                        console.warn(`[bo] no clip named '${name}' (have: ${Object.keys(actions).join(', ')})`);
                        return null;
                    }
                    next.reset();
                    if (! loop) {
                        next.setLoop(THREE.LoopOnce, 1);
                        next.clampWhenFinished = true;
                    } else {
                        next.setLoop(THREE.LoopRepeat, Infinity);
                        next.clampWhenFinished = false;
                    }
                    next.enabled = true;
                    next.setEffectiveTimeScale(1);
                    next.setEffectiveWeight(1);
                    next.fadeIn(duration).play();
                    if (currentAction && currentAction !== next) {
                        currentAction.fadeOut(duration);
                    }
                    currentAction = next;
                    return next;
                }

                // ---- State machine ---------------------------------------
                // Drives both the animation clip AND any world-position
                // tween that needs to run alongside it (hop entrance).
                let state = 'hop-in';
                let stateStart = 0;
                const HOP_DURATION = 1.0;
                const THUMBS_HOLD = 1.8;
                const IDLE_BEFORE_GESTURE = 6.0;
                const gestureBag = ['Wave', 'Yes', 'ThumbsUp', 'Dance'];
                let gestureIndex = 0;

                // Kick off the entrance: start the Jump clip immediately so
                // the model is in mid-leap pose during the fall-in.
                fadeTo('Jump', 0.001, false);

                // Ease for the hop fall — fast initial drop + small bounce.
                function hopOffset(p) {
                    if (p < 0.7) {
                        const t = p / 0.7;
                        return 6 * Math.pow(1 - t, 2);     // gravity-ish ease-in to 0
                    } else {
                        const t = (p - 0.7) / 0.3;
                        return Math.sin(t * Math.PI) * -0.25; // bounce below + back up
                    }
                }

                // ---- Animation loop --------------------------------------
                const clock = new THREE.Clock();
                function animate() {
                    requestAnimationFrame(animate);
                    const t = clock.getElapsedTime();
                    const dt = Math.min(clock.getDelta(), 0.05);
                    mixer.update(dt);

                    if (reducedMotion) {
                        model.position.copy(homePos);
                        renderer.render(scene, camera);
                        return;
                    }

                    const st = t - stateStart;

                    if (state === 'hop-in') {
                        const p = Math.min(1, st / HOP_DURATION);
                        model.position.set(homePos.x, homePos.y + hopOffset(p), homePos.z);
                        if (p >= 1) {
                            state = 'thumbs-up';
                            stateStart = t;
                            fadeTo('ThumbsUp', 0.3, false);
                        }
                    }
                    else if (state === 'thumbs-up') {
                        // Idle bob during the hold
                        model.position.set(homePos.x, homePos.y + Math.sin(t * 1.4) * 0.05, homePos.z);
                        if (st > THUMBS_HOLD) {
                            state = 'idle';
                            stateStart = t;
                            fadeTo('Idle', 0.4);
                        }
                    }
                    else if (state === 'idle') {
                        // Bob in place while idling. The Idle clip has its
                        // own subtle motion; the bob is a world-position
                        // offset on top of that for a "hovering" feel.
                        model.position.set(homePos.x, homePos.y + Math.sin(t * 1.2) * 0.08, homePos.z);
                        if (st > IDLE_BEFORE_GESTURE) {
                            state = 'gesture';
                            stateStart = t;
                            const next = gestureBag[gestureIndex++ % gestureBag.length];
                            fadeTo(next, 0.3, false);
                        }
                    }
                    else if (state === 'gesture') {
                        model.position.set(homePos.x, homePos.y + Math.sin(t * 1.4) * 0.05, homePos.z);
                        // Gesture clips run ~1.5-3s; return to idle after the
                        // current action's clip duration plus a small buffer.
                        const clipDur = currentAction?.getClip()?.duration ?? 2;
                        if (st > clipDur - 0.15) {
                            state = 'idle';
                            stateStart = t;
                            fadeTo('Idle', 0.4);
                        }
                    }

                    // Gentle yaw toward cursor so Bo feels engaged with the
                    // viewer (the model has no individually-controllable
                    // head bone via name lookup that's stable across
                    // exports, so we yaw the whole body subtly instead).
                    const yawTarget = window.__cursor.x * 0.18;
                    model.rotation.y += (Math.PI + yawTarget - model.rotation.y) * 0.05;

                    renderer.render(scene, camera);
                }
                animate();

                // Reveal canvas once we're actually drawing
                requestAnimationFrame(() => canvas.classList.add('ready'));
            } catch (err) {
                console.warn('[bo] Three.js / GLB load failed — SVG fallback stays', err);
            }
        }
    </script>
</body>
</html>
