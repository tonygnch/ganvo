/*
 | Ganvo storefront kit — motion + interaction layer shared by every theme.
 |
 | One bundle, loaded once, driven entirely by data attributes so each theme
 | opts into behaviors declaratively and keeps its own personality via a
 | motion config on <body data-gv-motion='{"duration":1.1,"ease":"power3.out"}'>.
 |
 |   data-gv-reveal[="fade-up|fade|scale"]  scroll-into-view entrance (GSAP)
 |   data-gv-split                          masked line-by-line headline reveal
 |   data-gv-parallax="0.15"                scroll-scrubbed y drift (±ratio)
 |   data-gv-embla='{"loop":true}'          touch carousel on the element
 |   data-gv-counter="123"                  count-up when scrolled into view
 |   form[data-gv-add]                      async add-to-cart → opens the drawer
 |
 | Everything respects prefers-reduced-motion: reveals render instantly,
 | Lenis/parallax/split never engage, the drawer still works (no transition).
 |
 | Alpine is started by this bundle (deferred). The cart drawer/quick-view
 | markup lives in Blade partials (storefront.partials.cart-drawer etc.) and
 | binds to the Alpine store registered here as $store.gvCart.
 */

import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import Lenis from 'lenis';
import Alpine from 'alpinejs';
import EmblaCarousel from 'embla-carousel';

gsap.registerPlugin(ScrollTrigger, SplitText);

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ─── per-theme motion personality ─────────────────────────────────────── */

const defaults = { duration: 1.0, ease: 'power3.out', distance: 28, stagger: 0.08 };
let motion = { ...defaults };
try {
    motion = { ...defaults, ...JSON.parse(document.body.dataset.gvMotion || '{}') };
} catch { /* malformed config → defaults */ }

/* ─── smooth scroll (Lenis) ────────────────────────────────────────────── */

let lenis = null;
if (!reduced && document.body.dataset.gvSmooth !== 'off') {
    lenis = new Lenis({ autoRaf: false });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((t) => lenis.raf(t * 1000));
    gsap.ticker.lagSmoothing(0);

    // Keep same-page anchor links working through Lenis.
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href^="#"]');
        if (!a) return;
        const target = document.querySelector(a.getAttribute('href'));
        if (!target) return;
        e.preventDefault();
        lenis.scrollTo(target, { offset: -80 });
    });
}

/* ─── entrances: data-gv-reveal ────────────────────────────────────────── */

function initReveals() {
    const els = gsap.utils.toArray('[data-gv-reveal]');
    if (!els.length) return;
    if (reduced) {
        els.forEach((el) => { el.style.opacity = 1; el.style.transform = 'none'; });
        return;
    }
    els.forEach((el) => {
        const kind = el.dataset.gvReveal || 'fade-up';
        const delay = parseFloat(el.dataset.gvDelay || 0);
        const from = { opacity: 0 };
        if (kind === 'fade-up') from.y = motion.distance;
        if (kind === 'scale') from.scale = 0.96;
        gsap.from(el, {
            ...from,
            duration: motion.duration,
            ease: motion.ease,
            delay,
            scrollTrigger: { trigger: el, start: 'top 88%', once: true },
        });
    });
}

/* ─── headlines: data-gv-split (masked line reveal) ────────────────────── */

function initSplits() {
    const els = gsap.utils.toArray('[data-gv-split]');
    if (!els.length || reduced) return;
    els.forEach((el) => {
        const split = new SplitText(el, { type: 'lines', linesClass: 'gv-line' });
        split.lines.forEach((line) => {
            const mask = document.createElement('div');
            mask.style.overflow = 'hidden';
            line.parentNode.insertBefore(mask, line);
            mask.appendChild(line);
        });
        gsap.from(split.lines, {
            yPercent: 110,
            duration: motion.duration * 1.1,
            ease: motion.ease,
            stagger: motion.stagger,
            scrollTrigger: { trigger: el, start: 'top 88%', once: true },
        });
    });
}

/* ─── parallax: data-gv-parallax="0.15" ────────────────────────────────── */

