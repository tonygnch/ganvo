/*
 | Ganvo marketing site — motion layer (Lenis + subtle GSAP).
 |
 | Premium/technical register: smooth scroll, gentle fade-and-rise reveals on
 | Apple/Linear easing, a whisper of hero parallax. Motion supports the
 | content; it is never the feature. Everything degrades to a clean static
 | page under prefers-reduced-motion, and the contact form works with or
 | without JS.
 |
 |   data-reveal[="up|fade|left|right"]   entrance when scrolled into view
 |   data-reveal-delay="0.05"             stagger offset (seconds)
 |   data-split                           statement — masked line-by-line reveal
 |   data-hero / -media / -content        hero (subtle parallax + load-in)
 |   data-parallax="0.08"                 scroll-scrubbed drift (project media)
 |   form[data-inquiry]                   async contact submit
 */

import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import Lenis from 'lenis';

gsap.registerPlugin(ScrollTrigger, SplitText);

// ScrollTrigger keeps its own scroll memory and re-applies the saved position
// AFTER the load event — the second, late restorer that dumped refreshes
// mid-page even with history.scrollRestoration set to manual. Clear it.
ScrollTrigger.clearScrollMemory('manual');

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const root = document.documentElement;
const EASE = 'expo.out';

// Refreshes must land on the hero: claim scroll restoration NOW, before the
// browser restores the previous position — the pin spacers don't exist yet at
// that moment, so a restored offset would land somewhere arbitrary mid-page.
if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
window.scrollTo(0, 0);

// Phones stream a 720p rendition of the hero loop and a lighter poster — the
// 1080p file is desktop bandwidth. Swapped at module eval, before playback
// begins, so load() restarts the fetch at most a few KB in.
{
    const hv = document.querySelector('[data-hero-video]');
    if (hv && window.matchMedia('(max-width: 768px)').matches) {
        const source = hv.querySelector('source');
        if (source && source.src.includes('hero.mp4')) {
            source.src = source.src.replace('hero.mp4', 'hero-720.mp4');
            hv.poster = hv.poster.replace('hero.png', 'hero-mobile.jpg');
            hv.load();
        }
    }
}

/* ─── smooth scroll ──────────────────────────────────────────────────────── */
let lenis = null;
if (!reduced) {
    lenis = new Lenis({ autoRaf: false, lerp: 0.09, wheelMultiplier: 1.0 });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((t) => lenis.raf(t * 1000));
    gsap.ticker.lagSmoothing(0);

    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href^="#"]');
        if (!a || a.getAttribute('href') === '#') return;
        const target = document.querySelector(a.getAttribute('href'));
        if (!target) return;
        e.preventDefault();
        // Pinned sections "live" at their pin's start scroll position, not at the
        // element's raw offset (the pin spacer stretches beneath them) — landing
        // anywhere else shows the section off-centre. Jump to the pin start when
        // the target owns one; plain sections keep the nav-height offset.
        const st = ScrollTrigger.getAll().find((t) => t.pin && target.contains(t.trigger));
        if (st) lenis.scrollTo(st.start + 1, { duration: 1.2 });
        else lenis.scrollTo(target, { offset: target.offsetHeight >= window.innerHeight ? 0 : -70 });
    });
}

/* progress hairline + nav condense */
ScrollTrigger.create({
    start: 0,
    end: 'max',
    onUpdate: (self) => {
        root.style.setProperty('--scroll', self.progress.toFixed(4));
        root.classList.toggle('is-scrolled', self.scroll() > 40);
    },
});

/* ─── loading screen — holds until the page is genuinely ready ───────────────
   Progress creeps to ~82% while assets arrive (real progress is unknowable),
   snaps to 100 once fonts + the hero loop are ready (or a 3.5s cap fires so a
   stalled network never traps the page), then the overlay lifts away. The
   hero entrance animation waits on the returned promise so it plays to a
   visible stage, not behind the curtain. Reduced-motion/no-JS never see it. */
