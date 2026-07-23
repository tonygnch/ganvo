/*
 | HERO G — the Ganvo mark as a live 3D object.
 |
 | The brand G (viewBox 0 0 59 66: left arch M0,11 a11,11 0 0 1 22,0 v55 h-22 z,
 | right arch M37,36 a11,11 0 0 1 22,0 v30 h-22 z) is sliced into stacked glass
 | blocks with azure light glowing through the seams, floating in a pure void
 | with drifting dust. Ported from the voyage-v3 world module's CH1 build.
 |
 | Interaction (fine pointers only): the G leans toward the cursor (damped, max
 | ~12°) while an azure light tracks the cursor's projected position — blocks
 | brighten as the pointer passes near them, and everything eases back to the
 | idle float when the pointer leaves. Touch devices get a tap glow surge.
 |
 | Degradation: reduced-motion never boots this module; WebGL failure (incl.
 | software GL) returns null and the poster <img> stays; a lost GL context
 | brings the poster back until the browser restores it. DPR capped, 60fps
 | cap, rendering pauses when the tab hides or the hero scrolls away.
 |
 | Pitfalls encoded here (learned the hard way): the canvas must clear OPAQUE
 | void — with alpha the transmission buffer clears to milky white; and glow
 | quads must be transparent:false + AdditiveBlending so they exist in the
 | opaque pass the transmission buffer renders.
 */

import {
    AdditiveBlending,
    BufferGeometry,
    CanvasTexture,
    Color,
    DirectionalLight,
    ExtrudeGeometry,
    Float32BufferAttribute,
    FogExp2,
    Group,
    MathUtils,
    Mesh,
    MeshBasicMaterial,
    MeshPhysicalMaterial,
    PerspectiveCamera,
    PlaneGeometry,
    PMREMGenerator,
    PointLight,
    Points,
    PointsMaterial,
    Scene,
    Shape,
    Sprite,
    SpriteMaterial,
    Vector3,
    WebGLRenderer,
} from 'three';