function initParallax() {
    if (reduced) return;
    gsap.utils.toArray('[data-gv-parallax]').forEach((el) => {
        const ratio = parseFloat(el.dataset.gvParallax || 0.15);
        gsap.fromTo(el, { y: () => -ratio * 100 }, {
            y: () => ratio * 100,
            ease: 'none',
            scrollTrigger: { trigger: el.parentElement, start: 'top bottom', end: 'bottom top', scrub: true },
        });
    });
}

/* ─── carousels: data-gv-embla ─────────────────────────────────────────── */

function initEmblas() {
    document.querySelectorAll('[data-gv-embla]').forEach((el) => {
        let opts = { align: 'start', dragFree: true };
        try { opts = { ...opts, ...JSON.parse(el.dataset.gvEmbla || '{}') }; } catch { /* defaults */ }
        EmblaCarousel(el, opts);
    });
}

/* ─── counters: data-gv-counter="42" (suffix stays in markup) ──────────── */

function initCounters() {
    gsap.utils.toArray('[data-gv-counter]').forEach((el) => {
        const target = parseFloat(el.dataset.gvCounter);
        if (Number.isNaN(target)) return;
        if (reduced) { el.textContent = el.dataset.gvCounter; return; }
        const obj = { v: 0 };
        gsap.to(obj, {
            v: target,
            duration: 1.4,
            ease: 'power2.out',
            snap: { v: 1 },
            onUpdate: () => { el.textContent = String(Math.round(obj.v)); },
            scrollTrigger: { trigger: el, start: 'top 92%', once: true },
        });
    });
}

/* ─── cart drawer store + async add-to-cart ────────────────────────────── */

Alpine.store('gvCart', {
    open: false,
    busy: false,
    count: null,
    lines: [],
    subtotal: '',
    total: '',
    flash: '',

    openDrawer() { this.open = true; document.documentElement.classList.add('gv-drawer-open'); lenis?.stop(); },
    closeDrawer() { this.open = false; document.documentElement.classList.remove('gv-drawer-open'); lenis?.start(); },

    apply(state) {
        this.count = state.item_count;
        this.lines = state.lines || [];
        this.subtotal = state.subtotal || '';
        this.total = state.total || '';
        this.flash = state.flash || '';
        // Keep every theme's header badge in sync (.bag .n is the shared hook).
        document.querySelectorAll('.bag .n').forEach((el) => { el.textContent = state.item_count; });
    },

    async add(form) {
        if (this.busy) return;
        this.busy = true;
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                body: new FormData(form),
            });
            if (!res.ok) throw new Error(String(res.status));
            const state = await res.json();
            if (state.ok === false) { this.flash = state.flash || ''; return; }
            this.apply(state);
            this.openDrawer();
        } catch {
            form.submit(); // graceful degrade to the classic redirect flow
        } finally {
            this.busy = false;
        }
    },

    async mutate(url, body) {
        // Shared qty/remove path for drawer rows (PATCH/DELETE via _method).
        if (this.busy) return;
        this.busy = true;
        try {
            const fd = new FormData();
            Object.entries(body).forEach(([k, v]) => fd.set(k, v));
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                body: fd,
            });
            if (res.ok) this.apply(await res.json());
        } finally {
            this.busy = false;
        }
    },
});

/* Quick-view modal store — cards call $store.gvQv.show({...}). */
Alpine.store('gvQv', {
    open: false,
    item: {},
    show(item) { this.item = item || {}; this.open = true; lenis?.stop(); },
    close() { this.open = false; lenis?.start(); },
});

function initAddForms() {
    document.querySelectorAll('form[data-gv-add]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            Alpine.store('gvCart').add(form);
        });
    });
}

/* ─── boot ─────────────────────────────────────────────────────────────── */

window.gv = { gsap, ScrollTrigger, lenis, motion, refresh: () => ScrollTrigger.refresh() };
window.Alpine = Alpine;

document.addEventListener('DOMContentLoaded', () => {
    initReveals();
    initParallax();
    initEmblas();
    initCounters();
    initAddForms();
    Alpine.start();
    // Split AFTER webfonts land — line boxes measured on fallback metrics
    // would mask/break in the wrong places once the real font swaps in.
    (document.fonts?.ready ?? Promise.resolve()).then(initSplits);
    // Fonts/images shift layout as they land — keep triggers honest.
    window.addEventListener('load', () => ScrollTrigger.refresh());
});