let loaderDone = Promise.resolve();
function runLoader() {
    const el = document.querySelector('[data-loader]');
    if (!el) return Promise.resolve();
    if (reduced) { el.remove(); return Promise.resolve(); }
    const bar = el.querySelector('[data-loader-bar]');
    const pct = el.querySelector('[data-loader-pct]');
    const state = { p: 0 };
    const render = () => {
        if (pct) pct.textContent = String(Math.round(state.p));
        if (bar) bar.style.transform = 'scaleX(' + state.p / 100 + ')';
    };
    const creep = gsap.to(state, { p: 82, duration: 1.7, ease: 'power2.out', onUpdate: render });

    const minTime = new Promise((r) => setTimeout(r, 900));   // never just a flash
    const video = document.querySelector('[data-hero-video]');
    const videoReady = new Promise((r) => {
        if (!video || video.readyState >= 3) { r(); return; }
        video.addEventListener('canplay', () => r(), { once: true });
        setTimeout(r, 3500);
    });
    const fonts = document.fonts ? document.fonts.ready : Promise.resolve();

    return Promise.all([minTime, videoReady, fonts]).then(() => new Promise((resolve) => {
        creep.kill();
        gsap.to(state, {
            p: 100, duration: 0.35, ease: 'power1.in', onUpdate: render,
            onComplete: () => {
                el.classList.add('is-done');
                resolve();                          // hero entrance starts as the veil lifts
                setTimeout(() => el.remove(), 800); // clean up after the transition
            },
        });
    }));
}

/* ─── boot ───────────────────────────────────────────────────────────────── */
if (reduced) {
    document.querySelectorAll('[data-reveal], [data-split]').forEach((el) => el.classList.add('is-in'));
    document.body.classList.add('is-ready');
    document.querySelector('[data-loader]')?.remove();
    // Honor reduced-motion: freeze the hero video on its poster frame.
    const hv = document.querySelector('[data-hero-video]');
    if (hv) { hv.removeAttribute('autoplay'); hv.pause(); }
} else {
    loaderDone = runLoader();
    (document.fonts ? document.fonts.ready : Promise.resolve()).then(() => {
        document.body.classList.add('is-ready');
        // Create pinned triggers in DOCUMENT ORDER (top → bottom) so each one's
        // pin-spacer is in place before the next measures its start — otherwise a
        // pin added above others shifts them down and they pin early.
        buildSplits();        // statement's masked line reveal
        // buildStatementZoom(); // statement → "o" dive: temporarily disabled (re-enable to bring it back)
        buildPins();          // ALL pinned sections (holds · steppers · timeline), in document order
        buildReveals();
        buildParallax();
        buildHero();
        buildSectionRail();
        ScrollTrigger.refresh();
        buildSlideNav();      // one wheel gesture = one slide (after refresh: needs final pin positions)
        // fresh load: honour an explicit #hash (pin-aware), otherwise start at the top
        let hashTarget = null;
        try { hashTarget = location.hash ? document.querySelector(location.hash) : null; } catch (e) { /* malformed hash */ }
        if (hashTarget) {
            const st = ScrollTrigger.getAll().find((t) => t.pin && hashTarget.contains(t.trigger));
            if (st) lenis.scrollTo(st.start + 1, { immediate: true });
            else lenis.scrollTo(hashTarget, { offset: -70, immediate: true });
            ScrollTrigger.update();
        } else {
            lenis.scrollTo(0, { immediate: true });
        }
    });
}

/* ─── slide navigation — one wheel gesture advances one frame ─────────────────
   Desktop wheel input is captured (capture phase, before Lenis's own wheel
   handler) and translated into discrete jumps between "frames": the hero, the
   about slide, each stepper item, each timeline station, the work hold, contact
   and the footer. Frames are derived from the live pin geometry, so a resize just
   rebuilds them on ScrollTrigger refresh. Touch scrolling stays natural — swipe
   hijacking on phones hurts more than it helps — and reduced-motion never runs
   this (it lives in the Lenis-only boot path). */