const AZURE = 0x4d8dff;
const ICE = 0x7dd3fc;
const VOID = 0x020409;

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
        // back without it, and dark unlit glass would fade in. Regenerate.
        const pg = new PMREMGenerator(renderer);
        scene.environment = pg.fromScene(envScene, 0.3).texture;
        pg.dispose();
        setLive(true);
    });

    let small = (host.clientWidth || window.innerWidth) < 800;
    const scene = new Scene();
    scene.fog = new FogExp2(VOID, 0.014); // gives the dust depth in the void
    const camera = new PerspectiveCamera(50, 1, 0.1, 120);

    /* ── dark studio environment for the glass (azure light strips) ── */
    const envScene = new Scene();
    envScene.background = new Color(0x010208);
    const strip = (hex, intensity, w, h, pos) => {
        const m = new MeshBasicMaterial({ color: new Color(hex).multiplyScalar(intensity) });
        const q = new Mesh(new PlaneGeometry(w, h), m);
        q.position.set(pos[0], pos[1], pos[2]);
        q.lookAt(0, 0, 0);
        envScene.add(q);
    };
    strip(0xcfe0ff, 7, 3, 12, [-7, 5, 3]);
    strip(0x4d8dff, 10, 9, 2.5, [6, 7, -4]);
    strip(0x7dd3fc, 7, 7, 1.2, [1, -5, 5]);
    strip(0x4d8dff, 3, 12, 6, [0, 2, 9]);
    const pmrem = new PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(envScene, 0.3).texture;
    pmrem.dispose();

    const glowTex = glowTexture();
    const key = new DirectionalLight(0xd8e8ff, 0.5);
    key.position.set(-4, 8, 6);
    scene.add(key);

    // The glow-follow light: parked dark until the pointer approaches the G.
    const follow = new PointLight(AZURE, 0, 11, 1.8);
    scene.add(follow);

    /* ════════ the G, assembled from glass blocks ════════
       True geometry traced from the brand icon (256×256 px): one continuous
       arch — outer radius 129 / inner 76 around centre (129,129), left leg
       x∈[0,51] running to the bottom, the arch's right end cut at y≈102 —
       plus the crossbar (x∈[97,256], y∈[130,180]) and the right foot column
       (x∈[205,256], y∈[181,256]). Split at its natural joints into 7 blocks. */
    const S = 6.6 / 256;      // icon px → world units (G is 6.6 tall)
    const GY = 3.45;          // world height of the G's centre
    const gGroup = new Group();
    gGroup.position.set(0, GY, 0);
    scene.add(gGroup);
    const gBlocks = [];

    const glassBase = new MeshPhysicalMaterial({
        transmission: 1,
        thickness: 1.2,
        roughness: 0.22,
        metalness: 0,
        ior: 1.42,
        dispersion: small ? 0 : 0.28,
        color: 0xffffff,
        attenuationColor: new Color(0xbcd6ff),
        attenuationDistance: 20,
        envMapIntensity: 1.4,
        clearcoat: 1,
        clearcoatRoughness: 0.18,
        emissive: new Color(0x0a1830),
        emissiveIntensity: 0.55,
    });

    // icon px → centred world coords (icon y grows down, world y grows up)
    const wx = (px) => (px - 128) * S;
    const wy = (py) => (128 - py) * S;
    const CX = wx(129), CY = wy(129);          // arch centre
    const R_OUT = 129 * S, R_IN = 76 * S;      // arch radii

    const addBlock = (shape) => {
        const geo = new ExtrudeGeometry(shape, { depth: 1.5, bevelEnabled: true, bevelThickness: 0.05, bevelSize: 0.05, bevelSegments: 2, curveSegments: 18 });
        geo.translate(0, 0, -0.75);
        geo.computeBoundingBox();
        const centre = new Vector3();
        geo.boundingBox.getCenter(centre);
        const block = new Mesh(geo, glassBase.clone()); // own emissive per block
        gGroup.add(block);
        gBlocks.push({ block, seed: Math.random() * Math.PI * 2, centre });
    };

    // axis-aligned bar in icon coords (y0i above y1i on screen)
    const rectBlock = (x0i, x1i, y0i, y1i) => {
        const s = new Shape();
        s.moveTo(wx(x0i), wy(y1i));
        s.lineTo(wx(x0i), wy(y0i));
        s.lineTo(wx(x1i), wy(y0i));
        s.lineTo(wx(x1i), wy(y1i));
        s.closePath();
        addBlock(s);
    };

    // annular arch segment between world angles (a0 > a1, swept clockwise
    // over the crown); the last segment closes on the flat right-end cut
    const arcBlock = (a0, a1) => {
        const s = new Shape();
        s.absarc(CX, CY, R_OUT, a0, a1, true);
        s.lineTo(CX + R_IN * Math.cos(a1), CY + R_IN * Math.sin(a1));
        s.absarc(CX, CY, R_IN, a1, a0, false);
        s.closePath();
        addBlock(s);
    };

    const AGAP = 0.028;               // angular seam between arch segments
    const CUT_Y = wy(102);            // the arch's flat right-end cut height
    const aCutOut = Math.asin((CUT_Y - CY) / R_OUT);   // outer/inner angles of
    const aCutIn = Math.asin((CUT_Y - CY) / R_IN);     // the right-end cut

    rectBlock(0, 51, 192 + 2, 256);                    // left leg, lower
    rectBlock(0, 51, 129, 192 - 2);                    // left leg, upper
    arcBlock(Math.PI - 0.001, MathUtils.degToRad(118) + AGAP);        // crown left
    arcBlock(MathUtils.degToRad(118) - AGAP, MathUtils.degToRad(55) + AGAP); // crown top
    {   // crown right, ending on the flat cut at y≈102
        const a0 = MathUtils.degToRad(55) - AGAP;
        const s = new Shape();
        s.absarc(CX, CY, R_OUT, a0, aCutOut, true);
        s.lineTo(CX + R_IN * Math.cos(aCutIn), CY + R_IN * Math.sin(aCutIn));
        s.absarc(CX, CY, R_IN, aCutIn, a0, false);
        s.closePath();
        addBlock(s);
    }
    rectBlock(97, 256, 130, 180 - 1);                  // crossbar
    rectBlock(205, 256, 181 + 1, 256);                 // right foot

    // light INSIDE the G: opaque-pass additive glow planes — they exist in
    // the transmission buffer, so the glass glows from within and the light
    // escapes through the seams between blocks
    const innerGlow = (x, y, w, h) => {
        const m = new MeshBasicMaterial({
            map: glowTex, color: new Color(0xbfdcff).multiplyScalar(2.1), transparent: false,
            blending: AdditiveBlending, depthWrite: false,
        });
        const q = new Mesh(new PlaneGeometry(w, h), m);
        q.position.set(x, y, 0);
        gGroup.add(q);
        return m;
    };
    const glowL = innerGlow(wx(25), wy(190), 2.2, 4.2);   // left leg core
    const glowR = innerGlow(wx(200), wy(170), 4.2, 2.6);  // bar + foot core
    const glowC = innerGlow(wx(129), wy(40), 4.6, 2.4);   // crown core

    // soft halo behind the whole mark
    const halo = new Sprite(new SpriteMaterial({
        map: glowTex, color: AZURE, transparent: true, opacity: 0.38,
        blending: AdditiveBlending, depthWrite: false,
    }));
    halo.position.set(0, GY, -3.5);
    halo.scale.set(17, 13, 1);
    scene.add(halo);

    // faint nebulae so the void has depth without stealing focus
    const nebulae = [];
    for (const n of [
        { x: 6, y: 7, z: -26, sx: 46, sy: 24, o: 0.10, c: AZURE },
        { x: -9, y: 2, z: -34, sx: 40, sy: 22, o: 0.07, c: ICE },
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
    const nDust = small ? 90 : 160;
    const dustPos = new Float32Array(nDust * 3);
    for (let i = 0; i < nDust; i++) {
        dustPos[i * 3] = (Math.random() - 0.5) * 30;
        dustPos[i * 3 + 1] = Math.random() * 12;
        dustPos[i * 3 + 2] = (Math.random() - 0.5) * 18;
    }
    const dustGeo = new BufferGeometry();
    dustGeo.setAttribute('position', new Float32BufferAttribute(dustPos, 3));
    const dust = new Points(dustGeo, new PointsMaterial({
        color: ICE, size: 0.05, transparent: true, opacity: 0.45,
        blending: AdditiveBlending, depthWrite: false, sizeAttenuation: true,
    }));
    scene.add(dust);

    /* ── layout: G right-of-centre on wide screens, centred on small ── */
    let camX = 0;
    let baseScale = 1;
    const layout = () => {
        small = (host.clientWidth || window.innerWidth) < 800;
        const wide = (host.clientWidth || window.innerWidth) >= 1024;
        // shifting the camera left pushes the G (at x=0) right of centre,
        // clearing the left-aligned headline without moving the mark itself
        camX = wide ? -4.3 : 0;
        baseScale = small ? 0.62 : 1;
        gGroup.scale.setScalar(baseScale);
        camera.position.set(camX, GY, wide ? 13.8 : 15.5);
        camera.lookAt(camX, GY, 0);
    };
    layout();

    /* ── pointer: tilt toward the cursor + glow follow ── */
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const pointerWorld = new Vector3(0, GY, 99); // parked far until first move
    let pointerSeen = false;
    let hoverT = 0;   // eased 0..1 "cursor is near the G"
    let pulse = 0;    // tap surge on touch devices
    const _origin = new Vector3();
    const _dir = new Vector3();

    // project the pointer onto the G's mid-depth plane (z = 0 world)
    const projectPointer = (clientX, clientY) => {
        const rect = host.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        const nx = ((clientX - rect.left) / rect.width) * 2 - 1;
        const ny = -((clientY - rect.top) / rect.height) * 2 + 1;
        _origin.setFromMatrixPosition(camera.matrixWorld);
        _dir.set(nx, ny, 0.5).unproject(camera).sub(_origin).normalize();
        const t = (0 - _origin.z) / _dir.z;
        if (!isFinite(t) || t <= 0) return;
        pointerWorld.copy(_origin).addScaledVector(_dir, t);
        pointerSeen = true;
    };

    const onMove = (e) => projectPointer(e.clientX, e.clientY);
    if (finePointer) window.addEventListener('pointermove', onMove, { passive: true });

    // touch: a tap ON the mark makes the light surge through it (scroll
    // flicks elsewhere on the page must not trigger anything)
    const onDown = (e) => {
        if (finePointer) return;
        projectPointer(e.clientX, e.clientY);
        const d = Math.hypot(pointerWorld.x - gGroup.position.x, pointerWorld.y - gGroup.position.y);
        if (d < 4.6) pulse = 1;
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
    const tilt = { x: 0, y: 0 };
    const _blockWorld = new Vector3();

    // the loop fully STOPS while the tab is hidden or the hero is scrolled
    // away — kick() (called from the visibility/intersection callbacks)
    // restarts it, so a parked hero costs zero rAF wakeups
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

        // how near is the cursor to the mark? (radial falloff in world units;
        // touch has no cursor — taps drive `pulse` instead, so hover stays 0)
        const dx = pointerWorld.x - gGroup.position.x;
        const dy = pointerWorld.y - gGroup.position.y;
        const dist = Math.hypot(dx, dy);
        const near = (finePointer && pointerSeen) ? 1 - MathUtils.smoothstep(dist, 3.2, 6.4) : 0;
        hoverT += (near - hoverT) * 0.08;
        pulse *= 0.94;
        const glowDrive = hoverT + pulse;

        // lean toward the cursor — damped, capped at ~12°
        const ty = MathUtils.clamp(dx / 4.5, -1, 1) * 0.21 * hoverT;
        const tx = MathUtils.clamp(-dy / 4.5, -1, 1) * 0.15 * hoverT;
        tilt.x += (tx - tilt.x) * 0.07;
        tilt.y += (ty - tilt.y) * 0.07;

        // idle float + breathing, with the lean layered on top
        gGroup.position.y = GY + Math.sin(t * 0.4) * 0.12;
        gGroup.rotation.x = tilt.x;
        gGroup.rotation.y = Math.sin(t * 0.11) * 0.06 + tilt.y;
        gGroup.scale.setScalar(baseScale * (1 + pulse * 0.03));
        for (const b of gBlocks) {
            b.block.position.y = Math.sin(t * 0.5 + b.seed) * 0.07;
            b.block.position.x = Math.sin(t * 0.33 + b.seed * 1.7) * 0.05;
            b.block.rotation.z = Math.sin(t * 0.21 + b.seed) * 0.008;
            // blocks brighten as the pointer passes them
            b.block.localToWorld(_blockWorld.copy(b.centre));
            const bd = _blockWorld.distanceTo(pointerWorld);
            const boost = 1 - MathUtils.smoothstep(bd, 0.6, 3.0);
            b.block.material.emissiveIntensity = 0.55 + glowDrive * boost * 1.7 + pulse * 0.5;
        }

        // the azure light rides the cursor (or flashes at the core on tap)
        follow.position.set(pointerWorld.x, pointerWorld.y, 2.3);
        follow.intensity = glowDrive * 30;

        const breathe = 1.7 + 0.5 * Math.sin(t * 0.7);
        glowL.color.setHex(0xbfdcff).multiplyScalar(breathe + glowDrive * 0.9);
        glowR.color.setHex(0xbfdcff).multiplyScalar(1.7 + 0.5 * Math.sin(t * 0.7 + 1.2) + glowDrive * 0.9);
        glowC.color.setHex(0xbfdcff).multiplyScalar(1.7 + 0.5 * Math.sin(t * 0.7 + 2.3) + glowDrive * 0.9);
        halo.material.opacity = 0.38 + glowDrive * 0.14;

        for (const n of nebulae) n.s.material.opacity = n.o * (0.8 + 0.2 * Math.sin(t * 0.2 + n.phase));
        dust.rotation.y = t * 0.008;

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
