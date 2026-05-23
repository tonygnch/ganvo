<x-filament-panels::page>
    @php
        $statusColors = [
            'active'    => ['#10b981', '#064e3b'],
            'trialing'  => ['#22d3ee', '#155e75'],
            'past_due'  => ['#f59e0b', '#78350f'],
            'unpaid'    => ['#ef4444', '#7f1d1d'],
            'incomplete'=> ['#f59e0b', '#78350f'],
            'canceled'  => ['#94a3b8', '#334155'],
        ];
        $statusKey   = $subscription?->stripe_status ?: ($isSubscribed ? 'active' : 'none');
        $statusColor = $statusColors[$statusKey] ?? ['#94a3b8', '#334155'];
    @endphp

    @if (session('billing_status'))
        <div style="margin: 0 0 1.5rem; padding: 1rem 1.25rem; border-radius: 0.75rem; background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.35); color: rgb(110,231,183); font-size: 0.9375rem;">
            {{ session('billing_status') }}
        </div>
    @endif
    @if (session('billing_error'))
        <div style="margin: 0 0 1.5rem; padding: 1rem 1.25rem; border-radius: 0.75rem; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.35); color: rgb(252,165,165); font-size: 0.9375rem;">
            {{ session('billing_error') }}
        </div>
    @endif

    {{-- Current state card --}}
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 1rem; padding: 1.75rem; margin: 0 0 2rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 2rem; align-items: flex-start; justify-content: space-between;">
            <div style="flex: 1; min-width: 280px;">
                <p style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: rgb(148,163,184); margin: 0 0 0.5rem;">
                    {{ __('billing.current_plan') }}
                </p>
                <h2 style="font-size: 1.875rem; font-weight: 700; margin: 0 0 0.25rem;">
                    {{ $currentPlan?->translated('name') ?? __('billing.no_plan') }}
                </h2>
                @if ($currentPlan)
                    <p style="color: rgb(148,163,184); margin: 0 0 1rem; font-size: 0.9375rem;">
                        {{ $currentPlan->translated('tagline') }}
                    </p>
                    <p style="margin: 0; font-size: 0.9375rem;">
                        @if ($currentPlan->isFree())
                            <span style="font-weight: 600;">{{ __('billing.free') }}</span>
                        @else
                            <span style="font-weight: 700; font-size: 1.5rem;">{{ \App\Services\Money::display($currentPlan->effectivePriceCentsFor($currentPeriod), 1, $currentPlan->currency) }}</span>
                            <span style="color: rgb(148,163,184);">/ {{ __('billing.period.' . $currentPeriod) }}</span>
                        @endif
                    </p>
                @endif
            </div>

            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem;">
                <span style="display: inline-block; padding: 0.375rem 0.875rem; border-radius: 999px; background: {{ $statusColor[1] }}; color: {{ $statusColor[0] }}; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;">
                    {{ __('billing.status.' . $statusKey, [], null) ?: $statusKey }}
                </span>
                @if ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture())
                    <span style="font-size: 0.8125rem; color: rgb(148,163,184);">
                        {{ __('billing.trial_ends', ['date' => $tenant->trial_ends_at->toFormattedDateString()]) }}
                    </span>
                @endif
                @if ($subscription?->ends_at && $subscription->ends_at->isFuture())
                    <span style="font-size: 0.8125rem; color: rgb(252,211,77);">
                        {{ __('billing.cancels_on', ['date' => $subscription->ends_at->toFormattedDateString()]) }}
                    </span>
                @endif
            </div>
        </div>

        @if ($tenant->stripe_id)
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.08); display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <form method="post" action="{{ route('billing.portal') }}">
                    @csrf
                    <button type="submit" class="fi-btn fi-btn-color-gray fi-btn-size-md fi-color-gray" style="cursor: pointer;">
                        {{ __('billing.open_portal') }} ↗
                    </button>
                </form>
                <p style="margin: 0; font-size: 0.8125rem; color: rgb(148,163,184); align-self: center;">
                    {{ __('billing.portal_hint') }}
                </p>
            </div>
        @endif
    </div>

    {{-- Plan picker --}}
    <div style="margin: 0 0 1rem;">
        <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 0.25rem;">{{ __('billing.change_plan') }}</h2>
        <p style="color: rgb(148,163,184); margin: 0; font-size: 0.9375rem;">{{ __('billing.change_plan_hint') }}</p>
    </div>

    {{-- Period toggle. Pure-CSS via :has(:checked). --}}
    <style>
        .billing-period { display: inline-flex; padding: 4px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 999px; margin: 0 0 1.5rem; }
        .billing-period label { padding: 0.5rem 1.25rem; border-radius: 999px; font-size: 0.8125rem; font-weight: 600; cursor: pointer; color: rgb(148,163,184); transition: all .15s ease; }
        .billing-period input { position: absolute; opacity: 0; pointer-events: none; }
        .billing-period:has(input[value="monthly"]:checked) label[data-period="monthly"],
        .billing-period:has(input[value="yearly"]:checked) label[data-period="yearly"] { background: rgb(99,102,241); color: white; }
        .plans-monthly, .plans-yearly { display: none; }
        body:has(input[name="billing_period_picker"][value="monthly"]:checked) .plans-monthly { display: grid; }
        body:has(input[name="billing_period_picker"][value="yearly"]:checked) .plans-yearly { display: grid; }
    </style>

    <div class="billing-period">
        <label data-period="monthly"><input type="radio" name="billing_period_picker" value="monthly" {{ $currentPeriod === 'monthly' ? 'checked' : '' }}> {{ __('billing.period.monthly') }}</label>
        <label data-period="yearly"><input type="radio" name="billing_period_picker" value="yearly" {{ $currentPeriod === 'yearly' ? 'checked' : '' }}> {{ __('billing.period.yearly') }}</label>
    </div>

    @foreach (['monthly', 'yearly'] as $period)
        <div class="plans-{{ $period }}" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
            @foreach ($plans as $plan)
                @php
                    $isCurrent = $plan->slug === $tenant->subscription_plan && $currentPeriod === $period;
                    $cents = $plan->effectivePriceCentsFor($period);
                @endphp
                <div style="background: rgba(255,255,255,0.03); border: 1px solid {{ $isCurrent ? 'rgb(99,102,241)' : 'rgba(255,255,255,0.08)' }}; border-radius: 1rem; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0;">{{ $plan->translated('name') }}</h3>
                            @if ($plan->is_popular)
                                <span style="padding: 0.125rem 0.5rem; background: rgba(99,102,241,0.2); color: rgb(165,180,252); font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; border-radius: 4px;">★</span>
                            @endif
                            @if ($isCurrent)
                                <span style="padding: 0.125rem 0.5rem; background: rgba(16,185,129,0.2); color: rgb(110,231,183); font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; border-radius: 4px;">{{ __('billing.current') }}</span>
                            @endif
                        </div>
                        <p style="margin: 0; font-size: 0.875rem; color: rgb(148,163,184);">{{ $plan->translated('tagline') }}</p>
                    </div>
                    <div>
                        @if ($plan->isFree())
                            <p style="margin: 0; font-size: 1.5rem; font-weight: 700;">{{ __('billing.free') }}</p>
                        @else
                            @if ($plan->hasActiveDiscount())
                                <p style="margin: 0; font-size: 0.8125rem; color: rgb(148,163,184); text-decoration: line-through;">{{ \App\Services\Money::display($plan->priceCentsFor($period), 1, $plan->currency) }}</p>
                            @endif
                            <p style="margin: 0; font-size: 1.75rem; font-weight: 700;">{{ \App\Services\Money::display($cents, 1, $plan->currency) }}</p>
                            <p style="margin: 0; font-size: 0.8125rem; color: rgb(148,163,184);">/ {{ __('billing.period.' . $period) }}</p>
                        @endif
                    </div>
                    <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 0.375rem;">
                        @foreach ((array) $plan->translated('features') as $feature)
                            <li style="font-size: 0.8125rem; color: rgb(203,213,225); display: flex; gap: 0.5rem;"><span style="color: rgb(110,231,183);">✓</span>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    @if ($isCurrent)
                        <button type="button" disabled style="margin-top: auto; padding: 0.75rem; background: rgba(255,255,255,0.06); color: rgb(148,163,184); border: 0; border-radius: 0.5rem; font-weight: 600; cursor: not-allowed;">
                            {{ __('billing.current_plan') }}
                        </button>
                    @elseif (! $plan->isFree() && ! $plan->stripePriceFor($period))
                        <button type="button" disabled title="{{ __('billing.errors.stripe_price_missing') }}" style="margin-top: auto; padding: 0.75rem; background: rgba(255,255,255,0.06); color: rgb(148,163,184); border: 0; border-radius: 0.5rem; font-weight: 600; cursor: not-allowed;">
                            {{ __('billing.unavailable') }}
                        </button>
                    @else
                        {{-- Already-subscribed merchants change plans via /billing/swap
                             (Stripe proration), first-time subscribers go through
                             /billing/checkout (Stripe Checkout creates the
                             subscription). Free-plan downgrade routes through
                             checkout, which detects + cancels the existing sub.
                             onsubmit confirm shows the proration warning so the
                             merchant knows they'll be charged/credited the
                             prorated difference today. --}}
                        @php
                            // Two paths:
                            //  - First-time subscribe / cancel-to-free → posts
                            //    direct to /billing/checkout (no preview modal;
                            //    they'll see Stripe Checkout's own UI).
                            //  - Switch plan while subscribed → posts to
                            //    /billing/swap AFTER the modal confirms.
                            //    The button is marked with data-swap-* attrs;
                            //    JS at the bottom of the page intercepts the
                            //    submit, fetches the preview, shows the modal.
                            $isSwap = $tenant->platformSubscribed() && (! $plan->isFree() || $tenant->platformSubscribed());
                            $action = ($tenant->platformSubscribed() && ! $plan->isFree())
                                ? route('billing.swap')
                                : route('billing.checkout');
                        @endphp
                        <form method="post"
                              action="{{ $action }}"
                              style="margin-top: auto;"
                              @if ($tenant->platformSubscribed()) data-swap-form="1" @endif>
                            @csrf
                            <input type="hidden" name="plan_slug" value="{{ $plan->slug }}">
                            <input type="hidden" name="period" value="{{ $period }}">
                            <button type="submit"
                                    class="fi-btn fi-btn-color-primary fi-btn-size-md fi-color-primary"
                                    style="width: 100%; cursor: pointer;"
                                    data-plan-label="{{ $plan->translated('name') }}"
                                    data-period="{{ $period }}"
                                    data-is-free="{{ $plan->isFree() ? '1' : '0' }}">
                                @if ($plan->isFree())
                                    {{ __('billing.switch_to_free') }}
                                @elseif ($tenant->platformSubscribed())
                                    {{ __('billing.switch_plan') }}
                                @else
                                    {{ __('billing.subscribe') }}
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach

    {{-- Plan-swap confirmation modal. Hidden by default; JS shows it
         once Stripe returns the preview total. Pre-populates with skeleton
         text while loading, then fills in real numbers. --}}
    <div id="swapModal" role="dialog" aria-modal="true" aria-hidden="true"
         style="position: fixed; inset: 0; z-index: 9999; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,.55); backdrop-filter: blur(4px);">
        <div style="background: rgb(24,32,52); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; box-shadow: 0 24px 60px -12px rgba(0,0,0,.6); max-width: 480px; width: calc(100% - 2rem); padding: 1.5rem; color: rgb(232,237,255);">
            <div id="swapModalContent">
                <h2 id="swapModalTitle" style="margin: 0 0 .5rem; font-size: 1.25rem; font-weight: 700;">
                    {{ __('billing.preview.loading_title') }}
                </h2>
                <p id="swapModalSubtitle" style="margin: 0 0 1.25rem; color: rgb(148,163,184); font-size: .9375rem;">
                    {{ __('billing.preview.loading_body') }}
                </p>

                <div id="swapModalLines" style="display: none; margin: 0 0 1.25rem; padding: 1rem; background: rgba(0,0,0,.25); border-radius: 8px; font-size: .875rem;">
                    {{-- populated by JS --}}
                </div>

                <div id="swapModalTotal" style="display: none; margin: 0 0 1.25rem; padding: 1rem; background: rgba(0,212,255,.08); border: 1px solid rgba(0,212,255,.25); border-radius: 8px;">
                    {{-- populated by JS --}}
                </div>

                <div id="swapModalError" role="alert" style="display: none; margin: 0 0 1.25rem; padding: .875rem 1rem; background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.35); border-radius: 8px; color: rgb(252,165,165); font-size: .875rem;">
                    {{-- error text populated by JS --}}
                </div>
            </div>

            <div style="display: flex; gap: .75rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" id="swapModalCancel"
                        class="fi-btn fi-btn-color-gray fi-btn-size-md fi-color-gray"
                        style="cursor: pointer;">
                    {{ __('billing.preview.cancel') }}
                </button>
                <button type="button" id="swapModalConfirm" disabled
                        class="fi-btn fi-btn-color-primary fi-btn-size-md fi-color-primary"
                        style="cursor: pointer; opacity: .5;">
                    {{ __('billing.preview.confirm') }}
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const previewUrl = @json(route('billing.swap.preview'));
            const csrfToken = @json(csrf_token());
            const i18n = {
                loadingTitle: @json(__('billing.preview.loading_title')),
                loadingBody:  @json(__('billing.preview.loading_body')),
                chargeTitle:  @json(__('billing.preview.charge_title')),
                chargeBody:   @json(__('billing.preview.charge_body')),
                creditTitle:  @json(__('billing.preview.credit_title')),
                creditBody:   @json(__('billing.preview.credit_body')),
                cancelTitle:  @json(__('billing.preview.cancel_title')),
                cancelBody:   @json(__('billing.preview.cancel_body_with_date')),
                cancelBodyNoDate: @json(__('billing.preview.cancel_body_no_date')),
                alreadyTitle: @json(__('billing.preview.already_on_plan_title')),
                noChange:     @json(__('billing.preview.no_change')),
                confirmLabel: @json(__('billing.preview.confirm')),
            };

            const modal = document.getElementById('swapModal');
            const title = document.getElementById('swapModalTitle');
            const subtitle = document.getElementById('swapModalSubtitle');
            const linesEl = document.getElementById('swapModalLines');
            const totalEl = document.getElementById('swapModalTotal');
            const errorEl = document.getElementById('swapModalError');
            const cancelBtn = document.getElementById('swapModalCancel');
            const confirmBtn = document.getElementById('swapModalConfirm');

            let pendingForm = null;

            function openModal(form) {
                pendingForm = form;
                // Reset to loading state
                title.textContent = i18n.loadingTitle;
                subtitle.textContent = i18n.loadingBody;
                linesEl.style.display = 'none';
                linesEl.innerHTML = '';
                totalEl.style.display = 'none';
                totalEl.innerHTML = '';
                errorEl.style.display = 'none';
                confirmBtn.disabled = true;
                confirmBtn.style.opacity = '.5';
                modal.style.display = 'flex';
                modal.setAttribute('aria-hidden', 'false');

                // Fetch preview
                const data = new FormData(form);
                fetch(previewUrl, {
                    method: 'POST',
                    body: data,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }).then(r => r.json().then(b => ({ status: r.status, body: b })))
                  .then(({ status, body }) => {
                      if (status >= 200 && status < 300 && body.ok) {
                          renderPreview(body);
                      } else {
                          showError(body.message || 'Couldn’t fetch preview.');
                      }
                  })
                  .catch(() => showError('Network error fetching preview.'));
            }

            function renderPreview(p) {
                if (p.already_on_plan) {
                    title.textContent = i18n.alreadyTitle;
                    subtitle.textContent = p.message || i18n.noChange;
                    confirmBtn.disabled = true;
                    confirmBtn.style.opacity = '.4';
                    return;
                }
                if (p.is_cancel) {
                    title.textContent = i18n.cancelTitle;
                    const body = p.end_date
                        ? i18n.cancelBody.replace(':date', p.end_date)
                        : i18n.cancelBodyNoDate;
                    subtitle.textContent = body;
                    confirmBtn.disabled = false;
                    confirmBtn.style.opacity = '1';
                    confirmBtn.textContent = i18n.confirmLabel;
                    return;
                }

                if (p.is_charge) {
                    title.textContent = i18n.chargeTitle.replace(':total', p.total_formatted);
                    subtitle.textContent = i18n.chargeBody;
                } else {
                    title.textContent = i18n.creditTitle.replace(':total', p.total_formatted);
                    subtitle.textContent = i18n.creditBody;
                }

                // Render line items
                if (p.lines && p.lines.length) {
                    linesEl.innerHTML = p.lines.map(line => {
                        const isNegative = line.amount_cents < 0;
                        const color = isNegative ? 'rgb(110,231,183)' : 'rgb(232,237,255)';
                        return `
                            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: .375rem 0;">
                                <span style="color: rgb(148,163,184); flex: 1;">${escapeHtml(line.description)}</span>
                                <span style="font-variant-numeric: tabular-nums; color: ${color}; font-weight: 600;">${escapeHtml(line.formatted)}</span>
                            </div>
                        `;
                    }).join('');
                    linesEl.style.display = 'block';
                }

                totalEl.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 1rem;">
                        <span style="font-weight: 600; font-size: .875rem; text-transform: uppercase; letter-spacing: .04em; color: rgb(148,163,184);">Total today</span>
                        <span style="font-size: 1.5rem; font-weight: 800; font-variant-numeric: tabular-nums; color: ${p.is_charge ? 'rgb(232,237,255)' : 'rgb(110,231,183)'};">
                            ${p.is_charge ? '' : '+'}${escapeHtml(p.total_formatted)}
                        </span>
                    </div>
                `;
                totalEl.style.display = 'block';

                confirmBtn.disabled = false;
                confirmBtn.style.opacity = '1';
                confirmBtn.textContent = i18n.confirmLabel;
            }

            function showError(msg) {
                title.textContent = 'Couldn’t load preview';
                subtitle.textContent = '';
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
                confirmBtn.disabled = true;
                confirmBtn.style.opacity = '.4';
            }

            function closeModal() {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                pendingForm = null;
            }

            function escapeHtml(s) {
                return String(s ?? '').replace(/[&<>"']/g, c => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                }[c]));
            }

            // Wire every form marked data-swap-form="1" to intercept submit
            document.querySelectorAll('form[data-swap-form="1"]').forEach(form => {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    openModal(form);
                });
            });

            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal(); // backdrop click
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
            });

            confirmBtn.addEventListener('click', () => {
                if (! pendingForm || confirmBtn.disabled) return;
                // Submit the original form for real (action = /billing/swap)
                pendingForm.submit();
            });
        })();
    </script>
</x-filament-panels::page>