function buildSlideNav() {
    let frames = [];

    const rebuild = () => {
        const max = ScrollTrigger.maxScroll(window);
        const pts = new Set([0]);
        ScrollTrigger.getAll().filter((t) => t.pin).forEach((t) => {
            const steps = t.trigger.closest('[data-steps]');
            const tl = t.trigger.closest('[data-timeline]');
            if (steps) {
                // stepper: item k is active for progress ∈ [k/n, (k+1)/n) — snap to
                // each segment's middle so the frame is stable, not on a boundary
                const n = steps.querySelectorAll('.step').length;
                for (let k = 0; k < n; k++) pts.add(Math.round(t.start + (t.end - t.start) * ((k + 0.5) / n)));
            } else if (tl) {
                // timeline: station k is centred exactly at progress k/(n-1)
                const n = tl.querySelectorAll('[data-tl-station]').length;
                for (let k = 0; k < n; k++) pts.add(Math.round(t.start + (t.end - t.start) * (k / (n - 1))));
            } else {
                pts.add(Math.round(t.start + 1));   // static hold — its pinned frame
            }
        });
        const contact = document.getElementById('contact');
        if (contact) pts.add(Math.round(Math.min(window.scrollY + contact.getBoundingClientRect().top, max)));
        pts.add(max);   // footer
        frames = [...pts].sort((a, b) => a - b).filter((v, i, arr) => i === 0 || v - arr[i - 1] > 40);
    };
    rebuild();
    ScrollTrigger.addEventListener('refresh', rebuild);

    // Feel: respond instantly, chain fluidly. A short gate (not a long lockout)
    // separates gestures; weak deltas — a trackpad's decaying momentum tail —
    // are ignored per-event, so a deliberate second flick lands even right
    // after the gate opens and retargets the glide mid-flight instead of dying.
    const GATE_MS = 550;        // min spacing between slide triggers
    const INTENT_DELTA = 25;    // per-event strength that counts as intent
    const THRESHOLD = 50;       // accumulated intent that triggers a slide
    let gateUntil = 0;
    let acc = 0;
    let target = -1;
    window.addEventListener('wheel', (e) => {
        // let native behaviour live where it matters: form fields and the modal
        if (e.target.closest('textarea, select, .work-modal')) return;
        if (document.querySelector('.work-modal.is-open')) return;
        e.preventDefault();
        e.stopPropagation();   // capture phase — Lenis's own wheel handler never sees it
        const now = performance.now();
        if (now < gateUntil) { acc = 0; return; }
        if (Math.abs(e.deltaY) < INTENT_DELTA) return;   // momentum tail / micro-jitter
        acc += e.deltaY;
        if (Math.abs(acc) < THRESHOLD) return;
        const dir = acc > 0 ? 1 : -1;
        acc = 0;
        // current frame: mid-glide, continue from the frame we're heading to,
        // so a chained flick advances rather than re-resolving from mid-air
        const y = Math.round(lenis.scroll);
        let cur = 0;
        for (let i = 0; i < frames.length; i++) if (frames[i] <= y + 2) cur = i;
        if (target >= 0 && Math.abs(frames[target] - y) > 2) cur = dir > 0 ? Math.max(cur, target) : Math.min(cur, target);
        const next = Math.max(0, Math.min(frames.length - 1, cur + dir));
        if (frames[next] === y) { target = -1; return; }
        target = next;
        gateUntil = now + GATE_MS;
        // constant PACE, not constant time: long section-to-section jumps take
        // proportionally longer and ease in-out (gentle start while the pinned
        // hold sits static, glide through, soft landing); short item hops keep
        // a snappy quart-out so stepping through a list stays light.
        const dist = Math.abs(frames[next] - y);
        const long = dist > 700;
        lenis.scrollTo(frames[next], {
            duration: Math.min(1.9, Math.max(0.9, 1 + dist / 1000)),
            easing: long
                ? (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2)
                : (t) => 1 - Math.pow(1 - t, 4),
            onComplete: () => { target = -1; },
        });
    }, { passive: false, capture: true });
}

buildContactForm();
buildProjectPreview();
buildWorkModal();

/* ─── recent-projects modal ──────────────────────────────────────────────────
   The Work section shows a trigger; clicking it opens an overlay listing the
   projects. Accessible: Esc closes, focus is trapped inside and restored to the
   trigger on close, background scroll is locked. */
function buildWorkModal() {
    const modal = document.querySelector('[data-work-modal]');
    const openers = document.querySelectorAll('[data-work-open]');
    if (!modal || !openers.length) return;
    const panel = modal.querySelector('.work-modal__panel');
    let lastFocus = null;

    const open = () => {
        lastFocus = document.activeElement;
        modal.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        lenis?.stop();
        // Force a reflow so the browser registers the hidden→shown state, then
        // flip to the open state — this drives the CSS transition reliably even
        // when requestAnimationFrame is throttled (e.g. a backgrounded tab).
        void modal.offsetWidth;
        modal.classList.add('is-open');
        panel.focus();
        document.addEventListener('keydown', onKey);
    };
    const close = () => {
        modal.classList.remove('is-open');
        document.documentElement.style.overflow = '';
        lenis?.start();
        document.removeEventListener('keydown', onKey);
        const done = (e) => {
            if (e && e.target !== modal) return;
            modal.hidden = true;
            modal.removeEventListener('transitionend', done);
        };
        modal.addEventListener('transitionend', done);
        setTimeout(() => { if (!modal.classList.contains('is-open')) modal.hidden = true; }, 450);
        lastFocus?.focus?.();
    };
    function onKey(e) {
        if (e.key === 'Escape') { close(); return; }
        if (e.key !== 'Tab') return;
        const f = [...modal.querySelectorAll('a[href], button:not([disabled])')].filter((el) => el.offsetParent !== null);
        if (!f.length) return;
        const first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }

    openers.forEach((o) => o.addEventListener('click', open));
    modal.querySelectorAll('[data-work-close]').forEach((c) => c.addEventListener('click', close));
}

