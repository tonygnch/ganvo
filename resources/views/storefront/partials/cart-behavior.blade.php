@php
    /*
     | Shared async-cart behavior. Theme-agnostic: drives quantity +/-,
     | remove, and discount apply/remove via fetch + the JSON returned by
     | CartController, then patches the DOM through the shared number-anim
     | engine (window.ganvoAnimateNumber). Any theme cart that uses the
     | data-* hooks below + includes storefront.partials.number-anim can
     | drop in this partial and get the full no-reload cart for free.
     |
     | Required hooks in the theme's cart markup:
     |   [data-cart-root]            wrapper
     |   [data-cart-line="{id}"]     per line; with [data-line-subtotal],
     |                               [data-line-qty], [data-qty-step] inputs
     |   [data-cart-qty] / [data-cart-remove]   the +/- and remove forms
     |   [data-cart-subtotal] [data-cart-total]
     |   [data-cart-discount-row] (+ -name / -amount)
     |   [data-cart-discount] region (apply/chip/remove + -code/-name/-msg)
     |   .bag .n   header count (in the layout)
     */
@endphp
<script>
    (function () {
        var root = document.querySelector('[data-cart-root]');
        if (! root) return;

        var animate = window.ganvoAnimateNumber || function (el, str) { if (el) el.textContent = str; };

        async function send(form) {
            var res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new FormData(form),
            });
            if (! res.ok) throw new Error('Request failed: ' + res.status);
            return res.json();
        }

        function applyState(s) {
            animate(document.querySelector('.bag .n'), String(s.item_count));
            animate(root.querySelector('[data-cart-subtotal]'), s.subtotal);
            animate(root.querySelector('[data-cart-total]'), s.total);

            var dRow = root.querySelector('[data-cart-discount-row]');
            if (dRow) {
                if (s.discount) {
                    var nm = dRow.querySelector('[data-cart-discount-name]'); if (nm) nm.textContent = s.discount.name;
                    animate(dRow.querySelector('[data-cart-discount-amount]'), s.discount.amount);
                    dRow.hidden = false;
                } else { dRow.hidden = true; }
            }

            (s.lines || []).forEach(function (line) {
                var rowEl = root.querySelector('[data-cart-line="' + line.line_id + '"]');
                if (! rowEl) return;
                animate(rowEl.querySelector('[data-line-subtotal]'), line.subtotal);
                animate(rowEl.querySelector('[data-line-qty]'), String(line.quantity));
                rowEl.querySelectorAll('[data-qty-step]').forEach(function (btn) {
                    var step = parseInt(btn.getAttribute('data-qty-step'), 10);
                    var input = btn.closest('form').querySelector('[data-qty-value]');
                    if (input) input.value = Math.max(0, line.quantity + step);
                });
            });

            if (s.empty) { window.location.reload(); }
        }

        function dropLine(lineId) {
            var rowEl = root.querySelector('[data-cart-line="' + lineId + '"]');
            if (! rowEl) return;
            rowEl.style.transition = 'opacity .2s ease';
            rowEl.style.opacity = '0';
            setTimeout(function () { rowEl.remove(); }, 200);
        }

        root.querySelectorAll('[data-cart-qty]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                send(form).then(function (s) { if (s.line_removed && s.line_id) dropLine(s.line_id); applyState(s); })
                    .catch(function () { form.submit(); });
            });
        });

        root.querySelectorAll('[data-cart-remove]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                send(form).then(function (s) { if (s.line_id) dropLine(s.line_id); applyState(s); })
                    .catch(function () { form.submit(); });
            });
        });

        var region = root.querySelector('[data-cart-discount]');
        if (region) {
            var applyForm = region.querySelector('[data-discount-apply]');
            var chip = region.querySelector('[data-discount-chip]');
            var msg = region.querySelector('[data-discount-msg]');

            function renderDiscount(s) {
                if (s.applied_code) {
                    var c = region.querySelector('[data-discount-code]'); if (c) c.textContent = s.applied_code;
                    var nameEl = region.querySelector('[data-discount-name]'); if (nameEl && s.discount) nameEl.textContent = s.discount.name;
                    if (applyForm) applyForm.hidden = true;
                    if (chip) chip.hidden = false;
                } else {
                    if (applyForm) { applyForm.hidden = false; var inp = applyForm.querySelector('[data-discount-input]'); if (inp) inp.value = ''; }
                    if (chip) chip.hidden = true;
                }
                if (msg) { if (s.flash) { msg.textContent = s.flash; msg.hidden = false; } else { msg.hidden = true; } }
            }

            if (applyForm) applyForm.addEventListener('submit', function (e) {
                e.preventDefault();
                send(applyForm).then(function (s) { applyState(s); renderDiscount(s); }).catch(function () { applyForm.submit(); });
            });
            var removeForm = region.querySelector('[data-discount-remove]');
            if (removeForm) removeForm.addEventListener('submit', function (e) {
                e.preventDefault();
                send(removeForm).then(function (s) { applyState(s); renderDiscount(s); }).catch(function () { removeForm.submit(); });
            });
        }
    })();
</script>
