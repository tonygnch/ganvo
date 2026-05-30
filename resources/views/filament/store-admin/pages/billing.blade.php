<x-filament-panels::page>
    @php
        // Status badge colors stay tinted on both themes — pairs of
        // [fg, bg] designed to be readable on either surface.
        $statusColors = [
            'active'    => ['#10b981', 'rgba(16,185,129,.18)'],
            'trialing'  => ['#22d3ee', 'rgba(34,211,238,.18)'],
            'past_due'  => ['#f59e0b', 'rgba(245,158,11,.22)'],
            'unpaid'    => ['#ef4444', 'rgba(239,68,68,.18)'],
            'incomplete'=> ['#f59e0b', 'rgba(245,158,11,.22)'],
            'canceled'  => ['#94a3b8', 'rgba(148,163,184,.22)'],
        ];
        $statusKey   = $subscription?->stripe_status ?: ($isSubscribed ? 'active' : 'none');
        $statusColor = $statusColors[$statusKey] ?? ['#94a3b8', 'rgba(148,163,184,.22)'];
    @endphp

    <style>
        /* Local theme tokens — swap when Filament toggles .dark on
           <html>. Everything in this page references the vars, so
           switching themes flips the entire UI in one place. */
        .bill-page {
            --bill-surface: white;
            --bill-surface-sunk: rgba(0,0,0,.03);
            --bill-border: rgba(0,0,0,.1);
            --bill-border-strong: rgba(0,0,0,.18);
            --bill-text: rgba(0,0,0,.88);
            --bill-text-muted: rgba(0,0,0,.6);
            --bill-text-soft: rgba(0,0,0,.5);
            --bill-accent: #4f46e5;
            --bill-accent-soft: rgba(99,102,241,.14);
            --bill-accent-fg: #3730a3;
            --bill-success-fg: #047857;
            --bill-success-soft: rgba(16,185,129,.14);
            --bill-success-border: rgba(16,185,129,.32);
            --bill-warning-fg: #b45309;
            --bill-warning-soft: rgba(245,158,11,.14);
            --bill-danger-fg: #b91c1c;
            --bill-danger-soft: rgba(239,68,68,.1);
            --bill-danger-border: rgba(239,68,68,.32);
            --bill-modal-bg: white;
            --bill-modal-border: rgba(0,0,0,.12);
            --bill-modal-text: rgba(0,0,0,.88);
            --bill-modal-sunk: rgba(0,0,0,.04);
        }
        .dark .bill-page {
            --bill-surface: rgba(255,255,255,.04);
            --bill-surface-sunk: rgba(255,255,255,.06);
            --bill-border: rgba(255,255,255,.1);
            --bill-border-strong: rgba(255,255,255,.22);
            --bill-text: rgba(255,255,255,.95);
            --bill-text-muted: rgba(255,255,255,.7);
            --bill-text-soft: rgba(255,255,255,.5);
            --bill-accent: #6366f1;
            --bill-accent-soft: rgba(99,102,241,.22);
            --bill-accent-fg: #c7d2fe;
            --bill-success-fg: #6ee7b7;
            --bill-success-soft: rgba(16,185,129,.18);
            --bill-success-border: rgba(16,185,129,.4);
            --bill-warning-fg: #fde68a;
            --bill-warning-soft: rgba(245,158,11,.18);
            --bill-danger-fg: #fca5a5;
            --bill-danger-soft: rgba(239,68,68,.18);
            --bill-danger-border: rgba(239,68,68,.4);
            --bill-modal-bg: rgb(24,32,52);
            --bill-modal-border: rgba(255,255,255,.1);
            --bill-modal-text: rgb(232,237,255);
            --bill-modal-sunk: rgba(0,0,0,.25);
        }

        .bill-page { color: var(--bill-text); }
        .bill-flash {
            margin: 0 0 1.5rem; padding: 1rem 1.25rem; border-radius: 0.75rem;
            font-size: 0.9375rem;
        }
        .bill-flash-success { background: var(--bill-success-soft); border: 1px solid var(--bill-success-border); color: var(--bill-success-fg); }
        .bill-flash-error   { background: var(--bill-danger-soft);  border: 1px solid var(--bill-danger-border);  color: var(--bill-danger-fg); }

        .bill-card {
            background: var(--bill-surface);
            border: 1px solid var(--bill-border);
            border-radius: 1rem;
            padding: 1.75rem;
            margin: 0 0 2rem;
            color: var(--bill-text);
        }
        .bill-card-row { display: flex; flex-wrap: wrap; gap: 2rem; align-items: flex-start; justify-content: space-between; }
        .bill-card-row > div { flex: 1; min-width: 280px; }
        .bill-card-aside { display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem; }
        .bill-eyebrow {
            font-size: 0.75rem; font-weight: 600; letter-spacing: 0.08em;
            text-transform: uppercase; color: var(--bill-text-soft);
            margin: 0 0 0.5rem;
        }
        .bill-card h2 { font-size: 1.875rem; font-weight: 700; margin: 0 0 0.25rem; color: var(--bill-text); }
        .bill-card .tagline { color: var(--bill-text-muted); margin: 0 0 1rem; font-size: 0.9375rem; }
        .bill-card .price-row { margin: 0; font-size: 0.9375rem; color: var(--bill-text); }
        .bill-card .price-row .big { font-weight: 700; font-size: 1.5rem; }
        .bill-card .price-row .per { color: var(--bill-text-muted); }

        .bill-status {
            display: inline-block; padding: 0.375rem 0.875rem; border-radius: 999px;
            font-size: 0.75rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
            white-space: nowrap;
        }
        .bill-note { font-size: 0.8125rem; color: var(--bill-text-muted); }
        .bill-note-warn { font-size: 0.8125rem; color: var(--bill-warning-fg); }

        .bill-actions {
            margin-top: 1.5rem; padding-top: 1.5rem;
            border-top: 1px solid var(--bill-border);
            display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;
        }

        .bill-section-head { margin: 0 0 1rem; }
        .bill-section-head h2 { font-size: 1.25rem; font-weight: 700; margin: 0 0 0.25rem; color: var(--bill-text); }
        .bill-section-head p { color: var(--bill-text-muted); margin: 0; font-size: 0.9375rem; }

        /* Period toggle */
        .billing-period {
            display: inline-flex; padding: 4px;
            background: var(--bill-surface-sunk);
            border: 1px solid var(--bill-border);
            border-radius: 999px;
            margin: 0 0 1.5rem;
            position: relative;
        }
        .billing-period label {
            padding: 0.5rem 1.25rem; border-radius: 999px;
            font-size: 0.8125rem; font-weight: 600;
            cursor: pointer; color: var(--bill-text-muted);
            transition: background .15s ease, color .15s ease;
        }
        .billing-period input { position: absolute; opacity: 0; pointer-events: none; }
        .billing-period:has(input[value="monthly"]:checked) label[data-period="monthly"],
        .billing-period:has(input[value="yearly"]:checked)  label[data-period="yearly"]  { background: var(--bill-accent); color: white; }

        .plans-monthly, .plans-yearly { display: none; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        body:has(input[name="billing_period_picker"][value="monthly"]:checked) .plans-monthly { display: grid; }
        body:has(input[name="billing_period_picker"][value="yearly"]:checked)  .plans-yearly  { display: grid; }

        /* Plan card */
        .bill-plan {
            background: var(--bill-surface);
            border: 1px solid var(--bill-border);
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex; flex-direction: column; gap: 1rem;
            color: var(--bill-text);
        }
        .bill-plan.is-current { border-color: var(--bill-accent); }
        .bill-plan-head { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
        .bill-plan-head h3 { font-size: 1.125rem; font-weight: 700; margin: 0; }
        .bill-tag {
            padding: 0.125rem 0.5rem; font-size: 0.6875rem; font-weight: 700;
            letter-spacing: 0.06em; text-transform: uppercase; border-radius: 4px;
        }
        .bill-tag-popular { background: var(--bill-accent-soft); color: var(--bill-accent-fg); }
        .bill-tag-current { background: var(--bill-success-soft); color: var(--bill-success-fg); }
        .bill-plan-tagline { margin: 0; font-size: 0.875rem; color: var(--bill-text-muted); }
        .bill-price-strike { margin: 0; font-size: 0.8125rem; color: var(--bill-text-muted); text-decoration: line-through; }
        .bill-price-current { margin: 0; font-size: 1.75rem; font-weight: 700; color: var(--bill-text); }
        .bill-price-per { margin: 0; font-size: 0.8125rem; color: var(--bill-text-muted); }
        .bill-features { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 0.375rem; }
        .bill-feature { font-size: 0.8125rem; color: var(--bill-text); display: flex; gap: 0.5rem; }
        .bill-feature .check { color: var(--bill-success-fg); }

        .bill-plan-btn {
            margin-top: auto; padding: 0.75rem;
            border-radius: 0.5rem; font-weight: 600; cursor: pointer;
            width: 100%; border: 0;
        }
        .bill-plan-btn[disabled] {
            background: var(--bill-surface-sunk);
            color: var(--bill-text-muted);
            cursor: not-allowed;
        }

        /* Swap modal */
        .bill-modal {
            position: fixed; inset: 0; z-index: 9999;
            display: none; align-items: center; justify-content: center;
            background: rgba(0,0,0,.55);
            backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
        }
        .bill-modal-panel {
            background: var(--bill-modal-bg);
            color: var(--bill-modal-text);
            border: 1px solid var(--bill-modal-border);
            border-radius: 12px;
            box-shadow: 0 24px 60px -12px rgba(0,0,0,.6);
            max-width: 480px; width: calc(100% - 2rem); padding: 1.5rem;
        }
        .bill-modal-panel h2 { margin: 0 0 .5rem; font-size: 1.25rem; font-weight: 700; }
        .bill-modal-panel .subtitle { margin: 0 0 1.25rem; font-size: .9375rem; color: var(--bill-text-muted); }
        .dark .bill-modal-panel .subtitle { color: rgba(255,255,255,.65); }
        .bill-modal-lines {
            display: none; margin: 0 0 1.25rem; padding: 1rem;
            background: var(--bill-modal-sunk); border-radius: 8px; font-size: .875rem;
        }
        .bill-modal-total {
            display: none; margin: 0 0 1.25rem; padding: 1rem;
            background: var(--bill-accent-soft);
            border: 1px solid var(--bill-accent-soft);
            border-radius: 8px;
        }
        .bill-modal-error {
            display: none; margin: 0 0 1.25rem; padding: .875rem 1rem;
            background: var(--bill-danger-soft);
            border: 1px solid var(--bill-danger-border);
            border-radius: 8px;
            color: var(--bill-danger-fg);
            font-size: .875rem;
        }
        .bill-modal-actions {
            display: flex; gap: .75rem; justify-content: flex-end; margin-top: 1rem;
        }
    </style>

    <div class="bill-page">
        @if (session('billing_status'))
            <div class="bill-flash bill-flash-success">{{ session('billing_status') }}</div>
        @endif
        @if (session('billing_error'))
            <div class="bill-flash bill-flash-error">{{ session('billing_error') }}</div>
        @endif

        {{-- Current state card --}}
        <div class="bill-card">
            <div class="bill-card-row">
                <div>
                    <p class="bill-eyebrow">{{ __('billing.current_plan') }}</p>
                    <h2>{{ $currentPlan?->translated('name') ?? __('billing.no_plan') }}</h2>
                    @if ($currentPlan)
                        <p class="tagline">{{ $currentPlan->translated('tagline') }}</p>
                        <p class="price-row">
                            @if ($currentPlan->isFree())
                                <span style="font-weight: 600;">{{ __('billing.free') }}</span>
                            @else
                                <span class="big">{{ \App\Services\Money::display($currentPlan->effectivePriceCentsFor($currentPeriod), 1, $currentPlan->currency) }}</span>
                                <span class="per">/ {{ __('billing.period.' . $currentPeriod) }}</span>
                            @endif
                        </p>
                    @endif
                </div>

                <div class="bill-card-aside">
                    <span class="bill-status" style="background: {{ $statusColor[1] }}; color: {{ $statusColor[0] }};">
                        {{ __('billing.status.' . $statusKey, [], null) ?: $statusKey }}
                    </span>
                    @if ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture())
                        <span class="bill-note">{{ __('billing.trial_ends', ['date' => $tenant->trial_ends_at->toFormattedDateString()]) }}</span>
                    @endif
                    @if ($subscription?->ends_at && $subscription->ends_at->isFuture())
                        <span class="bill-note-warn">{{ __('billing.cancels_on', ['date' => $subscription->ends_at->toFormattedDateString()]) }}</span>
                    @endif
                </div>
            </div>

            @if ($tenant->stripe_id)
                <div class="bill-actions">
                    <form method="post" action="{{ route('billing.portal') }}">
                        @csrf
                        <button type="submit" class="fi-btn fi-btn-color-gray fi-btn-size-md fi-color-gray" style="cursor: pointer;">
                            {{ __('billing.open_portal') }} ↗
                        </button>
                    </form>
                    <p class="bill-note" style="margin: 0;">{{ __('billing.portal_hint') }}</p>
                </div>
            @endif
        </div>

        {{-- Plan picker --}}
        <div class="bill-section-head">
            <h2>{{ __('billing.change_plan') }}</h2>
            <p>{{ __('billing.change_plan_hint') }}</p>
        </div>

        <div class="billing-period">
            <label data-period="monthly"><input type="radio" name="billing_period_picker" value="monthly" {{ $currentPeriod === 'monthly' ? 'checked' : '' }}> {{ __('billing.period.monthly') }}</label>
            <label data-period="yearly"><input type="radio" name="billing_period_picker" value="yearly" {{ $currentPeriod === 'yearly' ? 'checked' : '' }}> {{ __('billing.period.yearly') }}</label>
        </div>

        @foreach (['monthly', 'yearly'] as $period)
            <div class="plans-{{ $period }}">
                @foreach ($plans as $plan)
                    @php
                        $isCurrent = $plan->slug === $tenant->subscription_plan && $currentPeriod === $period;
                        $cents = $plan->effectivePriceCentsFor($period);
                    @endphp
                    <div class="bill-plan @if($isCurrent) is-current @endif">
                        <div>
                            <div class="bill-plan-head">
                                <h3>{{ $plan->translated('name') }}</h3>
                                @if ($plan->is_popular)
                                    <span class="bill-tag bill-tag-popular">★</span>
                                @endif
                                @if ($isCurrent)
                                    <span class="bill-tag bill-tag-current">{{ __('billing.current') }}</span>
                                @endif
                            </div>
                            <p class="bill-plan-tagline">{{ $plan->translated('tagline') }}</p>
                        </div>
                        <div>
                            @if ($plan->isFree())
                                <p class="bill-price-current">{{ __('billing.free') }}</p>
                            @else
                                @if ($plan->hasActiveDiscount())
                                    <p class="bill-price-strike">{{ \App\Services\Money::display($plan->priceCentsFor($period), 1, $plan->currency) }}</p>
                                @endif
                                <p class="bill-price-current">{{ \App\Services\Money::display($cents, 1, $plan->currency) }}</p>
                                <p class="bill-price-per">/ {{ __('billing.period.' . $period) }}</p>
                            @endif
                        </div>
                        <ul class="bill-features">
                            @foreach ((array) $plan->translated('features') as $feature)
                                <li class="bill-feature"><span class="check">✓</span>{{ $feature }}</li>
                            @endforeach
                        </ul>
                        @if ($isCurrent)
                            <button type="button" disabled class="bill-plan-btn">{{ __('billing.current_plan') }}</button>
                        @elseif (! $plan->isFree() && ! $plan->stripePriceFor($period))
                            <button type="button" disabled title="{{ __('billing.errors.stripe_price_missing') }}" class="bill-plan-btn">
                                {{ __('billing.unavailable') }}
                            </button>
                        @else
                            @php
                                $action = ($tenant->platformSubscribed() && ! $plan->isFree())
                                    ? route('billing.swap')
                                    : route('billing.checkout');
                            @endphp
                            <form method="post" action="{{ $action }}" style="margin-top: auto;"
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

        {{-- Plan-swap confirmation modal --}}
        <div id="swapModal" class="bill-modal" role="dialog" aria-modal="true" aria-hidden="true">
            <div class="bill-modal-panel">
                <div id="swapModalContent">
                    <h2 id="swapModalTitle">{{ __('billing.preview.loading_title') }}</h2>
                    <p id="swapModalSubtitle" class="subtitle">{{ __('billing.preview.loading_body') }}</p>
                    <div id="swapModalLines" class="bill-modal-lines"></div>
                    <div id="swapModalTotal" class="bill-modal-total"></div>
                    <div id="swapModalError" class="bill-modal-error" role="alert"></div>
                </div>
                <div class="bill-modal-actions">
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
    </div>{{-- /.bill-page --}}

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

            // Reads from CSS custom properties so the modal honors theme.
            function tokenColor(name, fallback) {
                const v = getComputedStyle(document.querySelector('.bill-page'))
                    .getPropertyValue(name);
                return (v && v.trim()) || fallback;
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

                if (p.lines && p.lines.length) {
                    const muted = tokenColor('--bill-text-muted', 'rgba(0,0,0,.6)');
                    const text  = tokenColor('--bill-text', 'rgba(0,0,0,.88)');
                    const positive = tokenColor('--bill-success-fg', '#047857');
                    linesEl.innerHTML = p.lines.map(line => {
                        const isNegative = line.amount_cents < 0;
                        const color = isNegative ? positive : text;
                        return `
                            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: .375rem 0;">
                                <span style="color: ${muted}; flex: 1;">${escapeHtml(line.description)}</span>
                                <span style="font-variant-numeric: tabular-nums; color: ${color}; font-weight: 600;">${escapeHtml(line.formatted)}</span>
                            </div>
                        `;
                    }).join('');
                    linesEl.style.display = 'block';
                }

                const muted = tokenColor('--bill-text-muted', 'rgba(0,0,0,.6)');
                const text  = tokenColor('--bill-text', 'rgba(0,0,0,.88)');
                const positive = tokenColor('--bill-success-fg', '#047857');
                totalEl.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 1rem;">
                        <span style="font-weight: 600; font-size: .875rem; text-transform: uppercase; letter-spacing: .04em; color: ${muted};">Total today</span>
                        <span style="font-size: 1.5rem; font-weight: 800; font-variant-numeric: tabular-nums; color: ${p.is_charge ? text : positive};">
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

            document.querySelectorAll('form[data-swap-form="1"]').forEach(form => {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    openModal(form);
                });
            });

            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
            });

            confirmBtn.addEventListener('click', () => {
                if (! pendingForm || confirmBtn.disabled) return;
                pendingForm.submit();
            });
        })();
    </script>
</x-filament-panels::page>