/* ─── section status rail — reflects which section you're in ──────────────── */
function buildSectionRail() {
    const rail = document.querySelector('[data-rail]');
    if (!rail) return;
    const items = new Map();
    rail.querySelectorAll('[data-rail-item]').forEach((el) => items.set(el.dataset.railItem, el));
    const setActive = (id) => items.forEach((el, key) => el.classList.toggle('is-active', key === id));

    items.forEach((el, id) => {
        const section = document.getElementById(id);
        if (!section) return;
        ScrollTrigger.create({
            trigger: section,
            start: 'top 45%',
            end: 'bottom 45%',
            onToggle: (self) => { if (self.isActive) setActive(id); },
        });
    });
}

/* ─── statement → next-section dive ──────────────────────────────────────────
   Cinematic hand-off: pin the statement and drive the CAMERA into the "o" of
   "No compromises.". The zoom subject is a high-resolution clone — rendered at N×
   the font in an N×-wide box (so its line breaks match the original exactly) and
   shown at scale(1/N). Scrubbing its inner scale 1→N is a razor-sharp N× dolly
   into the letter that is NEVER upscaled past its own render, so the text stays
   crisp (no bitmap pixelation), then the pin releases into the section below.
   Runs after buildSplits; skipped under reduced-motion (CSS keeps the plain text). */
function buildStatementZoom() {
    const sec = document.querySelector('[data-statement-zoom]');
    if (!sec) return;
    const orig = sec.querySelector('.statement__text');
    if (!orig) return;
    sec.classList.add('is-zoom');

    // High-res clone: same copy rendered at N× the font in an N×-wide box, shown at
    // scale(1/N). Scrubbing the inner scale 1→N is a razor-sharp N× dolly into the
    // "o", never upscaled past its own render, so the text stays crisp — no pixelation.
    const baseFont = parseFloat(getComputedStyle(orig).fontSize) || 32;
    const baseW = Math.max(1, orig.offsetWidth);
    const N = Math.max(4, Math.min(9, Math.floor(7200 / baseW)));  // keep the render < ~7.2k px (GPU-safe)
    const fit = document.createElement('div');          // static scale(1/N), positioned over the original
    fit.className = 'statement__dive';
    fit.setAttribute('aria-hidden', 'true');
    const big = document.createElement('p');            // inner: font N×, scrub-scaled 1→N into the "o"
    big.className = 'statement__dive-text';
    big.style.fontSize = (baseFont * N) + 'px';
    big.style.width = (baseW * N) + 'px';
    // Rebuild the EXACT line breaks from the original's SplitText lines as nowrap
    // blocks — a re-flowing clone at N× would round-drift and add a line, throwing
    // the vertical alignment off. Forcing the same breaks keeps it pixel-locked.
    const origLines = [...orig.querySelectorAll('.line')];
    if (origLines.length) {
        origLines.forEach((ln) => {
            const el = document.createElement('span');
            el.className = 'statement__dive-line';
            el.textContent = ln.textContent;
            big.appendChild(el);
        });
    } else {
        big.textContent = orig.textContent;
    }
    fit.appendChild(big);
    sec.appendChild(fit);
    const bigGlyph = wrapFocusGlyph(big, 'oо');   // the focal "o" (last one, "compromises")

    // Full-screen black the dive settles INTO at max zoom — the screen goes fully
    // black, holds a beat, then the pin releases into the (dark) section below.
    const black = document.createElement('span');
    black.className = 'statement__black';
    black.setAttribute('aria-hidden', 'true');
    sec.appendChild(black);

    const inv = 1 / N;
    const layout = () => {
        const sr = sec.getBoundingClientRect();
        const orc = orig.getBoundingClientRect();
        if (!sr.width || !orc.width) return;
        // place the fit box exactly over the original; scale(1/N) origin 0 0 → 1:1
        fit.style.left = (orc.left - sr.left) + 'px';
        fit.style.top = (orc.top - sr.top) + 'px';
        fit.style.width = orc.width + 'px';
        fit.style.height = orc.height + 'px';
        gsap.set(fit, { scale: inv, transformOrigin: '0 0' });
        // inner zoom origin = the clone's "o" centre (ratio is scale-invariant)
        const bb = big.getBoundingClientRect();
        const bg = (bigGlyph || big).getBoundingClientRect();
        gsap.set(big, { transformOrigin: (((bg.left + bg.width / 2) - bb.left) / bb.width * 100).toFixed(2) + '% ' + (((bg.top + bg.height / 2) - bb.top) / bb.height * 100).toFixed(2) + '%' });
    };
    layout();

    gsap.timeline({
        scrollTrigger: {
            trigger: sec,
            start: 'center center',
            end: () => '+=' + Math.round(window.innerHeight * 1.4),
            pin: true, pinSpacing: true, scrub: 0.7,
            invalidateOnRefresh: true,
            onRefresh: layout,
        },
    })
        // hand the live text over to the crisp clone in the first frames (pixel match,
        // so invisible) — keeps the entrance reveal, then the clone becomes the subject
        .to(orig, { autoAlpha: 0, duration: 0.04, ease: 'none' }, 0)
        .fromTo(fit, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.04, ease: 'none' }, 0)
        // the dolly: a crisp N× dive straight into the "o" (maxes at ~70%)
        .fromTo(big, { scale: 1 }, { scale: N, ease: 'power2.in', duration: 0.7 }, 0)
        // then the screen goes fully black; it holds a beat before the pin releases
        .to(black, { opacity: 1, ease: 'power1.inOut', duration: 0.26 }, 0.64);
}

