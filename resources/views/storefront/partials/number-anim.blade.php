@php
    /*
     | Shared number-change animation engine.
     |
     | Exposes a single global: window.ganvoAnimateNumber(el, targetStr).
     | It animates `el` from its current displayed value to `targetStr`
     | using the merchant's chosen style (Store::numberAnimation()):
     |   count | odometer | flip | fade | none
     | The resting frame always snaps to the EXACT target string, so the
     | value is never wrong even mid-animation. Honors prefers-reduced-motion.
     |
     | Any theme/page that wants animated numbers includes this once, then
     | calls window.ganvoAnimateNumber(el, str) instead of el.textContent = str.
     | Callers should null-check defensively isn't required — the function
     | no-ops on a null element.
     |
     | Requires $store in scope (for the configured style).
     */
@endphp
@once
    <style>
        /* Targets the engine touches across cart + checkout. tabular-nums
           keeps digit width fixed so values don't wobble while rolling. */
        [data-cart-subtotal], [data-cart-total], [data-cart-discount-amount],
        [data-line-subtotal], [data-line-qty],
        [data-sm-grand], [data-sm-shipping], .bag .n {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum";
            transition: color .2s ease;
        }
        .num-flash { color: var(--accent, var(--primary, currentColor)) !important; }

        /* flip needs a transformable box */
        .anim-move { display: inline-block; }

        /* odometer: digit reels inside an inline-flex track */
        .odo-track {
            display: inline-flex;
            align-items: center;
            line-height: 1;
            font-variant-numeric: lining-nums tabular-nums;
            font-feature-settings: "lnum" 1, "tnum" 1;
        }
        .odo-reel { display: inline-block; height: 1.15em; overflow: hidden; }
        .odo-col { display: block; }
        .odo-d, .odo-c {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 1.15em;
            line-height: 1;
        }
        .odo-c { display: inline-flex; }
    </style>

    <script>
        (function () {
            if (window.ganvoAnimateNumber) return;

            var STYLE = @json($store->numberAnimation());
            var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduceMotion) STYLE = 'none';

            // Pull a number + its surrounding template out of a formatted
            // string like "€144.00", "−€14.40", "1 234,00 лв." or "3".
            function parseNum(str) {
                str = String(str);
                var m = str.match(/[\d][\d.,\s ]*\d|\d/);
                if (! m) return null;
                var core = m[0];
                var at = str.indexOf(core);
                var prefix = str.slice(0, at);
                var suffix = str.slice(at + core.length);
                var decSep = null, decimals = 0;
                var dm = core.match(/[.,](\d{1,2})$/);
                if (dm) { decSep = core.charAt(core.length - dm[1].length - 1); decimals = dm[1].length; }
                var intRaw = decSep ? core.slice(0, core.length - dm[1].length - 1) : core;
                var gsm = intRaw.match(/[^\d]/);
                var groupSep = gsm ? gsm[0] : '';
                var clean = intRaw.replace(/[^\d]/g, '') + (decSep ? '.' + core.slice(-decimals) : '');
                var value = parseFloat(clean);
                if (isNaN(value)) return null;
                return { value: value, prefix: prefix, suffix: suffix, decimals: decimals, decSep: decSep || '.', groupSep: groupSep };
            }

            function formatNum(value, tpl) {
                var fixed = Math.abs(value).toFixed(tpl.decimals);
                var parts = fixed.split('.');
                var intPart = parts[0];
                if (tpl.groupSep) intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, tpl.groupSep);
                var body = intPart + (tpl.decimals ? tpl.decSep + (parts[1] || '') : '');
                return tpl.prefix + body + tpl.suffix;
            }

            function flash(el) {
                if (! el) return;
                el.classList.add('num-flash');
                setTimeout(function () { el.classList.remove('num-flash'); }, 360);
            }

            function animCount(el, targetStr) {
                var to = parseNum(targetStr);
                var from = parseNum(el.textContent);
                if (! to || ! from || from.value === to.value) { el.textContent = targetStr; return; }
                var a = from.value, b = to.value, start = null, dur = 450;
                function step(ts) {
                    if (start === null) start = ts;
                    var t = Math.min(1, (ts - start) / dur);
                    var eased = 1 - Math.pow(1 - t, 3);
                    if (t < 1) { el.textContent = formatNum(a + (b - a) * eased, to); requestAnimationFrame(step); }
                    else { el.textContent = targetStr; }
                }
                requestAnimationFrame(step);
            }

            function animFade(el, targetStr) {
                el.style.transition = 'opacity .13s ease';
                el.style.opacity = '0';
                setTimeout(function () { el.textContent = targetStr; el.style.opacity = '1'; }, 130);
            }

            function animFlip(el, targetStr) {
                el.classList.add('anim-move');
                el.style.transition = 'transform .16s ease, opacity .16s ease';
                el.style.transform = 'translateY(-45%)';
                el.style.opacity = '0';
                setTimeout(function () {
                    el.textContent = targetStr;
                    el.style.transition = 'none';
                    el.style.transform = 'translateY(45%)';
                    void el.offsetWidth;
                    el.style.transition = 'transform .24s cubic-bezier(.19,.7,.16,1), opacity .24s ease';
                    el.style.transform = 'translateY(0)';
                    el.style.opacity = '1';
                }, 160);
            }

            function animOdometer(el, targetStr) {
                var prev = el.getAttribute('data-odo') || el.textContent.trim();
                el.setAttribute('data-odo', targetStr);
                el.classList.add('odo');
                el.textContent = '';
                var track = document.createElement('span');
                track.className = 'odo-track';
                el.appendChild(track);
                var chars = targetStr.split('');
                for (var i = 0; i < chars.length; i++) {
                    var ch = chars[i];
                    if (/\d/.test(ch)) {
                        var fromRight = chars.length - 1 - i;
                        var pIdx = prev.length - 1 - fromRight;
                        var prevCh = (pIdx >= 0) ? prev.charAt(pIdx) : null;
                        var startDigit = (prevCh && /\d/.test(prevCh)) ? parseInt(prevCh, 10) : parseInt(ch, 10);
                        var reel = document.createElement('span'); reel.className = 'odo-reel';
                        var col = document.createElement('span'); col.className = 'odo-col';
                        for (var d = 0; d <= 9; d++) {
                            var dg = document.createElement('span'); dg.className = 'odo-d'; dg.textContent = d;
                            col.appendChild(dg);
                        }
                        reel.appendChild(col);
                        track.appendChild(reel);
                        col.style.transform = 'translateY(' + (-startDigit * 10) + '%)';
                        col.style.transition = 'none';
                        (function (col, ch) {
                            requestAnimationFrame(function () { requestAnimationFrame(function () {
                                col.style.transition = 'transform .6s cubic-bezier(.19,.7,.16,1)';
                                col.style.transform = 'translateY(' + (-parseInt(ch, 10) * 10) + '%)';
                            }); });
                        })(col, ch);
                    } else {
                        var s = document.createElement('span'); s.className = 'odo-c'; s.textContent = ch;
                        track.appendChild(s);
                    }
                }
            }

            window.ganvoAnimateNumber = function (el, targetStr) {
                if (! el) return;
                targetStr = String(targetStr);
                // After an odometer render el.textContent is stacked-digit soup,
                // so trust data-odo for the current value.
                var current = el.getAttribute('data-odo') || el.textContent;
                if (current.trim() === targetStr.trim()) return; // unchanged
                flash(el);
                if (STYLE === 'none') { el.textContent = targetStr; return; }
                if (STYLE === 'odometer') return animOdometer(el, targetStr);
                if (STYLE === 'flip')     return animFlip(el, targetStr);
                if (STYLE === 'fade')     return animFade(el, targetStr);
                return animCount(el, targetStr);
            };
        })();
    </script>
@endonce
