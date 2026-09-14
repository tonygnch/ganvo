/*
 | Sankevi rack configurator — the 3D view.
 |
 | Loaded on demand through window.gv.rack3d (storefront.js) the first time a
 | visitor presses „3D", so three.js costs the page nothing until then.
 |
 | This module knows how a rack is BUILT and nothing about how big it is. The
 | configurator hands update() a model in centimetres, derived from the same
 | constants and shelf heights as the 2D elevation — the two views cannot
 | disagree about a dimension because only one of them computes any. The label
 | text comes from the page too, so a measurement reads the same in both.
 |
 | The construction is Sankevi's own, read off their catalogue and product
 | photographs: square uprights drilled the whole height, a front and a back
 | upright joined by rungs into a ladder-like side frame, boards resting on
 | Ø10 pins between the frames, and a flat steel X across the back of a bay.
 */

import {
    ACESFilmicToneMapping,
    BoxGeometry,
    BufferGeometry,
    CanvasTexture,
    Color,
    CylinderGeometry,
    DirectionalLight,
    Float32BufferAttribute,
    Group,
    HemisphereLight,
    InstancedMesh,
    LineBasicMaterial,
    LineSegments,
    MathUtils,
    Mesh,
    MeshBasicMaterial,
    MeshStandardMaterial,
    Object3D,
    PerspectiveCamera,
    PlaneGeometry,
    Scene,
    Spherical,
    SRGBColorSpace,
    Vector3,
    WebGLRenderer,
} from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { CSS2DObject, CSS2DRenderer } from 'three/addons/renderers/CSS2DRenderer.js';

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

/*
 | The measurements, in cm, the way a dimensioned elevation lays them out:
 | the overall length ABOVE the rack, each bay's width on the floor in front of
 | it, the height beside the first upright and the depth beside the last. The
 | length used to sit on the floor too, one row further out — and from the
 | low three-quarter view the two rows foreshortened into one band and their
 | labels landed on top of each other.
 */
const FLOOR = 0.4; //        lift off the floor so the shadow never z-fights it
const BAYS_AT = 42; //       the bay-width row, out from the front face
const TOP_AT = 18; //        the overall length, above the top of the uprights
const SIDE_AT = 16; //       height and depth lines, beside the run
const TICK = 4; //           half-length of a dimension line's end tick
const LABEL_ROOM = 64; //    px a bay-width label needs before they collide

/* Opening angle: three-quarter from the front left and a little above, close
   to how the catalogue photographs the racks — high enough that the floor
   measurements are read, not glimpsed edge-on. */
const OPENING = new Vector3(-0.42, 0.42, 1).normalize();

function cssVar(el, name, fallback) {
    const v = getComputedStyle(el).getPropertyValue(name).trim();
    return v || fallback;
}