// Wrap the LAST occurrence of any character in `chars` found inside `root` in a
// <span> and return it — a precise focal point for the zoom. null if none present.
function wrapFocusGlyph(root, chars) {
    const set = chars.toLowerCase();
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
    let node, target = null, idx = -1;
    while ((node = walker.nextNode())) {
        const v = node.nodeValue.toLowerCase();
        for (let i = 0; i < v.length; i++) {
            if (set.indexOf(v[i]) !== -1) { target = node; idx = i; }
        }
    }
    if (!target) return null;
    const parent = target.parentNode;
    const rest = target.splitText(idx);
    rest.splitText(1);
    const span = document.createElement('span');
    span.className = 'zoom-focus';
    parent.insertBefore(span, rest);
    span.appendChild(rest);
    return span;
}

function buildSplits() {
    document.querySelectorAll('[data-split]').forEach((el) => {
        const split = new SplitText(el, { type: 'lines', linesClass: 'line' });
        split.lines.forEach((line) => {
            const wrap = document.createElement('span');
            wrap.className = 'line-mask';
            line.parentNode.insertBefore(wrap, line);
            wrap.appendChild(line);
        });
        gsap.set(split.lines, { yPercent: 110 });
        // Replays on EVERY entry, from either direction: reveal on enter, masked
        // exit on leave so coming back re-runs it. Kill the old tween by hand —
        // overwrite:'auto' misses staggered lines that haven't started yet, and
        // those would rise again after a reset.
        let tween = null;
        const show = () => {
            el.classList.add('is-in');
            if (tween) tween.kill();
            tween = gsap.fromTo(split.lines,
                { yPercent: 110 },
                { yPercent: 0, duration: 1.0, ease: 'power4.out', stagger: 0.08 });
        };
        const hide = () => {
            if (tween) tween.kill();
            tween = gsap.to(split.lines, { yPercent: 110, duration: 0.35, ease: 'power2.in', stagger: 0.04 });
        };
        // The statement sits inside a pinned hold: its real exit happens a full
        // pin-span later than unpinned coords suggest. Push the end edge out by
        // the hold's pin distance (function-based → re-measured on refresh).
        const holdEl = el.closest('[data-hold]');
        ScrollTrigger.create({
            trigger: el,
            start: 'top 82%',
            end: () => {
                const hold = holdEl && ScrollTrigger.getAll().find((t) => t.pin && t.trigger === holdEl);
                return 'bottom+=' + Math.round(hold ? hold.end - hold.start : 0) + ' top';
            },
            onEnter: show,
            onEnterBack: show,
            onLeave: hide,
            onLeaveBack: hide,
        });
    });
}

