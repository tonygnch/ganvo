/*
 | HERO G — the Ganvo mark as a live 3D object, in its own brand colour.
 |
 | Geometry traced pixel-exact from the brand icon (256×256): one continuous
 | arch — outer radius 129 / inner 76 around centre (129,129), left leg
 | x∈[0,51] to the bottom, the arch's right end cut flat at y≈102 — plus the
 | crossbar (x∈[97,256], y∈[130,180]) and the right foot column (x∈[205,256],
 | y∈[181,256]). Three solid extruded pieces, matte-satin brand blue #2072fa,
 | floating in a void with drifting dust.
 |
 | Interaction: BLEACH + SPLASH. A shader patch mixes the surface toward
 | white in a soft radius around the cursor — and the colour that leaves the
 | letter SPLASHES out as paint droplets: wiping the cursor across the mark
 | sprays brand-blue drops in the stroke's direction (rate scales with
 | cursor speed), which arc under gravity and fade. A gentle damped tilt
 | keeps the piece physical. Touch: a tap on the mark bursts a radial splash.
 |
 | Degradation: reduced-motion never boots this module; WebGL failure returns
 | null and the poster <img> stays; a lost GL context brings the poster back
 | (and the PMREM env is regenerated on restore — it lives in a render target
 | the browser cannot restore). DPR capped per size class, 30fps cap on
 | phones, and the rAF loop fully STOPS when the tab hides or the hero
 | scrolls away (kick() restarts it from the observer callbacks).
 */

import {
    AdditiveBlending,
    BufferGeometry,
    CanvasTexture,
    CircleGeometry,
    Color,
    DirectionalLight,
    DynamicDrawUsage,
    ExtrudeGeometry,
    Float32BufferAttribute,
    FogExp2,
    Group,
    InstancedMesh,
    MathUtils,
    Matrix4,
    Mesh,
    MeshBasicMaterial,
    MeshStandardMaterial,
    PerspectiveCamera,
    PlaneGeometry,
    PMREMGenerator,
    Points,
    PointsMaterial,
    Raycaster,
    Scene,
    Shape,
    Sprite,
    SpriteMaterial,
    Vector2,
    Vector3,
    WebGLRenderer,
} from 'three';

const AZURE = 0x4d8dff;
const ICE = 0x7dd3fc;
const VOID = 0x020409;
const BRAND = 0x2072fa; // sampled from the icon itself

function glowTexture(size = 256) {
    const c = document.createElement('canvas');
    c.width = c.height = size;
    const ctx = c.getContext('2d');
    const g = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
    g.addColorStop(0, 'rgba(255,255,255,0.85)');
    g.addColorStop(0.25, 'rgba(255,255,255,0.28)');
    g.addColorStop(0.6, 'rgba(255,255,255,0.06)');
    g.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, size, size);
    return new CanvasTexture(c);
}