function setColor(color, value, fallback) {
    try {
        color.set(value);
    } catch {
        color.set(fallback); // a colour three cannot parse (color-mix, say)
    }
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

/* How far the contact shadow spreads past the rack's footprint, in cm, and
   how dark it is directly under the rack. */
const CONTACT_FADE = 45;
const CONTACT_DARK = 0.34;

/**
 * A soft shadow for a footprint of w × d cm (fade included on every side),
 * as an alpha mask: darkest under the rack, easing to nothing at the edges.
 * Plain pixels, so it looks the same on every GPU — see the renderer setup.
 */
function contactShadow(w, d, fade) {
    const W = 256;
    const H = 128;
    const c = document.createElement('canvas');
    c.width = W;
    c.height = H;
    const ctx = c.getContext('2d');
    const img = ctx.createImageData(W, H);

    const ease = (t) => t * t * (3 - 2 * t);
    const edge = (t, f) => ease(Math.min(1, Math.max(0, t / f))) * ease(Math.min(1, Math.max(0, (1 - t) / f)));
    const fx = Math.min(0.5, fade / w);
    const fy = Math.min(0.5, fade / d);

    for (let y = 0; y < H; y++) {
        const ay = edge((y + 0.5) / H, fy);
        for (let x = 0; x < W; x++) {
            const i = (y * W + x) * 4;
            img.data[i + 3] = Math.round(255 * CONTACT_DARK * ay * edge((x + 0.5) / W, fx));
        }
    }
    ctx.putImageData(img, 0, 0);

    return new CanvasTexture(c);
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
    /*
     | NO SHADOW MAPS.
     |
     | three's shadows compare depth on the GPU (a sampler2DShadow). On an
     | iPhone that comparison came back "in shadow" everywhere: the transparent
     | floor that only draws shadow turned into a solid black slab, the sun went
     | out on the rack and the top board went black. Shadows now come from a
     | soft contact shadow baked into a texture under the rack — ordinary alpha
     | blending, which every GPU draws the same, and cheaper on a phone besides.
     */
    Object.assign(renderer.domElement.style, { display: 'block', width: '100%', height: '100%' });
    host.appendChild(renderer.domElement);

    /* Measurements, and the arrow on the selected bay's label, are HTML over
       the canvas rather than objects in the scene: crisp at any zoom, always
       facing the viewer, in the storefront's own type and colours (.cfg-3d-dim).
       They never take a pointer, so dragging over one still turns the model. */
    const labels = new CSS2DRenderer();
    Object.assign(labels.domElement.style, { position: 'absolute', inset: '0', pointerEvents: 'none', zIndex: '1' });
    host.appendChild(labels.domElement);

    const scene = new Scene();
    const camera = new PerspectiveCamera(32, 1, 0.01, 200);

    /*
     | Every face gets some light. The hemisphere's ground colour is what a
     | downward face sees, and it used to be the near-black of the page — so
     | turned side-on, the underside of the top board went black and the far
     | uprights dark brown. A warm timber-shade ground and a soft fill from
     | behind, opposite the sun, keep the back of the rack readable as wood.
     */
    scene.add(new HemisphereLight(0xfff4e2, 0x7a6450, 1.35));
    const sun = new DirectionalLight(0xfff1dc, 2.1);
    scene.add(sun, sun.target);
    const fill = new DirectionalLight(0xeef2ea, 0.85);
    scene.add(fill, fill.target);

    // The contact shadow: a plane under the rack carrying contactShadow().
    // Nothing else of the floor is drawn, so the canvas keeps the page's ground.
    const floor = new Mesh(new PlaneGeometry(1, 1), new MeshBasicMaterial({ color: 0x000000, transparent: true, depthWrite: false }));
    floor.rotation.x = -Math.PI / 2;
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
        dim: new LineBasicMaterial({ transparent: true, opacity: 0.8 }),
    };

    const PLAIN = new Color(1, 1, 1);
    const accent = new Color(0xc9a36a);
    const picked = new Color();

    /* The same wood, steel and ink tokens as the drawing, so Daylight mode
       retunes the model along with everything else. */
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

        setColor(mats.steel.color, cssVar(palette, '--pc-steel', '#9aa79c'), '#9aa79c');
        mats.steel.emissive.copy(mats.steel.color).multiplyScalar(0.22);

        setColor(accent, cssVar(palette, '--accent', '#c9a36a'), '#c9a36a');
        picked.copy(PLAIN).lerp(accent, 0.45);
        setColor(mats.dim.color, cssVar(palette, '--muted', '#a9b3a6'), '#a9b3a6');
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
    let flight = null; // a recentre in progress — see frame() and fly()
    function tick(now) {
        raf = 0;
        ticking = true;
        const flying = flight ? fly(now) : false;
        const moving = controls.update(); // true while damping is still easing
        declutter();
        renderer.render(scene, camera);
        labels.render(scene, camera);
        ticking = false;
        if (moving || flying) raf = requestAnimationFrame(tick);
    }
    /* Frames on demand, not a loop: an idle model draws nothing. */
    function requestRender() {
        if (!raf && !ticking) raf = requestAnimationFrame(tick);
    }
    controls.addEventListener('change', requestRender);
    // A hand on the model mid-flight takes the camera back from the animation.
    controls.addEventListener('start', () => {
        flight = null;
    });

    /* ---------- the rack --------------------------------------------- */
    const place = new Object3D();
    let rack = null;
    let last = null;
    let bounds = { minX: -50, maxX: 50, minZ: -30, maxZ: 30, h: 200 };
    let footprint = { w: 100, d: 60 }; // the rack itself, centred on the origin
    let fitDistance = 1;
    let framed = false;

    /* The bay-width labels and the two ends of their row, for declutter(). */
    let bayLabels = [];
    const rowStart = new Vector3();
    const rowEnd = new Vector3();

    function instanced(geometry, material, count) {
        const mesh = new InstancedMesh(geometry, material, Math.max(count, 1));
        mesh.count = count;
        return mesh;
    }

    function put(mesh, i, x, y, z, sx, sy, sz, rz = 0) {
        place.position.set(x * CM, y * CM, z * CM);
        place.rotation.set(0, 0, rz);
        place.scale.set(sx * CM, sy * CM, sz * CM);
        place.updateMatrix();
        mesh.setMatrixAt(i, place.matrix);
    }

    function overlay(className, x, y, z, fill) {
        const el = document.createElement('div');
        el.className = className;
        fill(el);
        const obj = new CSS2DObject(el);
        obj.position.set(x * CM, y * CM, z * CM);
        return obj;
    }

    function label(text, x, y, z, kind = '') {
        return overlay('cfg-3d-dim' + (kind ? ' ' + kind : ''), x, y, z, (el) => {
            el.textContent = text;
        });
    }

    function build(m) {
        const H = m.height;
        const D = m.depth;
        const P = m.post;
        const frames = m.pitches.length;
        const bays = frames - 1;
        const levels = m.shelfTops.length;
        const ox = -(m.pitches[0] + m.pitches[frames - 1]) / 2; // centre the run on the origin
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
        const holes = instanced(unitRod, mats.hole, frames * 2 * m.holes.length);
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
        const pins = instanced(unitRod, mats.steel, frames * levels * 2);
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

        /* ---- the selected bay and the measurements ---- */
        const front = D / 2;
        const xFirst = ox + m.pitches[0];
        const xLast = ox + m.pitches[frames - 1];
        const xLeft = xFirst - P / 2 - SIDE_AT;
        const xRight = xLast + P / 2 + SIDE_AT;
        const zBays = front + BAYS_AT;
        const yTop = H + TOP_AT;

        const seg = [];
        const line = (x0, y0, z0, x1, y1, z1) => seg.push(x0 * CM, y0 * CM, z0 * CM, x1 * CM, y1 * CM, z1 * CM);

        /*
         | Each bay's width on the floor, pitch line to pitch line — the width
         | the customer picked for it. The selected bay's label carries the
         | arrow pointing up into that bay (.cfg-3d-dim.is-picked::before). A
         | separate arrow at the bay's foot projected straight onto this label
         | from the opening view; as part of the label it cannot, and it stays
         | when declutter() hides the other widths.
         */
        const sel = Math.min(Math.max(m.selected, 0), bays - 1);
        bayLabels = [];
        line(xFirst, FLOOR, zBays, xLast, FLOOR, zBays);
        for (const x of m.pitches) line(ox + x, FLOOR, zBays - TICK, ox + x, FLOOR, zBays + TICK);
        for (let i = 0; i < bays; i++) {
            const mid = ox + (m.pitches[i] + m.pitches[i + 1]) / 2;
            const obj = label(m.labels.bays[i], mid, FLOOR, zBays, i === sel ? 'is-picked' : '');
            obj.userData.picked = i === sel;
            bayLabels.push(obj);
            group.add(obj);
        }
        rowStart.set(xFirst * CM, FLOOR * CM, zBays * CM);
        rowEnd.set(xLast * CM, FLOOR * CM, zBays * CM);

        // The whole run, above it.
        line(xFirst, yTop, front, xLast, yTop, front);
        line(xFirst, yTop - TICK, front, xFirst, yTop + TICK, front);
        line(xLast, yTop - TICK, front, xLast, yTop + TICK, front);
        group.add(label(m.labels.total, (xFirst + xLast) / 2, yTop, front, 'is-total'));

        // Height, standing beside the first upright.
        line(xLeft, 0, front, xLeft, H, front);
        line(xLeft - TICK, 0, front, xLeft + TICK, 0, front);
        line(xLeft - TICK, H, front, xLeft + TICK, H, front);
        group.add(label(m.labels.height, xLeft, H / 2, front));

        // Depth, along the floor past the last upright.
        line(xRight, FLOOR, -D / 2, xRight, FLOOR, D / 2);
        line(xRight - TICK, FLOOR, -D / 2, xRight + TICK, FLOOR, -D / 2);
        line(xRight - TICK, FLOOR, D / 2, xRight + TICK, FLOOR, D / 2);
        // The label sits at the back end of its line: at the middle it met the
        // selected bay's arrow on a phone.
        group.add(label(m.labels.depth, xRight, FLOOR, -D / 2));

        const dimGeometry = new BufferGeometry();
        dimGeometry.setAttribute('position', new Float32BufferAttribute(seg, 3));
        group.add(new LineSegments(dimGeometry, mats.dim));

        // What the camera has to fit: the rack AND its measurements.
        bounds = { minX: xLeft - 24, maxX: xRight + 24, minZ: -D / 2 - 4, maxZ: zBays + 10, h: yTop + 12 };
        footprint = { w: xLast - xFirst + P, d: D };

        return group;
    }

    /*
     | Bay widths on a long run, seen from far off, pile into one smudge. When
     | the row gives each bay less room than a label needs, only the selected
     | bay keeps its width; zoom in or turn the rack and the rest come back.
     */
    const a = new Vector3();
    const b = new Vector3();
    function declutter() {
        if (!bayLabels.length) return;
        a.copy(rowStart).project(camera);
        b.copy(rowEnd).project(camera);
        const px = (Math.abs(a.x - b.x) / 2) * host.clientWidth;
        const roomy = px / bayLabels.length >= LABEL_ROOM;
        for (const obj of bayLabels) obj.visible = roomy || obj.userData.picked;
    }

    function disposeRack() {
        if (!rack) return;
        scene.remove(rack);
        rack.traverse((o) => {
            if (o.isInstancedMesh) o.dispose();
            if (o.isLineSegments) o.geometry.dispose();
            // CSS2DRenderer leaves an element behind when only its parent is
            // removed from the scene; take it out of the page ourselves.
            if (o.isCSS2DObject) o.element.remove();
        });
        rack = null;
        bayLabels = [];
    }

    /* The light follows the rack, so a 25 m run is lit as evenly as a single
       bay, and the contact shadow is re-baked to the new footprint. */
    function stage() {
        const w = (bounds.maxX - bounds.minX) * CM;
        const d = (bounds.maxZ - bounds.minZ) * CM;
        const h = bounds.h * CM;
        const cx = ((bounds.minX + bounds.maxX) / 2) * CM;
        const cz = ((bounds.minZ + bounds.maxZ) / 2) * CM;

        sun.position.set(cx - w * 0.3 - 1.2, h * 2.2 + 1, cz + d * 2 + 1.6);
        sun.target.position.set(cx, h / 2, cz);
        fill.position.set(cx + w * 0.3 + 1.2, h * 0.8, cz - d * 2 - 1.6);
        fill.target.position.set(cx, h / 2, cz);

        const sw = footprint.w + 2 * CONTACT_FADE;
        const sd = footprint.d + 2 * CONTACT_FADE;
        floor.position.set(0, 0, 0);
        floor.scale.set(sw * CM, sd * CM, 1);
        floor.material.map?.dispose();
        floor.material.map = contactShadow(sw, sd, CONTACT_FADE);
        floor.material.needsUpdate = true;
    }

    /* The distance at which the rack and its measurements fit the box. */
    function distanceToFit() {
        const vfov = MathUtils.degToRad(camera.fov);
        const hfov = 2 * Math.atan(Math.tan(vfov / 2) * camera.aspect);
        const w = (bounds.maxX - bounds.minX) * CM;
        const d = (bounds.maxZ - bounds.minZ) * CM;
        const h = bounds.h * CM;
        const byWidth = (w * 0.92 + d * 0.4) / 2 / Math.tan(hfov / 2);
        const byHeight = (h + d * 0.3) / 2 / Math.tan(vfov / 2);
        // Generous: in perspective the corner nearest the camera draws larger
        // than the box it came from.
        return Math.max(byWidth, byHeight) * 1.25 + d / 2;
    }

    /**
     * Point the camera at the rack.
     *
     * From the opening angle when asked to (first view, „Центрирай"). Otherwise
     * the visitor's own angle and zoom are kept and only re-applied to the new
     * size — adding a bay should not swing the camera back to where it started.
     */
    function frame(reset, glide = false) {
        const offset = camera.position.clone().sub(controls.target);
        const keep = !reset && framed && offset.lengthSq() > 0;
        const zoom = keep ? offset.length() / fitDistance : 1;
        const direction = keep ? offset.normalize() : OPENING.clone();

        fitDistance = distanceToFit();
        const distance = fitDistance * MathUtils.clamp(zoom, 0.12, 3);

        const target = new Vector3(
            ((bounds.minX + bounds.maxX) / 2) * CM,
            bounds.h * CM * 0.45,
            ((bounds.minZ + bounds.maxZ) / 2) * CM,
        );
        const position = target.clone().addScaledVector(direction, distance);
        const range = { near: Math.max(0.01, distance / 200), far: distance * 20 + 10, min: fitDistance * 0.12, max: fitDistance * 3 };

        /*
         | The recentre button flies the camera there rather than cutting to it,
         | so the visitor sees where the view went. Only the button: the first
         | view and every edit stay instant — adding a bay should not set the
         | camera drifting — and so does everything under reduced motion.
         */
        if (glide && framed && !reduced) {
            startFlight(target, position, range);
            return;
        }

        flight = null;
        controls.target.copy(target);
        camera.position.copy(position);
        applyRange(range);
        framed = true;

        controls.update();
        requestRender();
    }

    function applyRange(range) {
        camera.near = range.near;
        camera.far = range.far;
        camera.updateProjectionMatrix();
        controls.minDistance = range.min;
        controls.maxDistance = range.max;
    }

    const FLIGHT_MS = 750;

    /*
     | The flight goes AROUND the rack, not through it: the camera's offset
     | from the target is interpolated as a sphere (distance, height angle,
     | turn angle), turning the short way round, while the target itself
     | slides across. A straight line from a back view to the front would
     | pass through the shelves on the way.
     */
    function startFlight(target, position, range) {
        const from = new Spherical().setFromVector3(camera.position.clone().sub(controls.target));
        const to = new Spherical().setFromVector3(position.clone().sub(target));

        let turn = (to.theta - from.theta) % (Math.PI * 2);
        if (turn > Math.PI) turn -= Math.PI * 2;
        if (turn < -Math.PI) turn += Math.PI * 2;

        // Widen the limits for the journey, so OrbitControls does not clamp
        // the camera to the destination's zoom range halfway there.
        camera.near = Math.min(camera.near, range.near);
        camera.far = Math.max(camera.far, range.far);
        camera.updateProjectionMatrix();
        controls.minDistance = Math.min(from.radius, range.min);
        controls.maxDistance = Math.max(from.radius, range.max);

        flight = { start: performance.now(), fromTarget: controls.target.clone(), toTarget: target, from, to, turn, range };
        requestRender();
    }

    const along = new Spherical();

    /* One step of the flight; true while there is more of it to come. */
    function fly(now) {
        const f = flight;
        const t = Math.min(1, Math.max(0, (now - f.start) / FLIGHT_MS));
        const e = t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; // ease in-out

        along.radius = MathUtils.lerp(f.from.radius, f.to.radius, e);
        along.phi = MathUtils.lerp(f.from.phi, f.to.phi, e);
        along.theta = f.from.theta + f.turn * e;
        controls.target.lerpVectors(f.fromTarget, f.toTarget, e);
        camera.position.setFromSpherical(along).add(controls.target);

        if (t < 1) return true;
        applyRange(f.range);
        flight = null;
        return false;
    }

    function resize() {
        const w = host.clientWidth;
        const h = host.clientHeight;
        if (!w || !h) return;
        renderer.setSize(w, h, false);
        labels.setSize(w, h);
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
            stage();
            resize();
            frame(!framed);
        },

        fit() {
            frame(true, true);
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
            floor.material.map?.dispose();
            floor.material.dispose();
            for (const mat of Object.values(mats)) {
                mat.map?.dispose();
                mat.dispose();
            }
            renderer.dispose();
            renderer.domElement.remove();
            labels.domElement.remove();
        },
    };

    return api;
}