/* ─── pinned sections — every big moment holds the screen ────────────────────
   One dispatcher walks all pinnable sections in DOCUMENT ORDER (top → bottom) so
   each pin's spacer is in place before the next measures its start — pins created
   out of order shift later triggers and make them pin early. Three kinds:
     [data-steps]     stepper — pins while scroll picks the active item
     [data-timeline]  process — pins while the track scrubs horizontally
     [data-hold]      static hold — pins centred for a beat (statement · work)
   reduced-motion never calls this (CSS shows static layouts); if a pin setup
   throws, .is-static forces the plain layout — nothing is ever hidden. */
function buildPins() {
    // Pinning on touch: a mobile URL bar hiding/showing on scroll changes innerHeight
    // and would otherwise force a refresh that jitters the pin — ignore those. And own
    // scroll restoration so a reload / back-nav can't land on a stale offset before the
    // pin spacers are built (setup runs after fonts load).
    ScrollTrigger.config({ ignoreMobileResize: true });
    document.querySelectorAll('[data-steps], [data-timeline], [data-hold]').forEach((el) => {
        try {
            if (el.hasAttribute('data-steps')) stepPin(el);
            else if (el.hasAttribute('data-timeline')) timelinePin(el);
            else holdPin(el);
        } catch (e) {
            el.classList.add('is-static');
        }
    });
}

/* static hold: the section pins centred in the viewport for a short stretch of
   scroll, presenting as a framed full-screen moment like the steppers do. */
function holdPin(section) {
    ScrollTrigger.create({
        trigger: section,
        start: 'center center',
        end: () => '+=' + Math.round(window.innerHeight * 0.55),
        pin: true,
        pinSpacing: true,
        invalidateOnRefresh: true,
    });
}

function stepPin(group) {
    const items = [...group.querySelectorAll('.step')];
    const dots = [...group.querySelectorAll('.steps__dot')];
    const folio = group.querySelector('[data-step-current]');
    const n = items.length;
    if (n < 2) return;
    const inner = group.querySelector('.steps__inner') || group;
    let cur = -1;
    const setActive = (raw) => {
        const i = Math.max(0, Math.min(n - 1, raw));
        if (i === cur) return;
        cur = i;
        items.forEach((s, k) => s.classList.toggle('is-active', k === i));
        dots.forEach((d, k) => d.classList.toggle('is-active', k <= i));
        if (folio) folio.textContent = String(i + 1).padStart(2, '0');
    };
    ScrollTrigger.create({
        trigger: inner,
        start: 'center center',
        // per-item scroll distance; a touch shorter on phones so it never drags
        end: () => '+=' + Math.round(n * window.innerHeight * (window.innerWidth >= 900 ? 0.62 : 0.5)),
        pin: true,
        pinSpacing: true,
        invalidateOnRefresh: true,
        onUpdate: (self) => setActive(Math.floor(self.progress * n * 0.999)),
        onRefreshInit: () => { cur = -1; },
    });
    setActive(0);
}

/* ─── process timeline — horizontal scrub ────────────────────────────────────
   The section pins and a horizontal track of phase "stations" scrubs sideways with
   scroll. The track is offset so each station passes through the viewport centre:
   station i is centred at progress i/(n-1). A spine line spans the first→last node;
   its fill is scaleX(progress) — the geometry makes the fill's leading edge land
   exactly on the viewport centre at every progress, so the line appears to "draw"
   straight to screen centre while nodes ignite as they arrive. Called by
   buildPins(); reduced-motion / .is-static show the plain vertical list. */
