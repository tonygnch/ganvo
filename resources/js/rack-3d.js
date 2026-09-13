/*
 | Sankevi rack configurator — the 3D view.
 |
 | Loaded on demand through window.gv.rack3d (storefront.js) the first time a
 | visitor presses „3D", so three.js costs the page nothing until then.
 |
 | This module knows how a rack is BUILT and nothing about how big it is. The
 | configurator hands update() a model in centimetres, derived from the same
 | constants and shelf heights as the 2D elevation — the two views cannot
 | disagree about a dimension because only one of them computes any.
 |
 | The construction is Sankevi's own, read off their catalogue and product
 | photographs: square uprights drilled the whole height, a front and a back
 | upright joined by rungs into a ladder-like side frame, boards resting on
 | Ø10 pins between the frames, and a flat steel X across the back of a bay.
 */

import {
    ACESFilmicToneMapping,
    BoxGeometry,
    CanvasTexture,
    Color,
    CylinderGeometry,
    DirectionalLight,
    Group,
    HemisphereLight,
    InstancedMesh,
    MathUtils,
    Mesh,
    MeshBasicMaterial,
    MeshStandardMaterial,
    Object3D,
    PCFSoftShadowMap,
    PerspectiveCamera,
    PlaneGeometry,
    Scene,
    ShadowMaterial,
    SRGBColorSpace,
    Vector3,
    WebGLRenderer,
} from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

/* The model is in centimetres; the scene is in metres, where three's
   defaults (shadow bias, near plane) behave. */
const CM = 0.01;

/* Parts the elevation cannot show, sized off the catalogue photographs rather
   than a drawing — worth confirming with Sankevi, and harmless if slightly off. */
const RUNG_H = 4; //         the front-to-back rails of a side frame
const PIN_R = 0.5; //        Ø10 mm
const PIN_REACH = 2.5; //    how far a pin stands proud of the post into a bay
const STRAP_W = 2.2; //      flat steel cross brace
const STRAP_T = 0.3;
const HOLE_R = 0.55;

/* Opening angle: three-quarter from the front left, a little above eye level —
   close to how the catalogue photographs the racks. */
const OPENING = new Vector3(-0.42, 0.3, 1).normalize();

function cssVar(el, name, fallback) {
    const v = getComputedStyle(el).getPropertyValue(name).trim();
    return v || fallback;
}

/**
 * Timber as a texture: a mid tone with soft light and dark streaks. 'v' runs
 * the streaks down the texture (uprights), 'u' across it (boards and rungs),
 * so the grain follows the long axis of each part. Seeded, so every visitor
 * sees the same planks.
 */
function grain(light, mid, dark, along) {
    const size = 256;
    const c = document.createElement('canvas');
    c.width = c.height = size;
    const ctx = c.getContext('2d');
    ctx.fillStyle = mid;
    ctx.fillRect(0, 0, size, size);

    let seed = 11;
    const rnd = () => (seed = (seed * 16807) % 2147483647) / 2147483647;
    for (let i = 0; i < 28; i++) {
        const at = rnd() * size;
        const width = 2 + rnd() * 16;
        ctx.globalAlpha = 0.08 + rnd() * 0.22;
        ctx.fillStyle = rnd() > 0.5 ? light : dark;
        if (along === 'v') ctx.fillRect(at, 0, width, size);
        else ctx.fillRect(0, at, size, width);
    }
    ctx.globalAlpha = 1;

    const tex = new CanvasTexture(c);
    tex.colorSpace = SRGBColorSpace;
    tex.anisotropy = 4;
    return tex;
}

