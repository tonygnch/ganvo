@php
    /*
     | Stripe Payment Element + JS confirm flow. Themes include this
     | inside their checkout form (replaces the stub-payment notice
     | when $payment_mode === 'stripe').
     |
     | Required view vars:
     |   $stripe_publishable_key   — pk_test_… / pk_live_… (CheckoutController sets)
     |   $stripe_account_id        — the merchant's acct_… (Connect)
     |
     | The Payment Element mounts AFTER the customer submits the form
     | for the first time (so they don't see a half-mounted UI before
     | filling out address). The JS:
     |   1. Intercepts the form submit.
     |   2. POSTs form data to /checkout — server creates pending
     |      Order + PaymentIntent, returns { client_secret, return_url }.
     |   3. Mounts the Payment Element with the client_secret.
     |   4. Re-submits via Stripe.confirmPayment with return_url so 3DS
     |      redirects come back to /orders/{number}.
     */
@endphp

<div class="sp-pay">
    <div class="sp-pay-info">
        <strong>Secure card payment</strong>
        <span>Powered by Stripe — your card details never touch this server.</span>
    </div>
    {{-- Where Stripe Elements mounts the Payment Element. Hidden
         until the form is otherwise filled out (see JS). --}}
    <div id="sp-payment-element" data-sp-mount></div>
    <div id="sp-payment-error" class="sp-pay-error" data-sp-error hidden></div>
</div>

<style>
    .sp-pay {
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }
    .sp-pay-info {
        display: flex;
        flex-direction: column;
        gap: .125rem;
        padding: .75rem .875rem;
        border-radius: 10px;
        background: rgba(99, 102, 241, .08);
        border: 1px solid rgba(99, 102, 241, .2);
        color: inherit;
        font-size: .8125rem;
    }
    .sp-pay-info strong { font-size: .875rem; }
    .sp-pay-info span { opacity: .75; }
    #sp-payment-element:not([data-mounted]) { display: none; }
    .sp-pay-error {
        padding: .625rem .875rem;
        border-radius: 8px;
        background: rgba(239,68,68,.1);
        border: 1px solid rgba(239,68,68,.3);
        color: #991b1b;
        font-size: .875rem;
    }
</style>