export default function initHeroG(host) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return null;

    let renderer;
    try {
        renderer = new WebGLRenderer({
            antialias: true,
            alpha: false,
            powerPreference: 'high-performance',
            failIfMajorPerformanceCaveat: true,
        });
    } catch {
        return null; // no real GPU — the poster stays
    }
    renderer.setClearColor(VOID, 1);
    renderer.domElement.setAttribute('aria-hidden', 'true');
    host.appendChild(renderer.domElement);

    const setLive = (on) => host.classList.toggle('is-live', on);
    renderer.domElement.addEventListener('webglcontextlost', (e) => { e.preventDefault(); setLive(false); });
    renderer.domElement.addEventListener('webglcontextrestored', () => {
        // the PMREM env lives in a render target — a restored context comes
        // back without it, and dark unlit material would fade in. Regenerate.
        const pg = new PMREMGenerator(renderer);
        scene.environment = pg.fromScene(envScene, 0.3).texture;
        pg.dispose();
        setLive(true);
    });

    let small = (host.clientWidth || window.innerWidth) < 800;
    const scene = new Scene();
    scene.fog = new FogExp2(VOID, 0.014); // gives the dust depth in the void
    const camera = new PerspectiveCamera(50, 1, 0.1, 120);

    /* ── soft studio environment: a gentle satin sheen on the mark ── */
    const envScene = new Scene();
    envScene.background = new Color(0x010208);
    const strip = (hex, intensity, w, h, pos) => {
        const m = new MeshBasicMaterial({ color: new Color(hex).multiplyScalar(intensity) });
        const q = new Mesh(new PlaneGeometry(w, h), m);
        q.position.set(pos[0], pos[1], pos[2]);
        q.lookAt(0, 0, 0);
        envScene.add(q);
    };
    strip(0xffffff, 5, 4, 10, [-7, 6, 4]);
    strip(0xcfe0ff, 6, 8, 2.5, [6, 7, -3]);
    strip(0x9db8e8, 3, 7, 1.5, [1, -5, 5]);
    const pmrem = new PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(envScene, 0.3).texture;
    pmrem.dispose();

    const glowTex = glowTexture();
    const key = new DirectionalLight(0xeaf2ff, 1.1);
    key.position.set(-5, 7, 7);
    scene.add(key);
    const fill = new DirectionalLight(0x4d8dff, 0.35);
    fill.position.set(6, -3, 5);
    scene.add(fill);

    /* ════════ the G — three solid pieces in the brand blue ════════ */
    const S = 6.6 / 256;      // icon px → world units (G is 6.6 tall)
    const GY = 3.45;          // world height of the G's centre
    const gGroup = new Group();
    gGroup.position.set(0, GY, 0);
    scene.add(gGroup);

    // icon px → centred world coords (icon y grows down, world y grows up)
    const wx = (px) => (px - 128) * S;
    const wy = (py) => (128 - py) * S;
    const CX = wx(129), CY = wy(129);          // arch centre
    const R_OUT = 129 * S, R_IN = 76 * S;      // arch radii
    const CUT_Y = wy(102);                     // flat right-end cut height
    const aCutOut = Math.asin((CUT_Y - CY) / R_OUT);
    const aCutIn = Math.asin((CUT_Y - CY) / R_IN);

    /* ── the bleach shader patch ──
       One shared material; the fragment stage mixes the albedo toward white
       (plus a whisper of emissive lift) in a soft radius around uPointer.
       Distance is measured in WORLD space so the falloff rides the pointer
       exactly, whatever the group's float/tilt is doing. */
    const uPointer = { value: new Vector3(0, GY, 99) };
    const uBleach = { value: 0 };
    const uRadius = { value: 1.7 };
    // loading vessel: while uVessel is 1, everything above the fill line
    // (uFillY, world) renders as the dark empty glyph — the 3D mark IS the
    // loading bar, filling with brand blue as setProgress() advances
    const uFillY = { value: -99 };
    const uVessel = { value: 1 };
    const logoMat = new MeshStandardMaterial({
        color: BRAND,
        roughness: 0.38,
        metalness: 0.08,
        envMapIntensity: 0.9,
    });
    logoMat.onBeforeCompile = (shader) => {
        shader.uniforms.uPointer = uPointer;
        shader.uniforms.uBleach = uBleach;
        shader.uniforms.uRadius = uRadius;
        shader.uniforms.uFillY = uFillY;
        shader.uniforms.uVessel = uVessel;
        shader.vertexShader = shader.vertexShader
            .replace('#include <common>', '#include <common>\nvarying vec3 vBleachPos;')
            .replace('#include <project_vertex>', '#include <project_vertex>\nvBleachPos = (modelMatrix * vec4(transformed, 1.0)).xyz;');
        shader.fragmentShader = shader.fragmentShader
            .replace('#include <common>', '#include <common>\nvarying vec3 vBleachPos;\nuniform vec3 uPointer;\nuniform float uBleach;\nuniform float uRadius;\nuniform float uFillY;\nuniform float uVessel;')
            .replace('#include <color_fragment>', `#include <color_fragment>
                float bleachD = distance(vBleachPos.xy, uPointer.xy);
                float bleach = uBleach * smoothstep(uRadius, uRadius * 0.2, bleachD);
                diffuseColor.rgb = mix(diffuseColor.rgb, vec3(0.55, 0.76, 1.0), min(bleach, 1.0));
                float vesselAbove = smoothstep(uFillY - 0.02, uFillY + 0.02, vBleachPos.y) * uVessel;
                diffuseColor.rgb = mix(diffuseColor.rgb, vec3(0.030, 0.048, 0.11), vesselAbove);`)
            .replace('#include <emissivemap_fragment>', `#include <emissivemap_fragment>
                totalEmissiveRadiance += vec3(0.14, 0.32, 0.68) * min(bleach, 1.0);
                float fillLine = max(1.0 - abs(vBleachPos.y - uFillY) / 0.07, 0.0);
                totalEmissiveRadiance += vec3(0.10, 0.28, 0.85) * fillLine * uVessel;`);
    };

    const gMeshes = [];
    const addPiece = (shape) => {
        const geo = new ExtrudeGeometry(shape, { depth: 1.5, bevelEnabled: true, bevelThickness: 0.05, bevelSize: 0.05, bevelSegments: 2, curveSegments: 24 });
        geo.translate(0, 0, -0.75);
        const mesh = new Mesh(geo, logoMat);
        gGroup.add(mesh);
        gMeshes.push(mesh);
    };

    {   // arch + left leg, one continuous piece
        const s = new Shape();
        s.moveTo(wx(0), wy(256));
        s.lineTo(wx(0), wy(129));                            // leg outer edge
        s.absarc(CX, CY, R_OUT, Math.PI, aCutOut, true);     // over the crown
        s.lineTo(CX + R_IN * Math.cos(aCutIn), CY + R_IN * Math.sin(aCutIn)); // flat cut
        s.absarc(CX, CY, R_IN, aCutIn, Math.PI, false);      // inner arc back
        s.lineTo(wx(51), wy(256));                           // leg inner edge
        s.closePath();
        addPiece(s);
    }
    {   // crossbar
        const s = new Shape();
        s.moveTo(wx(97), wy(180));
        s.lineTo(wx(97), wy(130));
        s.lineTo(wx(256), wy(130));
        s.lineTo(wx(256), wy(180));
        s.closePath();
        addPiece(s);
    }
    {   // right foot
        const s = new Shape();
        s.moveTo(wx(205), wy(256));
        s.lineTo(wx(205), wy(181));
        s.lineTo(wx(256), wy(181));
        s.lineTo(wx(256), wy(256));
        s.closePath();
        addPiece(s);
    }

    // the faintest halo so the mark separates from the void
    const halo = new Sprite(new SpriteMaterial({
        map: glowTex, color: AZURE, transparent: true, opacity: 0.15,
        blending: AdditiveBlending, depthWrite: false,
    }));
    halo.position.set(0, GY, -3.5);
    halo.scale.set(16, 12, 1);
    scene.add(halo);

    // faint nebulae so the void has depth without stealing focus
    const nebulae = [];
    for (const n of [
        { x: 6, y: 7, z: -30, sx: 46, sy: 24, o: 0.05, c: AZURE },
        { x: -9, y: 2, z: -36, sx: 40, sy: 22, o: 0.035, c: AZURE },
    ]) {
        const s = new Sprite(new SpriteMaterial({
            map: glowTex, color: n.c, transparent: true, opacity: n.o,
            blending: AdditiveBlending, depthWrite: false,
        }));
        s.position.set(n.x, n.y, n.z);
        s.scale.set(n.sx, n.sy, 1);
        scene.add(s);
        nebulae.push({ s, o: n.o, phase: Math.random() * Math.PI * 2 });
    }

    /* ── drifting dust ── */
    const nDust = small ? 70 : 120;
    const dustPos = new Float32Array(nDust * 3);
    for (let i = 0; i < nDust; i++) {
        dustPos[i * 3] = (Math.random() - 0.5) * 30;
        dustPos[i * 3 + 1] = Math.random() * 12;
        dustPos[i * 3 + 2] = (Math.random() - 0.5) * 18;
    }
    const dustGeo = new BufferGeometry();
    dustGeo.setAttribute('position', new Float32BufferAttribute(dustPos, 3));
    const dust = new Points(dustGeo, new PointsMaterial({
        color: 0x9db8e8, size: 0.045, transparent: true, opacity: 0.3,
        blending: AdditiveBlending, depthWrite: false, sizeAttenuation: true,
    }));
    scene.add(dust);
    // per-mote rise speed — the drift is slow but unmistakable
    const dustSpeed = new Float32Array(nDust);
    for (let i = 0; i < nDust; i++) dustSpeed[i] = 0.18 + Math.random() * 0.42;

    /* ── paint droplets: the colour that splashes off the letter ──
       An InstancedMesh of small camera-facing circles — deterministic sizing
       on every GPU (gl_PointSize caps vary wildly), still one draw call.
       Droplets live in WORLD space — once the paint has left the letter it
       no longer rides the group's tilt. Fade = shrink + darken toward the
       void (per-instance alpha isn't a thing; on this backdrop it reads
       identically). */
    const MAX_P = 420;
    const pVel = new Float32Array(MAX_P * 3);
    const pPosArr = new Float32Array(MAX_P * 3);
    const pSize = new Float32Array(MAX_P);
    const pTtl = new Float32Array(MAX_P);       // seconds remaining
    const pMax = new Float32Array(MAX_P);       // initial ttl
    const pCol = []; for (let i = 0; i < MAX_P; i++) pCol.push(new Color());
    let pNext = 0;
    const drops = new InstancedMesh(
        new CircleGeometry(1, 12),
        new MeshBasicMaterial({ transparent: true, opacity: 0.95, depthWrite: false }),
        MAX_P,
    );
    drops.instanceMatrix.setUsage(DynamicDrawUsage);
    drops.frustumCulled = false;
    const _m4 = new Matrix4();
    const _zero = new Matrix4().makeScale(0, 0, 0);
    for (let i = 0; i < MAX_P; i++) { drops.setMatrixAt(i, _zero); drops.setColorAt(i, new Color(BRAND)); }
    scene.add(drops);

    const _brandCol = new Color(BRAND);
    const _iceCol = new Color(ICE);
    const _voidCol = new Color(VOID);
    const _fadeCol = new Color();
    const spawnDrop = (x, y, z, vx, vy, vz) => {
        const i = pNext;
        pNext = (pNext + 1) % MAX_P;
        pPosArr[i * 3] = x; pPosArr[i * 3 + 1] = y; pPosArr[i * 3 + 2] = z;
        pVel[i * 3] = vx; pVel[i * 3 + 1] = vy; pVel[i * 3 + 2] = vz;
        pCol[i].copy(_brandCol).lerp(_iceCol, Math.random() * 0.35);
        // mostly fine spray with the occasional fat blob
        pSize[i] = Math.random() < 0.18 ? 0.14 + Math.random() * 0.09 : 0.05 + Math.random() * 0.07;
        pMax[i] = pTtl[i] = 0.9 + Math.random() * 0.7;
    };

    // a radial burst (touch taps / hard crossings)
    const burst = (p, n, power) => {
        for (let i = 0; i < n; i++) {
            const a = Math.random() * Math.PI * 2;
            const r = (0.5 + Math.random()) * power;
            spawnDrop(
                p.x, p.y, p.z + 0.2,
                Math.cos(a) * r,
                Math.sin(a) * r * 0.8 + 0.6,
                0.4 + Math.random() * 1.2,
            );
        }
    };

    /* ── layout: G right-of-centre on wide screens, centred on small ──
       The camera itself is driven per-frame: it starts on the INTRO pose
       (G huge, front and centre, while the loader lifts) and flies to the
       final pose once begin() fires — layout() only maintains the targets. */
    let camX = 0;
    let camYF = GY;
    let camZF = 13.8;
    let baseScale = 1;
    let introT = 0;   // 0 = big front-and-centre, 1 = resting pose
    let begun = false;
    // intro distance: the whole G fits (with margin) on ANY aspect ratio —
    // portrait phones need the horizontal fit, not the vertical one
    const introZ = () => {
        const w = host.clientWidth || 1;
        const h = host.clientHeight || 1;
        const t = Math.tan(MathUtils.degToRad(25));
        const half = 6.6 * baseScale * 0.58; // half-size + breathing margin
        return Math.max(half / t, half / (t * (w / h))) + 0.3;
    };
    const layout = () => {
        small = (host.clientWidth || window.innerWidth) < 800;
        const wide = (host.clientWidth || window.innerWidth) >= 1024;
        // shifting the camera left pushes the G (at x=0) right of centre,
        // clearing the left-aligned headline without moving the mark itself
        camX = wide ? -4.9 : 0;
        baseScale = wide ? 1 : (small ? 0.44 : 0.6);
        gGroup.scale.setScalar(baseScale);
        // narrow layouts centre the headline, so the mark rests ABOVE it
        camYF = wide ? GY : GY - 3.5;
        camZF = wide ? 16.4 : 15.5;
        if (introT === 0) {
            camera.position.set(0, GY, introZ());
            camera.lookAt(0, GY, 0);
        }
    };

    /* ── pointer: bleach follows the cursor; the mark leans gently ── */
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const pointerWorld = new Vector3(0, GY, 99); // parked far until first move
    const pointerNdc = new Vector2();
    let pointerSeen = false;
    let pulse = 0;    // tap surge on touch devices
    const _origin = new Vector3();
    const _dir = new Vector3();
    const raycaster = new Raycaster();

    // project the pointer onto the G's mid-depth plane (z = 0 world)
    const projectPointer = (clientX, clientY) => {
        const rect = host.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        const nx = ((clientX - rect.left) / rect.width) * 2 - 1;
        const ny = -((clientY - rect.top) / rect.height) * 2 + 1;
        pointerNdc.set(nx, ny);
        _origin.setFromMatrixPosition(camera.matrixWorld);
        _dir.set(nx, ny, 0.5).unproject(camera).sub(_origin).normalize();
        const t = (0 - _origin.z) / _dir.z;
        if (!isFinite(t) || t <= 0) return;
        pointerWorld.copy(_origin).addScaledVector(_dir, t);
        pointerSeen = true;
    };

    const onMove = (e) => projectPointer(e.clientX, e.clientY);
    if (finePointer) window.addEventListener('pointermove', onMove, { passive: true });

    // touch: a tap ON the mark bleach-pulses and bursts a splash from it
    const onDown = (e) => {
        if (finePointer) return;
        projectPointer(e.clientX, e.clientY);
        raycaster.setFromCamera(pointerNdc, camera);
        const hit = raycaster.intersectObjects(gMeshes, false)[0];
        if (hit) {
            pulse = 1;
            burst(hit.point, 44, 2.4);
        }
    };
    window.addEventListener('pointerdown', onDown, { passive: true });

    /* ── sizing ── */
    const resize = () => {
        layout(); // recomputes `small` BEFORE the DPR cap below reads it
        const w = host.clientWidth || 1;
        const h = host.clientHeight || 1;
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, small ? 1.5 : 2));
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
    };
    resize();
    const ro = new ResizeObserver(resize);
    ro.observe(host);

    /* ── pause (fully stop the rAF) when hidden or scrolled away ── */
    let pageVisible = !document.hidden;
    const onVis = () => { pageVisible = !document.hidden; kick(); };
    document.addEventListener('visibilitychange', onVis);
    let inView = true;
    const io = new IntersectionObserver((entries) => {
        inView = entries[entries.length - 1]?.isIntersecting !== false;
        kick();
    });
    io.observe(host);

    /* ── frame loop ── */
    let readyResolve;
    const ready = new Promise((r) => { readyResolve = r; });
    const start = performance.now();
    let shown = false;
    let last = -Infinity;
    let hoverT = 0;
    let prevT = 0;
    let progress01 = 0;
    const tilt = { x: 0, y: 0 };
    const prevPointer = new Vector3();
    let prevPointerValid = false;

    // the loop fully STOPS while the tab is hidden or the hero is scrolled
    // away — kick() restarts it, so a parked hero costs zero rAF wakeups
    let raf = 0;
    let running = false;
    const shouldRun = () => pageVisible && (inView || !shown);
    function kick() {
        if (!running && shouldRun()) {
            running = true;
            last = -Infinity;
            raf = requestAnimationFrame(frame);
        }
    }
    const frame = (now) => {
        if (!shouldRun()) { running = false; return; }
        raf = requestAnimationFrame(frame);
        const minFrame = (small ? 1000 / 30 : 1000 / 60) - 0.5; // phones: 30fps is plenty
        if (now - last < minFrame) return;
        last = now;
        const t = (now - start) / 1000;
        const dt = Math.min(0.05, Math.max(0.001, t - prevT));
        prevT = t;

        // intro flight: big front-and-centre → resting pose (smootherstep)
        if (begun && introT < 1) introT = Math.min(1, introT + dt / 1.4);
        const ie = MathUtils.smootherstep(introT, 0, 1);
        const cx = MathUtils.lerp(0, camX, ie);
        const cy = MathUtils.lerp(GY, camYF, ie);
        const cz = MathUtils.lerp(introZ(), camZF, ie);
        camera.position.set(cx, cy, cz);
        camera.lookAt(cx, cy, 0);

        // cursor proximity drives both the bleach and the lean; touch has no
        // cursor — taps drive `pulse` instead, so hover stays 0 there
        const dx = pointerWorld.x - gGroup.position.x;
        const dy = pointerWorld.y - gGroup.position.y;
        const dist = Math.hypot(dx, dy);
        const near = (finePointer && pointerSeen) ? 1 - MathUtils.smoothstep(dist, 3.4, 6.4) : 0;
        hoverT += (near - hoverT) * 0.08;
        pulse *= 0.955;

        // the bleach: full strength under the cursor, a wide soft ring on tap
        // (parked while still loading — the vessel owns the surface then)
        uPointer.value.copy(pointerWorld);
        uBleach.value = begun ? hoverT + pulse * 1.2 : 0;
        uRadius.value = 1.7 + pulse * 2.6;

        // a gentle lean toward the cursor — the piece feels physical
        const ty = MathUtils.clamp(dx / 4.5, -1, 1) * 0.12 * hoverT;
        const tx = MathUtils.clamp(-dy / 4.5, -1, 1) * 0.09 * hoverT;
        tilt.x += (tx - tilt.x) * 0.07;
        tilt.y += (ty - tilt.y) * 0.07;

        // idle float, with the lean layered on top
        gGroup.position.y = GY + Math.sin(t * 0.4) * 0.12;
        gGroup.rotation.x = tilt.x;
        gGroup.rotation.y = Math.sin(t * 0.11) * 0.05 + tilt.y;
        gGroup.scale.setScalar(baseScale * (1 + pulse * 0.02));

        // the fill line rides the glyph (and its idle float) bottom → top
        uFillY.value = gGroup.position.y - 3.3 * baseScale + progress01 * 6.6 * baseScale;

        halo.material.opacity = 0.15 + 0.03 * Math.sin(t * 0.5) + hoverT * 0.05;
        for (const n of nebulae) n.s.material.opacity = n.o * (0.8 + 0.2 * Math.sin(t * 0.2 + n.phase));
        {   // dust rises and sways; wraps back under the floor of its box
            const arr = dustGeo.attributes.position.array;
            for (let i = 0; i < nDust; i++) {
                let y = arr[i * 3 + 1] + dustSpeed[i] * dt;
                if (y > 12) y -= 12;
                arr[i * 3 + 1] = y;
                arr[i * 3] += Math.sin(t * 0.4 + i * 1.7) * 0.0025;
            }
            dustGeo.attributes.position.needsUpdate = true;
            dust.rotation.y = t * 0.006;
        }

        // ── the splash: wiping across the letter knocks its colour off ──
        if (begun && finePointer && pointerSeen) {
            raycaster.setFromCamera(pointerNdc, camera);
            const hit = raycaster.intersectObjects(gMeshes, false)[0];
            if (hit) {
                const sx = (pointerWorld.x - prevPointer.x) / dt;
                const sy = (pointerWorld.y - prevPointer.y) / dt;
                const speed = prevPointerValid ? Math.hypot(sx, sy) : 0;
                // faster wipe → bigger splash, sprayed along the whole swept
                // path so a quick stroke leaves a trail, not one clump
                const n = speed > 0.4 ? Math.min(14, 1 + Math.floor(speed * 1.1)) : 0;
                for (let i = 0; i < n; i++) {
                    const along = Math.random();
                    spawnDrop(
                        hit.point.x - (pointerWorld.x - prevPointer.x) * along + (Math.random() - 0.5) * 0.25,
                        hit.point.y - (pointerWorld.y - prevPointer.y) * along + (Math.random() - 0.5) * 0.25,
                        hit.point.z + 0.15,
                        sx * (0.16 + Math.random() * 0.18) + (Math.random() - 0.5) * 0.9,
                        sy * (0.16 + Math.random() * 0.18) + (Math.random() - 0.25) * 0.9,
                        0.5 + Math.random() * 1.6,
                    );
                }
            }
        }
        prevPointer.copy(pointerWorld);
        prevPointerValid = pointerSeen;

        // advance the droplets: gravity, drag, shrink-and-darken fade
        let dropsAlive = false;
        for (let i = 0; i < MAX_P; i++) {
            if (pTtl[i] <= 0) continue;
            dropsAlive = true;
            pTtl[i] -= dt;
            if (pTtl[i] <= 0) { drops.setMatrixAt(i, _zero); continue; }
            pVel[i * 3 + 1] -= 6.5 * dt;
            const drag = 1 - 1.4 * dt;
            pVel[i * 3] *= drag; pVel[i * 3 + 1] *= drag; pVel[i * 3 + 2] *= drag;
            pPosArr[i * 3] += pVel[i * 3] * dt;
            pPosArr[i * 3 + 1] += pVel[i * 3 + 1] * dt;
            pPosArr[i * 3 + 2] += pVel[i * 3 + 2] * dt;
            const life = pTtl[i] / pMax[i];
            const sc = pSize[i] * (0.45 + 0.55 * life);
            _m4.makeScale(sc, sc, sc);
            _m4.setPosition(pPosArr[i * 3], pPosArr[i * 3 + 1], pPosArr[i * 3 + 2]);
            drops.setMatrixAt(i, _m4);
            _fadeCol.copy(pCol[i]).lerp(_voidCol, (1 - life) * 0.8);
            drops.setColorAt(i, _fadeCol);
        }
        if (dropsAlive) {
            drops.instanceMatrix.needsUpdate = true;
            if (drops.instanceColor) drops.instanceColor.needsUpdate = true;
        }

        renderer.render(scene, camera);
        if (!shown) {
            shown = true;
            setLive(true);
            readyResolve();
        }
    };
    kick();

    return {
        ready,
        // loader progress drives the vessel fill (0-100)
        setProgress(p) { progress01 = MathUtils.clamp(p / 100, 0, 1); },
        // the loader calls this as its veil lifts — the flight starts then
        begin() { begun = true; progress01 = 1; uVessel.value = 0; },
        destroy() {
            cancelAnimationFrame(raf);
            running = false;
            ro.disconnect();
            io.disconnect();
            document.removeEventListener('visibilitychange', onVis);
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerdown', onDown);
            scene.traverse((o) => {
                o.geometry?.dispose?.();
                const mats = Array.isArray(o.material) ? o.material : (o.material ? [o.material] : []);
                for (const m of mats) { m.map?.dispose?.(); m.dispose?.(); }
            });
            scene.environment?.dispose?.();
            glowTex.dispose();
            setLive(false);
            renderer.dispose();
            renderer.forceContextLoss();
            renderer.domElement.remove();
        },
    };
}
