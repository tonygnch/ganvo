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
    /* Hide the error box when it's hidden via attribute OR empty.
       Theme CSS sometimes overrides the [hidden] attribute with a
       display:flex on the parent, leaving the empty box visible as a
       thin red border line at the bottom of the payment section. The
       :empty match catches that case + protects against any "show but
       no message" code path. !important so it wins over theme cascade. */
    .sp-pay-error[hidden],
    .sp-pay-error:empty { display: none !important; }
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
                    elements = stripe.elements({
                        clientSecret: clientSecret,
                        appearance: { theme: 'stripe' },
                    });
                    // Surface Apple Pay / Google Pay / Link buttons at
                    // the top of the Payment Element when the customer's
                    // browser supports them + Stripe has the storefront
                    // domain verified. 'auto' = Stripe decides per-render.
                    paymentElement = elements.create('payment', {
                        wallets: {
                            applePay: 'auto',
                            googlePay: 'auto',
                        },
                    });
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

            // Manual escape hatch: if Stripe.js misbehaves, surface a
            // visible "View your order →" link so the customer can
            // navigate themselves. The order page reconciles the PI
            // state from Stripe directly on load, so they're not
            // stuck even if the JS hangs.
            function showManualFallback(message) {
                var box = document.createElement('div');
                box.className = 'sp-pay-info';
                box.style.marginTop = '.5rem';
                box.innerHTML =
                    (message || 'Taking longer than expected.') +
                    ' <a href="' + returnUrl + '" style="color:inherit;font-weight:700;text-decoration:underline">' +
                    'View your order →</a>';
                if (errorEl && errorEl.parentNode) {
                    errorEl.parentNode.insertBefore(box, errorEl.nextSibling);
                }
            }

            function confirm() {
                clearError();
                var submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = 'Confirming payment…';
                }

                // Safety-net timeout: if neither the Stripe promise
                // resolves nor a redirect happens within 15s, surface
                // a manual "View your order" link so the customer
                // isn't stranded staring at "Confirming payment…".
                var stuckTimer = setTimeout(function () {
                    console.warn('[stripe-payment] confirm hung for 15s — showing manual fallback');
                    showManualFallback('Still working on it.');
                }, 15000);

                console.log('[stripe-payment] calling elements.submit() then stripe.confirmPayment', { returnUrl: returnUrl });

                // Newer Stripe.js requires elements.submit() FIRST — it
                // locks the Payment Element's collected data + runs
                // client-side validation. Without it confirmPayment
                // throws "elements.submit() must be called before
                // stripe.confirmPayment()". Chain it into confirmPayment
                // so a validation error short-circuits before we hit
                // the network.
                elements.submit().then(function (submitResult) {
                    if (submitResult && submitResult.error) {
                        clearTimeout(stuckTimer);
                        console.warn('[stripe-payment] elements.submit error', submitResult.error);
                        showError(submitResult.error.message || 'Please check your card details.');
                        resetButton(submitBtn, 'Try again');
                        return null; // skip confirmPayment
                    }
                    // `redirect: 'if_required'` only navigates when
                    // 3DS / off-session auth is needed. For succeeded
                    // charges the promise resolves synchronously and
                    // we redirect ourselves.
                    return stripe.confirmPayment({
                        elements: elements,
                        clientSecret: clientSecret,
                        confirmParams: { return_url: returnUrl },
                        redirect: 'if_required',
                    });
                }).then(function (result) {
                    if (result === null) return; // elements.submit() errored

                    clearTimeout(stuckTimer);
                    console.log('[stripe-payment] confirmPayment resolved', result);

                    if (! result) {
                        showError('Payment status unknown — please try again.');
                        resetButton(submitBtn, 'Pay now');
                        showManualFallback();
                        return;
                    }

                    if (result.error) {
                        showError(result.error.message || 'Payment failed. Please check your card and try again.');
                        resetButton(submitBtn, 'Try again');
                        return;
                    }

                    var pi = result.paymentIntent;
                    if (! pi) {
                        showError('No payment intent returned. Please try again.');
                        resetButton(submitBtn, 'Try again');
                        showManualFallback();
                        return;
                    }

                    console.log('[stripe-payment] PI status:', pi.status);

                    if (pi.status === 'succeeded' || pi.status === 'processing') {
                        console.log('[stripe-payment] navigating to', returnUrl);
                        window.location.href = returnUrl;
                        // Belt + braces — if navigation doesn't happen
                        // for any reason, show the manual link after 2s.
                        setTimeout(function () { showManualFallback('Payment confirmed!'); }, 2000);
                        return;
                    }

                    if (pi.status === 'requires_action' || pi.status === 'requires_confirmation') {
                        // Stripe is mid-flight (3DS modal, etc.) — when
                        // it's done it'll navigate via return_url, so
                        // leave the button disabled here.
                        return;
                    }

                    showError('Payment was not completed (status: ' + pi.status + '). Please try again.');
                    resetButton(submitBtn, 'Try again');
                }).catch(function (e) {
                    clearTimeout(stuckTimer);
                    console.error('[stripe-payment] confirmPayment threw', e);
                    showError((e && e.message) ? e.message : 'Something went wrong while contacting your bank. Please try again.');
                    resetButton(submitBtn, 'Try again');
                    showManualFallback();
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