function timelinePin(scope) {
    const inner = scope.querySelector('.tl__inner') || scope;
    const viewport = scope.querySelector('.tl__viewport') || inner;
    const track = scope.querySelector('[data-tl-track]');
    const spine = scope.querySelector('[data-tl-spine]');
    const fill = scope.querySelector('[data-tl-fill]');
    const meter = scope.querySelector('[data-tl-meter]');
    const stations = [...scope.querySelectorAll('[data-tl-station]')];
    const folio = scope.querySelector('[data-tl-current]');
    const n = stations.length;
    if (n < 2 || !track) return;

    // Layout position (ignores the live transform): x of a station's centre inside
    // the track, walked up the offsetParent chain so it's robust to wrappers.
    const centreOf = (el) => {
        let x = el.offsetWidth / 2, node = el;
        while (node && node !== track) { x += node.offsetLeft; node = node.offsetParent; }
        return x;
    };
    let c0 = 0, cN = 0;
    const measure = () => {
        c0 = centreOf(stations[0]);
        cN = centreOf(stations[n - 1]);
        if (spine) {                    // span the spine first-node → last-node
            spine.style.left = c0 + 'px';
            spine.style.width = Math.max(0, cN - c0) + 'px';
        }
    };
    measure();

    let cur = -1;
    const setActive = (raw) => {
        const i = Math.max(0, Math.min(n - 1, raw));
        if (i === cur) return;
        cur = i;
        stations.forEach((s, k) => {
            s.classList.toggle('is-active', k === i);
            s.classList.toggle('is-past', k < i);
        });
        if (folio) folio.textContent = String(i + 1).padStart(2, '0');
    };

    // Drive track x, the spine fill and the active node from ONE progress value
    // (no scrub — Lenis already eases the scroll). Because the track is offset so
    // x = W/2 − c0 − p·(cN − c0), station i is centred exactly at p = i/(n−1), and
    // the fill's leading edge (scaleX = p across the c0→cN spine) lands precisely on
    // the viewport centre at every p — so tip, playhead and active node stay locked.
    // Centre on the VIEWPORT (where the playhead lives at left:50%), not the window —
    // window.innerWidth includes the scrollbar, so it would push the active node a few
    // px off the playhead / node stem. viewport.clientWidth excludes it, so node,
    // fill-tip and playhead line stay pixel-locked.
    const mid = () => (viewport.clientWidth || window.innerWidth) / 2;
    let prog = 0;
    const apply = (p) => {
        gsap.set(track, { x: Math.round(mid() - c0 - p * (cN - c0)) });
        if (fill) fill.style.transform = 'scaleX(' + p.toFixed(4) + ')';
        if (meter) meter.style.transform = 'scaleX(' + p.toFixed(4) + ')';
        setActive(Math.round(p * (n - 1)));
    };
    apply(0);

    const st = ScrollTrigger.create({
        trigger: inner,
        start: 'top top',
        // measure INSIDE end so the pin distance is derived from the SAME fresh c0/cN
        // that apply() uses — GSAP parses end before firing onRefresh, so measuring in
        // onRefresh alone would leave the length one refresh stale after a resize/rotate.
        end: () => { measure(); return '+=' + Math.max(1, Math.round(cN - c0)); },
        pin: true,
        pinSpacing: true,
        invalidateOnRefresh: true,
        onRefreshInit: () => { cur = -1; },
        onRefresh: () => { apply(prog); },
        onUpdate: (self) => { prog = self.progress; apply(prog); },
    });

    // Snap: once scrolling settles inside the pinned range, ease to the nearest
    // station so the timeline steps cleanly point-to-point (Lenis owns the scroll, so
    // snap through it rather than ScrollTrigger's own snap, which Lenis would override).
    if (lenis) {
        let snapId = 0;
        const settle = () => {
            if (!st.isActive) return;
            const target = Math.round(prog * (n - 1)) / (n - 1);
            const y = st.start + target * (st.end - st.start);
            if (Math.abs(y - lenis.scroll) < 2) return;
            lenis.scrollTo(y, { duration: 0.55, easing: (t) => 1 - Math.pow(1 - t, 3) });
        };
        lenis.on('scroll', () => {
            if (!st.isActive) return;
            clearTimeout(snapId);
            snapId = setTimeout(settle, 130);
        });
    }
}

/* ─── generic entrances ──────────────────────────────────────────────────── */
function buildReveals() {
    const from = {
        up: { y: 24, opacity: 0, filter: 'blur(8px)' },
        fade: { opacity: 0, filter: 'blur(8px)' },
        left: { x: -26, opacity: 0, filter: 'blur(6px)' },
        right: { x: 26, opacity: 0, filter: 'blur(6px)' },
    };
    document.querySelectorAll('[data-reveal]').forEach((el) => {
        const kind = el.dataset.reveal || 'up';
        const delay = parseFloat(el.dataset.revealDelay || '0');
        gsap.set(el, from[kind] || from.up);
        ScrollTrigger.create({
            trigger: el, start: 'top 88%', once: true,
            onEnter: () => {
                el.classList.add('is-in');
                gsap.to(el, { x: 0, y: 0, opacity: 1, filter: 'blur(0px)', duration: 1.05, ease: EASE, delay });
            },
        });
    });
}

