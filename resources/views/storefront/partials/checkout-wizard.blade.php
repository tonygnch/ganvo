@php
    /*
     | Shared multi-step checkout wizard behavior. Theme-agnostic — operates
     | on .wz-* hooks any theme's checkout markup provides:
     |   form[data-wizard]            the checkout form
     |   .wz-steps [data-go-step]     stepper items
     |   .wz-step[data-step]          step sections (steps 2+ start hidden)
     |   [data-wz-prev]               back button (hidden on step 1)
     |   [data-wz-primary]            single submit button; morphs label via
     |                                data-continue-label / data-pay-label,
     |                                price wrapper [data-wz-amount]
     | Wizard intercepts submit in CAPTURE phase so on steps < last it
     | validates + advances + stops propagation (so the Stripe partial's
     | bubble-phase submit handler never fires); on the last step it lets
     | the submit through. Form must add .wz-on for the CSS to switch in.
     */
@endphp
<script>
    (function () {
        var form = document.querySelector('form[data-wizard]');
        if (! form) return;
        var steps = Array.prototype.slice.call(form.querySelectorAll('.wz-step'));
        if (steps.length < 2) return;

        var stepperItems = Array.prototype.slice.call(form.querySelectorAll('.wz-steps [data-go-step]'));
        var backBtn = form.querySelector('[data-wz-prev]');
        var primaryBtn = form.querySelector('[data-wz-primary]');
        var primaryLabel = form.querySelector('[data-wz-label]');
        var payLabel = primaryBtn ? primaryBtn.getAttribute('data-pay-label') : '';
        var continueLabel = primaryBtn ? primaryBtn.getAttribute('data-continue-label') : '';

        var current = 1, furthest = 1, last = steps.length;
        form.classList.add('wz-on');

        function fieldsIn(step) {
            return Array.prototype.slice.call(step.querySelectorAll('input, select, textarea'))
                .filter(function (el) { return el.type !== 'hidden' && ! el.disabled; });
        }
        function stepValid(step) {
            var f = fieldsIn(step);
            for (var i = 0; i < f.length; i++) { if (! f[i].checkValidity()) { f[i].reportValidity(); return false; } }
            return true;
        }
        function render() {
            steps.forEach(function (s) {
                var n = parseInt(s.getAttribute('data-step'), 10);
                var on = (n === current);
                s.classList.toggle('is-current', on);
                s.hidden = ! on;
            });
            stepperItems.forEach(function (li) {
                var n = parseInt(li.getAttribute('data-go-step'), 10);
                li.classList.toggle('is-current', n === current);
                li.classList.toggle('is-done', n < current || (n <= furthest && n !== current));
            });
            if (backBtn) backBtn.hidden = (current === 1);
            if (primaryLabel) primaryLabel.textContent = (current < last) ? continueLabel : payLabel;
            var amount = primaryBtn ? primaryBtn.querySelector('[data-wz-amount]') : null;
            if (amount) amount.style.display = (current < last) ? 'none' : '';
        }
        function go(n) {
            current = Math.max(1, Math.min(last, n));
            if (current > furthest) furthest = current;
            render();
            var top = form.getBoundingClientRect().top + window.pageYOffset - 90;
            window.scrollTo({ top: top, behavior: 'smooth' });
        }
        function advance() { if (stepValid(steps[current - 1])) go(current + 1); }

        form.addEventListener('submit', function (e) {
            if (current < last) { e.preventDefault(); e.stopPropagation(); advance(); }
        }, true);
        form.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && current < last && e.target.tagName !== 'TEXTAREA') { e.preventDefault(); advance(); }
        });
        if (backBtn) backBtn.addEventListener('click', function () { go(current - 1); });
        stepperItems.forEach(function (li) {
            li.addEventListener('click', function () {
                var n = parseInt(li.getAttribute('data-go-step'), 10);
                if (n < current) go(n);
            });
        });
        render();
    })();
</script>
