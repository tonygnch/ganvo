<x-filament-panels::page>
    @php
        /*
         * State-driven UI. $status comes from Payments::status() — one of:
         *   not_connected | onboarding | pending_review | active | restricted
         *
         * Every action button is a tiny <form> that posts to a route
         * on PaymentsController; we don't use Livewire actions here so
         * we keep behavior identical to redirects from Stripe (which
         * also land on plain GETs).
         */
        $statusBadges = [
            'not_connected'  => ['label' => __('admin.payments.status.not_connected'), 'color' => '#6b7280', 'bg' => 'rgba(107,114,128,.12)'],
            'onboarding'     => ['label' => __('admin.payments.status.onboarding'), 'color' => '#b45309', 'bg' => 'rgba(245,158,11,.15)'],
            'pending_review' => ['label' => __('admin.payments.status.pending_review'), 'color' => '#1d4ed8', 'bg' => 'rgba(59,130,246,.15)'],
            'active'         => ['label' => __('admin.payments.status.active'), 'color' => '#047857', 'bg' => 'rgba(16,185,129,.15)'],
            'restricted'     => ['label' => __('admin.payments.status.restricted'), 'color' => '#b91c1c', 'bg' => 'rgba(239,68,68,.15)'],
        ];
        $badge = $statusBadges[$status];
    @endphp

    <style>
        /* Local design tokens that swap when .dark is present on
           an ancestor (Filament toggles `.dark` on <html>). Everything
           inside the page leans on these — no more hard-coded white
           backgrounds or rgba(0,0,0,…) text colors that disappear on
           dark theme. */
        .pay-page {
            --pay-surface: white;
            --pay-border: rgba(0,0,0,.08);
            --pay-border-strong: rgba(0,0,0,.18);
            --pay-text: rgba(0,0,0,.85);
            --pay-text-muted: rgba(0,0,0,.65);
            --pay-text-soft: rgba(0,0,0,.5);
            --pay-hover: rgba(0,0,0,.06);
            --pay-code-bg: rgba(0,0,0,.05);
        }
        .dark .pay-page {
            --pay-surface: rgba(255,255,255,.04);
            --pay-border: rgba(255,255,255,.1);
            --pay-border-strong: rgba(255,255,255,.22);
            --pay-text: rgba(255,255,255,.92);
            --pay-text-muted: rgba(255,255,255,.7);
            --pay-text-soft: rgba(255,255,255,.5);
            --pay-hover: rgba(255,255,255,.08);
            --pay-code-bg: rgba(255,255,255,.08);
        }

        .pay-card {
            padding: 1.25rem;
            border: 1px solid var(--pay-border);
            border-radius: 12px;
            background: var(--pay-surface);
            color: var(--pay-text);
            margin-bottom: 1rem;
        }
        .pay-card p { color: var(--pay-text-muted); margin: 0; }
        .pay-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .75rem;
            flex-wrap: wrap;
        }
        .pay-card-head h3 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--pay-text); }

        .pay-badge {
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            padding: .25rem .625rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .02em;
            white-space: nowrap;
        }
        .pay-badge::before {
            content: '';
            display: inline-block;
            width: 6px; height: 6px;
            border-radius: 50%;
            background: currentColor;
        }
        .pay-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: .75rem 1.25rem;
            margin: .75rem 0 0;
        }
        .pay-meta-label {
            font-size: .6875rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--pay-text-soft);
            font-weight: 600;
            margin: 0 0 .125rem;
        }
        .pay-meta-value { font-size: .9375rem; font-weight: 500; color: var(--pay-text); margin: 0; }
        .pay-meta-value code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .8125rem;
            background: var(--pay-code-bg);
            padding: .125rem .375rem;
            border-radius: 4px;
        }

        .pay-actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: 1rem; }
        .pay-btn {
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            padding: .5rem 1rem;
            border-radius: 8px;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            background: transparent;
            color: var(--pay-text);
            text-decoration: none;
            transition: background-color .12s ease, border-color .12s ease, color .12s ease;
        }
        /* Primary uses solid indigo on both themes — high contrast
           against the surface in either direction. */
        .pay-btn-primary {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
        }
        .pay-btn-primary:hover:not(:disabled) { background: #4338ca; border-color: #4338ca; }
        .pay-btn-secondary {
            border-color: var(--pay-border-strong);
            color: var(--pay-text);
            background: transparent;
        }
        .pay-btn-secondary:hover:not(:disabled) { background: var(--pay-hover); }
        .pay-btn-danger {
            border-color: rgba(239,68,68,.5);
            color: #dc2626;
            background: transparent;
        }
        .dark .pay-btn-danger { color: #fca5a5; }
        .pay-btn-danger:hover:not(:disabled) { background: rgba(239,68,68,.1); }
        .pay-btn[disabled] {
            opacity: .55;
            cursor: not-allowed;
            /* Keep the border visible even when disabled so the seam
               for "Connect your own Stripe account" doesn't vanish
               into the card background. */
            border-color: var(--pay-border-strong);
            color: var(--pay-text-muted);
        }

        .pay-warning {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: .875rem 1rem;
            border-radius: 10px;
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.3);
            color: #991b1b;
            margin-top: .75rem;
        }
        .dark .pay-warning { color: #fca5a5; background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.4); }

        /* Platform fee chip — neutral indigo tint that reads on both themes. */
        .pay-badge-fee { background: rgba(99,102,241,.16); color: #4338ca; }
        .dark .pay-badge-fee { background: rgba(99,102,241,.22); color: #c7d2fe; }
    </style>

    {{-- Wrap everything in .pay-page so the CSS-variable scope works
         and Filament's .dark class on <html> can flip the tokens. --}}
    <div class="pay-page">

    {{-- ============ Connect status card ============ --}}
    <div class="pay-card">
        <div class="pay-card-head">
            <h3>Stripe Connect</h3>
            <span class="pay-badge" style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }}">
                {{ $badge['label'] }}
            </span>
        </div>

        @if ($status === 'not_connected')
            <p>
                {{ __('admin.payments.text.not_connected_before') }} <strong>{{ __('admin.payments.text.demo_mode') }}</strong>
                {{ __('admin.payments.text.not_connected_after') }}
            </p>

        @elseif ($status === 'onboarding')
            <p>
                {{ __('admin.payments.text.onboarding') }}
            </p>

        @elseif ($status === 'pending_review')
            <p>
                {{ __('admin.payments.text.pending_review') }} <em>{{ __('admin.payments.action.refresh') }}</em>.
            </p>

        @elseif ($status === 'active')
            <p>
                {{ __('admin.payments.text.active') }}
            </p>

        @elseif ($status === 'restricted')
            <p>
                {{ __('admin.payments.text.restricted') }}
            </p>
            <div class="pay-warning">
                <div>
                    <strong>{{ __('admin.payments.field.reason') }}:</strong> {{ $tenant->stripe_connect_disabled_reason ?: __('admin.payments.text.reason_fallback') }}
                </div>
            </div>
        @endif

        @if ($tenant->hasConnect())
            <div class="pay-grid">
                <div>
                    <p class="pay-meta-label">{{ __('admin.payments.field.account_id') }}</p>
                    <p class="pay-meta-value"><code>{{ $tenant->stripe_account_id }}</code></p>
                </div>
                <div>
                    <p class="pay-meta-label">{{ __('admin.payments.field.account_type') }}</p>
                    <p class="pay-meta-value">{{ ucfirst($tenant->stripe_connect_account_type ?? 'express') }}</p>
                </div>
                <div>
                    <p class="pay-meta-label">{{ __('admin.payments.field.charges') }}</p>
                    <p class="pay-meta-value">{{ $tenant->stripe_connect_charges_enabled ? __('admin.payments.status.enabled') : __('admin.payments.status.not_yet') }}</p>
                </div>
                <div>
                    <p class="pay-meta-label">{{ __('admin.payments.field.payouts') }}</p>
                    <p class="pay-meta-value">{{ $tenant->stripe_connect_payouts_enabled ? __('admin.payments.status.enabled') : __('admin.payments.status.not_yet') }}</p>
                </div>
            </div>
        @endif

        <div class="pay-actions">
            @if ($status === 'not_connected')
                <form method="post" action="{{ route('store.payments.connect.express') }}">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-primary">
                        {{ __('admin.payments.action.setup') }} →
                    </button>
                </form>
                <button type="button" class="pay-btn pay-btn-secondary" disabled title="{{ __('admin.payments.help.coming_soon') }}">
                    {{ __('admin.payments.action.connect_own') }}
                </button>

            @elseif ($status === 'onboarding')
                <form method="post" action="{{ route('store.payments.connect.express') }}">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-primary">{{ __('admin.payments.action.continue_setup') }} →</button>
                </form>
                <form method="post" action="{{ route('store.payments.sync') }}">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-secondary">{{ __('admin.payments.action.refresh') }}</button>
                </form>
                <form method="post" action="{{ route('store.payments.disconnect') }}"
                      onsubmit="return confirm('{{ __('admin.payments.notify.confirm_disconnect') }}');">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-danger">{{ __('admin.payments.action.disconnect') }}</button>
                </form>

            @elseif ($status === 'pending_review')
                <form method="post" action="{{ route('store.payments.sync') }}">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-primary">{{ __('admin.payments.action.refresh') }}</button>
                </form>
                <form method="post" action="{{ route('store.payments.dashboard') }}" target="_blank">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-secondary">{{ __('admin.payments.action.dashboard') }} ↗</button>
                </form>

            @elseif ($status === 'active' || $status === 'restricted')
                <form method="post" action="{{ route('store.payments.dashboard') }}" target="_blank">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-primary">{{ __('admin.payments.action.dashboard') }} ↗</button>
                </form>
                <form method="post" action="{{ route('store.payments.sync') }}">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-secondary">{{ __('admin.payments.action.refresh') }}</button>
                </form>
                <form method="post" action="{{ route('store.payments.disconnect') }}"
                      onsubmit="return confirm('{{ __('admin.payments.notify.confirm_disconnect_active') }}');">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-danger">{{ __('admin.payments.action.disconnect') }}</button>
                </form>
            @endif
        </div>
    </div>

    {{-- ============ Wallets (Apple Pay / Google Pay / Link) ============ --}}
    @if ($walletStatus !== null)
        <div class="pay-card">
            <div class="pay-card-head">
                <h3>{{ __('admin.payments.section.wallets') }}</h3>
                @php
                    $allActive = (
                        ($walletStatus['apple_pay'] ?? null) === 'active'
                        && ($walletStatus['google_pay'] ?? null) === 'active'
                    );
                @endphp
                @if (! empty($walletStatus['domain']))
                    <span class="pay-badge"
                          style="background: {{ $allActive ? 'rgba(16,185,129,.15)' : 'rgba(245,158,11,.15)' }};
                                 color: {{ $allActive ? '#047857' : '#b45309' }};">
                        {{ $allActive ? __('admin.payments.status.wallets_active') : __('admin.payments.status.needs_verification') }}
                    </span>
                @else
                    <span class="pay-badge" style="background: rgba(107,114,128,.12); color: #6b7280;">
                        {{ __('admin.payments.status.not_registered') }}
                    </span>
                @endif
            </div>

            @if (empty($walletStatus['domain']))
                <p>
                    {{ __('admin.payments.text.wallets_unregistered_before') }}
                    <em>{{ __('admin.payments.action.register') }}</em> {{ __('admin.payments.text.wallets_unregistered_after') }}
                </p>
            @else
                <p>
                    {{ __('admin.payments.text.wallets_registered') }} <code style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.8125rem; background:var(--pay-code-bg); padding:.125rem .375rem; border-radius:4px;">{{ $walletStatus['domain'] }}</code>.
                </p>

                <div class="pay-grid">
                    @foreach (['apple_pay' => 'Apple Pay', 'google_pay' => 'Google Pay', 'link' => 'Link'] as $key => $label)
                        @php $status = $walletStatus[$key] ?? null; @endphp
                        <div>
                            <p class="pay-meta-label">{{ $label }}</p>
                            <p class="pay-meta-value">
                                @if ($status === 'active')
                                    <span style="color:#047857;">{{ __('admin.payments.status.wallet_on') }}</span>
                                @elseif ($status === 'inactive')
                                    <span style="color:#b91c1c;">{{ __('admin.payments.status.wallet_off') }}</span>
                                @else
                                    <span style="color:var(--pay-text-soft);">{{ $status ?? '—' }}</span>
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>

                @if (! empty($walletStatus['apple_pay_error']))
                    <div class="pay-warning" style="margin-top:.875rem;">
                        <div><strong>Apple Pay:</strong> {{ $walletStatus['apple_pay_error'] }}</div>
                    </div>
                @endif
            @endif

            <div class="pay-actions">
                <form method="post" action="{{ route('store.payments.wallets.register') }}">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-primary">
                        {{ empty($walletStatus['domain']) ? __('admin.payments.action.register') : __('admin.payments.action.reverify') }}
                    </button>
                </form>
                <p style="margin:0; align-self:center; color:var(--pay-text-soft); font-size:.8125rem;">
                    {{ __('admin.payments.help.apple_pay') }}
                </p>
            </div>
        </div>
    @endif

    {{-- ============ Platform fee summary ============ --}}
    <div class="pay-card">
        <div class="pay-card-head">
            <h3>{{ __('admin.payments.section.platform_fee') }}</h3>
            <span class="pay-badge pay-badge-fee">{{ $feeRate }}</span>
        </div>
        <p>
            {{ __('admin.payments.text.fee_intro') }}
            @if ($feeBps === 0)
                {{ __('admin.payments.text.fee_none_before') }} <strong>{{ __('admin.payments.text.fee_none') }}</strong>
                {{ __('admin.payments.text.fee_none_after') }}
            @else
                {{ __('admin.payments.text.fee_detail', ['rate' => $feeRate, 'bps' => $feeBps]) }}
            @endif
        </p>
    </div>
    </div>{{-- /.pay-page --}}
</x-filament-panels::page>