<script src="https://js.stripe.com/v3/" defer></script>
<script>
    (function () {
        var PUBLISHABLE_KEY = @json($stripe_publishable_key);
        var STRIPE_ACCOUNT = @json($stripe_account_id);

        function init() {
            // Stripe.js loads with `defer` so it might not be ready
            // when this script runs; poll briefly.
            if (typeof Stripe === 'undefined') return setTimeout(init, 50);

            var form = document.querySelector('form[action="/checkout"]');
            if (! form) return; // theme without a /checkout form? bail.

            // Tag the form so multi-form pages don't trip us up.
            if (form.__spBound) return;
            form.__spBound = true;

            var stripe = Stripe(PUBLISHABLE_KEY, { stripeAccount: STRIPE_ACCOUNT });
            var elements = null;
            var paymentElement = null;
            var clientSecret = null;
            var returnUrl = null;
            var mountTarget = document.querySelector('[data-sp-mount]');
            var errorEl = document.querySelector('[data-sp-error]');

            function showError(msg) {
                if (! errorEl) return;
                errorEl.textContent = msg;
                errorEl.hidden = false;
            }

            function clearError() {
                if (errorEl) { errorEl.hidden = true; errorEl.textContent = ''; }
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                clearError();

                var submitBtn = form.querySelector('button[type="submit"]');
                var origLabel = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = 'Processing…'; }

                if (clientSecret) {
                    // Second pass: PE already mounted, user clicked
                    // submit again — just confirm.
                    confirm();
                    return;
                }

                // First pass: POST form data, expect JSON back.
                var fd = new FormData(form);
                fetch('/checkout', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                .then(function (r) {
                    return r.json().then(function (body) { return { res: r, body: body }; });
                })
                .then(function (r) {
                    if (! r.res.ok) {
                        // Validation error: show inline + reset
                        var msg = 'Please correct the errors above and try again.';
                        if (r.body && r.body.errors) {
                            var first = Object.values(r.body.errors)[0];
                            if (Array.isArray(first)) msg = first[0];
                        } else if (r.body && r.body.message) {
                            msg = r.body.message;
                        }
                        showError(msg);
                        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origLabel; }
                        return;
                    }

                    clientSecret = r.body.client_secret;
                    returnUrl = r.body.return_url;

                    // Mount the Payment Element now that we have a
                    // client_secret. The Element renders its own card
                    // input + handles future Apple Pay / Google Pay etc.
                    elements = stripe.elements({ clientSecret: clientSecret, appearance: { theme: 'stripe' } });
                    paymentElement = elements.create('payment');
                    paymentElement.mount('#sp-payment-element');
                    mountTarget.setAttribute('data-mounted', '1');

                    // Reset the button so they can complete payment
                    // with the same submit press.
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origLabel.replace('Place order', 'Pay now');
                    }

                    // Tell the customer what's happening.
                    showError('');
                    var notice = document.createElement('div');
                    notice.className = 'sp-pay-info';
                    notice.style.marginBottom = '.5rem';
                    notice.textContent = 'Enter your card details above, then click the button again to pay.';
                    mountTarget.parentNode.insertBefore(notice, mountTarget);

                    // Scroll PE into view so they see it.
                    mountTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                })
                .catch(function () {
                    showError('Network error — please try again.');
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origLabel; }
                });
            });

            function resetButton(submitBtn, label) {
                if (! submitBtn) return;
                submitBtn.disabled = false;
                submitBtn.innerHTML = label || 'Pay now';
            }

            function confirm() {
                clearError();
                var submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = 'Confirming payment…';
                }

                // `redirect: 'if_required'` means: only navigate away
                // when 3DS / off-session auth is actually needed. For
                // succeeded charges (most common), the promise resolves
                // synchronously with the PI object and we redirect to
                // the order page ourselves. Stops the "stuck on
                // Confirming…" bug where Stripe returns success but
                // never navigates.
                stripe.confirmPayment({
                    elements: elements,
                    clientSecret: clientSecret,
                    confirmParams: { return_url: returnUrl },
                    redirect: 'if_required',
                }).then(function (result) {
                    if (! result) {
                        // Defensive: Stripe.js sometimes resolves with
                        // undefined when something weird happens at the
                        // browser layer. Treat as a recoverable error.
                        showError('Payment status unknown — please try again.');
                        resetButton(submitBtn, 'Pay now');
                        return;
                    }

                    if (result.error) {
                        // Card declined / validation / etc. Stripe gives
                        // us a user-friendly message most of the time.
                        showError(result.error.message || 'Payment failed. Please check your card and try again.');
                        resetButton(submitBtn, 'Try again');
                        return;
                    }

                    var pi = result.paymentIntent;
                    if (! pi) {
                        showError('No payment intent returned. Please try again.');
                        resetButton(submitBtn, 'Try again');
                        return;
                    }

                    if (pi.status === 'succeeded' || pi.status === 'processing') {
                        // Hard navigate so the order page loads fresh
                        // + the webhook race-recovery kicks in.
                        window.location.href = returnUrl;
                        return;
                    }

                    if (pi.status === 'requires_action' || pi.status === 'requires_confirmation') {
                        // Stripe is mid-flight (3DS modal, etc.) — when
                        // it's done it'll navigate via return_url, so
                        // leave the button disabled here.
                        return;
                    }

                    // Anything else (requires_payment_method, canceled)
                    // → surface + let them retry.
                    showError('Payment was not completed (status: ' + pi.status + '). Please try again.');
                    resetButton(submitBtn, 'Try again');
                }).catch(function (e) {
                    // Network error / Stripe.js internal failure / etc.
                    showError((e && e.message) ? e.message : 'Something went wrong while contacting your bank. Please try again.');
                    resetButton(submitBtn, 'Try again');
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