/* ─── subtle scrubbed parallax ───────────────────────────────────────────── */
function buildParallax() {
    document.querySelectorAll('[data-parallax]').forEach((el) => {
        const depth = parseFloat(el.dataset.parallax || '0.08');
        gsap.fromTo(el,
            { yPercent: -depth * 100 },
            {
                yPercent: depth * 100, ease: 'none',
                scrollTrigger: { trigger: el.closest('[data-parallax-scope]') || el, start: 'top bottom', end: 'bottom top', scrub: true },
            });
    });
}

/* ─── hero: gentle load-in + whisper of parallax (no pin) ────────────────── */
function buildHero() {
    const hero = document.querySelector('[data-hero]');
    if (!hero) return;
    const media = hero.querySelector('[data-hero-media]');
    const content = hero.querySelector('[data-hero-content]');

    // Nudge the background loop to play — some browsers hold `autoplay` on
    // muted video until asked, or until enough data is buffered.
    const video = hero.querySelector('[data-hero-video]');
    if (video) {
        const kick = () => video.play().catch(() => {});
        kick();
        video.addEventListener('canplay', kick, { once: true });
    }

    if (content) {
        gsap.set(content.children, { y: 22, opacity: 0 });
        loaderDone.then(() => {
            gsap.to(content.children, {
                y: 0, opacity: 1, duration: 1.1, ease: EASE, stagger: 0.09, delay: 0.15,
            });
        });
    }
    if (media) {
        gsap.to(media, {
            yPercent: 12, ease: 'none',
            scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true },
        });
    }
}

/* ─── contact form (progressive enhancement) ─────────────────────────────── */
function buildContactForm() {
    const form = document.querySelector('form[data-inquiry]');
    if (!form) return;
    const note = form.querySelector('[data-inquiry-note]');
    const submit = form.querySelector('[data-inquiry-submit]');
    const submitHtml = submit ? submit.innerHTML : '';

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (note) { note.className = 'form__note'; note.textContent = ''; }
        if (submit) { submit.disabled = true; submit.textContent = submit.dataset.sending || '…'; }
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.ok) {
                form.reset();
                showNote(note, data.message, true);
            } else {
                showNote(note, data.message || 'Something went wrong.', false);
            }
        } catch {
            showNote(note, 'Something went wrong. Please email us directly.', false);
        } finally {
            if (submit) { submit.disabled = false; submit.innerHTML = submitHtml; }
        }
    });
}
function showNote(note, message, ok) {
    if (!note) return;
    note.textContent = message;
    note.className = 'form__note is-shown ' + (ok ? 'is-ok' : 'is-err');
}

/* ─── work: live hover preview ───────────────────────────────────────────────
   Desktop (fine pointer) only. Hovering a project row lazy-loads a scaled live
   iframe of the real site into a card that follows the cursor; the row itself
   is a normal link that opens the site in a new tab. Purely decorative — touch
   and keyboard users get the link with no preview. */
function buildProjectPreview() {
    const list = document.querySelector('[data-proj-list]');
    const preview = document.querySelector('[data-proj-preview]');
    if (!list || !preview) return;
    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;

    const iframe = preview.querySelector('iframe');
    let currentUrl = null, rafId = 0, px = 0, py = 0;

    iframe.addEventListener('load', () => { if (iframe.src) iframe.classList.add('is-loaded'); });

    list.querySelectorAll('[data-proj]').forEach((row) => {
        row.addEventListener('pointerenter', (e) => {
            px = e.clientX; py = e.clientY;
            const url = row.dataset.url;
            if (url && url !== '#' && url !== currentUrl) {
                currentUrl = url;
                iframe.classList.remove('is-loaded');
                iframe.src = url;
            }
            preview.classList.add('is-on');
            place();
        });
    });

    list.addEventListener('pointermove', (e) => {
        px = e.clientX; py = e.clientY;
        if (!rafId) rafId = requestAnimationFrame(place);
    });
    list.addEventListener('pointerleave', () => preview.classList.remove('is-on'));

    function place() {
        rafId = 0;
        const w = preview.offsetWidth, h = preview.offsetHeight;
        let x = px + 28;
        if (x + w > window.innerWidth - 16) x = px - w - 28; // flip left of the cursor near the edge
        x = Math.max(16, x);
        const y = Math.max(16, Math.min(py - h / 2, window.innerHeight - h - 16));
        preview.style.left = x + 'px';
        preview.style.top = y + 'px';
    }
}