export default function mountRack3d(host) {
    let renderer;
    try {
        renderer = new WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'low-power' });
    } catch {
        return null; // no WebGL after all — the page stays on the drawing
    }

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const palette = host.closest('[data-cfg]') || document.documentElement;

    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.outputColorSpace = SRGBColorSpace;
    renderer.toneMapping = ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = PCFSoftShadowMap;
    Object.assign(renderer.domElement.style, { display: 'block', width: '100%', height: '100%' });
    host.appendChild(renderer.domElement);

    const scene = new Scene();
    const camera = new PerspectiveCamera(32, 1, 0.01, 200);

    scene.add(new HemisphereLight(0xfff4e2, 0x2a3a2e, 1.35));
    const sun = new DirectionalLight(0xfff1dc, 2.1);
    sun.castShadow = true;
    sun.shadow.mapSize.set(2048, 2048);
    sun.shadow.bias = -0.0004;
    sun.shadow.normalBias = 0.01;
    scene.add(sun, sun.target);

    // Only the shadow is drawn, so the canvas keeps the page's own ground.
    const floor = new Mesh(new PlaneGeometry(1, 1), new ShadowMaterial({ opacity: 0.28 }));
    floor.rotation.x = -Math.PI / 2;
    floor.receiveShadow = true;
    scene.add(floor);

    /* One unit box and one unit rod, stretched per instance: a 25 m run is
       hundreds of boards and thousands of drill holes, and as instances that
       is still a handful of draw calls — which is what keeps a phone smooth. */
    const unitBox = new BoxGeometry(1, 1, 1);
    const unitRod = new CylinderGeometry(1, 1, 1, 12);

    const mats = {
        upright: new MeshStandardMaterial({ roughness: 0.78, metalness: 0 }),
        board: new MeshStandardMaterial({ roughness: 0.72, metalness: 0 }),
        /* Barely metallic, and faintly self-lit. A high metalness reflects the
           environment — and there is none here, so the braces rendered as
           near-black lines that vanished against the dark ground. */
        steel: new MeshStandardMaterial({ roughness: 0.5, metalness: 0.2 }),
        hole: new MeshBasicMaterial({ color: 0x2a1a0c }),
    };

    const PLAIN = new Color(1, 1, 1);
    const accent = new Color(0xc9a36a);
    const picked = new Color();

    /* The same wood and steel tokens as the drawing, so Daylight mode retunes
       the model along with everything else. */
    function paint() {
        const light = cssVar(palette, '--pc-wood-l', '#e2b57d');
        const mid = cssVar(palette, '--pc-wood-m', '#c2904f');
        const dark = cssVar(palette, '--pc-wood-d', '#8a5f2f');

        mats.upright.map?.dispose();
        mats.board.map?.dispose();
        mats.upright.map = grain(light, mid, dark, 'v');
        mats.board.map = grain(light, mid, dark, 'u');
        mats.upright.needsUpdate = true;
        mats.board.needsUpdate = true;

        mats.steel.color.set(cssVar(palette, '--pc-steel', '#9aa79c'));
        mats.steel.emissive.copy(mats.steel.color).multiplyScalar(0.22);
        try {
            accent.set(cssVar(palette, '--accent', '#c9a36a'));
        } catch {
            /* a colour three cannot parse keeps the default */
        }
        picked.copy(PLAIN).lerp(accent, 0.45);
    }
    paint();

    /* ---------- camera + controls ------------------------------------ */
    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = !reduced;
    controls.dampingFactor = 0.08;
    controls.screenSpacePanning = true;
    controls.minPolarAngle = 0.15;
    controls.maxPolarAngle = Math.PI / 2 - 0.04; // never look up from under the floor

    let raf = 0;
    let ticking = false;
    function tick() {
        raf = 0;
        ticking = true;
        const moving = controls.update(); // true while damping is still easing
        renderer.render(scene, camera);
        ticking = false;
        if (moving) raf = requestAnimationFrame(tick);
    }
    /* Frames on demand, not a loop: an idle model draws nothing. */
    function requestRender() {
        if (!raf && !ticking) raf = requestAnimationFrame(tick);
    }
    controls.addEventListener('change', requestRender);

    /* ---------- the rack --------------------------------------------- */
    const place = new Object3D();
    let rack = null;
    let last = null;
    let size = { w: 100, h: 200, d: 60 };
    let fitDistance = 1;
    let framed = false;

    function instanced(geometry, material, count, castShadow = true) {
        const mesh = new InstancedMesh(geometry, material, Math.max(count, 1));
        mesh.count = count;
        mesh.castShadow = castShadow;
        mesh.receiveShadow = true;
        return mesh;
    }

    function put(mesh, i, x, y, z, sx, sy, sz, rz = 0) {
        place.position.set(x * CM, y * CM, z * CM);
        place.rotation.set(0, 0, rz);
        place.scale.set(sx * CM, sy * CM, sz * CM);
        place.updateMatrix();
        mesh.setMatrixAt(i, place.matrix);
    }

    function build(m) {
        const H = m.height;
        const D = m.depth;
        const P = m.post;
        const frames = m.pitches.length;
        const bays = frames - 1;
        const levels = m.shelfTops.length;
        const ox = -m.length / 2; // centre the run on the origin
        const zFront = D / 2 - P / 2;
        const zBack = -D / 2 + P / 2;

        const group = new Group();

        // Side frames: a front and a back upright…
        const uprights = instanced(unitBox, mats.upright, frames * 2);
        let n = 0;
        for (const x of m.pitches) {
            put(uprights, n++, ox + x, H / 2, zFront, P, H, P);
            put(uprights, n++, ox + x, H / 2, zBack, P, H, P);
        }
        group.add(uprights);

        // …joined by rungs, which is what makes them a frame.
        const rungYs = H > 160 ? [12, H * 0.45, H - 8] : [12, H - 8];
        const rungs = instanced(unitBox, mats.board, frames * rungYs.length);
        n = 0;
        for (const x of m.pitches) {
            for (const y of rungYs) put(rungs, n++, ox + x, y, 0, P * 0.8, RUNG_H, D - 2 * P);
        }
        group.add(rungs);

        // Drilled the whole way down, through the post from bay to bay.
        const holes = instanced(unitRod, mats.hole, frames * 2 * m.holes.length, false);
        n = 0;
        for (const x of m.pitches) {
            for (const z of [zFront, zBack]) {
                for (const y of m.holes) put(holes, n++, ox + x, y, z, HOLE_R, P + 0.06, HOLE_R, Math.PI / 2);
            }
        }
        group.add(holes);

        // Boards, between the frames. The selected bay is tinted, as its
        // chip is, so the eye can find it in either view.
        const boards = instanced(unitBox, mats.board, bays * levels);
        n = 0;
        for (let i = 0; i < bays; i++) {
            const x0 = m.pitches[i] + P / 2 + 0.1;
            const x1 = m.pitches[i + 1] - P / 2 - 0.1;
            for (const top of m.shelfTops) {
                put(boards, n, ox + (x0 + x1) / 2, top - m.thick / 2, 0, x1 - x0, m.thick, m.shelfDepth);
                boards.setColorAt(n, i === m.selected ? picked : PLAIN);
                n++;
            }
        }
        if (boards.instanceColor) boards.instanceColor.needsUpdate = true;
        group.add(boards);

        // Pins under each board end. An end frame's pin reaches into one bay;
        // a shared frame's middle pin into both.
        const pins = instanced(unitRod, mats.steel, frames * levels * 2, false);
        n = 0;
        m.pitches.forEach((x, f) => {
            const left = f > 0 ? PIN_REACH : 0;
            const right = f < frames - 1 ? PIN_REACH : 0;
            const len = P + left + right;
            const cx = x + (right - left) / 2;
            for (const top of m.shelfTops) {
                const y = top - m.thick - PIN_R;
                put(pins, n++, ox + cx, y, zFront, PIN_R, len, PIN_R, Math.PI / 2);
                put(pins, n++, ox + cx, y, zBack, PIN_R, len, PIN_R, Math.PI / 2);
            }
        });
        group.add(pins);

        // The steel X, on the back face of every braced bay.
        const braced = m.braced.filter(Boolean).length;
        const straps = instanced(unitBox, mats.steel, braced * 2);
        n = 0;
        const bh = m.braceTop - m.braceBottom;
        m.braced.forEach((on, i) => {
            if (!on) return;
            const x0 = m.pitches[i];
            const x1 = m.pitches[i + 1];
            const len = Math.hypot(x1 - x0, bh);
            const angle = Math.atan2(bh, x1 - x0);
            const cx = ox + (x0 + x1) / 2;
            const cy = m.braceBottom + bh / 2;
            const z = -D / 2 - STRAP_T / 2 - 0.05;
            put(straps, n++, cx, cy, z, len, STRAP_W, STRAP_T, angle);
            put(straps, n++, cx, cy, z - STRAP_T, len, STRAP_W, STRAP_T, -angle);
        });
        group.add(straps);

        return group;
    }

    function disposeRack() {
        if (!rack) return;
        scene.remove(rack);
        rack.traverse((o) => o.isInstancedMesh && o.dispose());
        rack = null;
    }

    /* Light and shadow follow the rack, so a 25 m run is lit as evenly as a
       single bay and its shadow is not cut off at the second section. */
    function stage() {
        const w = size.w * CM;
        const h = size.h * CM;
        const d = size.d * CM;
        const reach = Math.max(w, h, d) * 0.75 + 0.5;

        floor.scale.set(w + 4, d + 4, 1);
        sun.position.set(-w * 0.3 - 1.2, h * 2.2 + 1, d * 2 + 1.6);
        sun.target.position.set(0, h / 2, 0);
        Object.assign(sun.shadow.camera, { left: -reach, right: reach, top: reach, bottom: -reach, near: 0.1, far: reach * 6 });
        sun.shadow.camera.updateProjectionMatrix();
    }

    /* The distance at which the whole rack fits the box, for this aspect. */
    function distanceToFit() {
        const vfov = MathUtils.degToRad(camera.fov);
        const hfov = 2 * Math.atan(Math.tan(vfov / 2) * camera.aspect);
        const w = size.w * CM;
        const h = size.h * CM;
        const d = size.d * CM;
        const byWidth = (w * 0.92 + d * 0.4) / 2 / Math.tan(hfov / 2);
        const byHeight = (h + d * 0.3) / 2 / Math.tan(vfov / 2);
        // Generous: in perspective the corner nearest the camera draws larger
        // than the box it came from, and at 1.18 it ran off the bottom edge.
        return Math.max(byWidth, byHeight) * 1.32 + d / 2;
    }

    /**
     * Point the camera at the rack.
     *
     * From the opening angle when asked to (first view, „Центрирай"). Otherwise
     * the visitor's own angle and zoom are kept and only re-applied to the new
     * size — adding a bay should not swing the camera back to where it started.
     */
    function frame(reset) {
        const offset = camera.position.clone().sub(controls.target);
        const keep = !reset && framed && offset.lengthSq() > 0;
        const zoom = keep ? offset.length() / fitDistance : 1;
        const direction = keep ? offset.normalize() : OPENING.clone();

        fitDistance = distanceToFit();
        const distance = fitDistance * MathUtils.clamp(zoom, 0.12, 3);

        controls.target.set(0, size.h * CM * 0.5, 0);
        camera.position.copy(controls.target).addScaledVector(direction, distance);
        camera.near = Math.max(0.01, distance / 200);
        camera.far = distance * 20 + 10;
        camera.updateProjectionMatrix();
        controls.minDistance = fitDistance * 0.12;
        controls.maxDistance = fitDistance * 3;
        framed = true;

        controls.update();
        requestRender();
    }

    function resize() {
        const w = host.clientWidth;
        const h = host.clientHeight;
        if (!w || !h) return;
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        requestRender();
    }

    const ro = new ResizeObserver(() => {
        resize();
        if (framed) frame(false); // full screen and back, rotating a phone
    });
    ro.observe(host);

    // Daylight / dark toggle: repaint the timber and redraw.
    const mo = new MutationObserver(() => {
        paint();
        if (last) api.update(last);
    });
    mo.observe(document.documentElement, { attributes: true, attributeFilter: ['data-mode'] });

    const api = {
        update(model) {
            last = model;
            disposeRack();
            rack = build(model);
            scene.add(rack);
            size = { w: model.length, h: model.height, d: model.depth };
            stage();
            resize();
            frame(!framed);
        },

        fit() {
            frame(true);
        },

        zoom(factor) {
            const offset = camera.position.clone().sub(controls.target);
            const length = MathUtils.clamp(offset.length() / factor, controls.minDistance, controls.maxDistance);
            camera.position.copy(controls.target).addScaledVector(offset.normalize(), length);
            controls.update();
            requestRender();
        },

        resize,

        destroy() {
            cancelAnimationFrame(raf);
            ro.disconnect();
            mo.disconnect();
            controls.dispose();
            disposeRack();
            unitBox.dispose();
            unitRod.dispose();
            floor.geometry.dispose();
            floor.material.dispose();
            for (const mat of Object.values(mats)) {
                mat.map?.dispose();
                mat.dispose();
            }
            renderer.dispose();
            renderer.domElement.remove();
        },
    };

    return api;
}
