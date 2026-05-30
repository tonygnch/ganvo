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
            'not_connected'  => ['label' => 'Not connected', 'color' => '#6b7280', 'bg' => 'rgba(107,114,128,.12)'],
            'onboarding'     => ['label' => 'Onboarding in progress', 'color' => '#b45309', 'bg' => 'rgba(245,158,11,.15)'],
            'pending_review' => ['label' => 'Pending review', 'color' => '#1d4ed8', 'bg' => 'rgba(59,130,246,.15)'],
            'active'         => ['label' => 'Active', 'color' => '#047857', 'bg' => 'rgba(16,185,129,.15)'],
            'restricted'     => ['label' => 'Restricted', 'color' => '#b91c1c', 'bg' => 'rgba(239,68,68,.15)'],
        ];
        $badge = $statusBadges[$status];
    @endphp

    <style>
        .pay-card {
            padding: 1.25rem;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 12px;
            background: white;
            margin-bottom: 1rem;
        }
        .dark .pay-card { background: rgba(255,255,255,.03); border-color: rgba(255,255,255,.08); }
        .pay-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .75rem;
            flex-wrap: wrap;
        }
        .pay-card-head h3 { margin: 0; font-size: 1rem; font-weight: 700; }

        .pay-badge {
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            padding: .25rem .625rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .02em;
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
        .pay-meta-label { font-size: .6875rem; text-transform: uppercase; letter-spacing: .08em; color: rgba(0,0,0,.55); font-weight: 600; margin: 0 0 .125rem; }
        .dark .pay-meta-label { color: rgba(255,255,255,.5); }
        .pay-meta-value { font-size: .9375rem; font-weight: 500; }
        .pay-meta-value code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8125rem; }

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
            color: inherit;
            text-decoration: none;
            transition: background-color .12s ease, border-color .12s ease;
        }
        .pay-btn-primary {
            background: rgb(var(--primary-600, 79 70 229));
            color: white;
            border-color: rgb(var(--primary-600, 79 70 229));
        }
        .pay-btn-primary:hover { background: rgb(var(--primary-700, 67 56 202)); border-color: rgb(var(--primary-700, 67 56 202)); }
        .pay-btn-secondary {
            border-color: rgba(0,0,0,.15);
            color: rgba(0,0,0,.75);
        }
        .pay-btn-secondary:hover { background: rgba(0,0,0,.05); }
        .dark .pay-btn-secondary { border-color: rgba(255,255,255,.18); color: rgba(255,255,255,.8); }
        .dark .pay-btn-secondary:hover { background: rgba(255,255,255,.06); }
        .pay-btn-danger {
            border-color: rgba(239,68,68,.4);
            color: #dc2626;
        }
        .pay-btn-danger:hover { background: rgba(239,68,68,.08); }
        .pay-btn[disabled] { opacity: .5; cursor: not-allowed; }

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
    </style>

    {{-- ============ Connect status card ============ --}}
    <div class="pay-card">
        <div class="pay-card-head">
            <h3>Stripe Connect</h3>
            <span class="pay-badge" style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }}">
                {{ $badge['label'] }}
            </span>
        </div>

        @if ($status === 'not_connected')
            <p style="margin:0;color:rgba(0,0,0,.65);">
                Your storefront is in <strong>demo payment mode</strong> — orders go through without charging real cards.
                To start accepting real payments, set up Ganvo Payments below. Takes about 5 minutes.
            </p>

        @elseif ($status === 'onboarding')
            <p style="margin:0;color:rgba(0,0,0,.65);">
                You started setting up payments but haven't finished. Pick up where you left off — Stripe remembers what you've already filled in.
            </p>

        @elseif ($status === 'pending_review')
            <p style="margin:0;color:rgba(0,0,0,.65);">
                You've submitted all your info. Stripe is reviewing your account — this usually takes a few minutes but can take up to 1–2 business days in rare cases.
                You'll get an email when it's ready, or you can check back here and click <em>Refresh status</em>.
            </p>

        @elseif ($status === 'active')
            <p style="margin:0;color:rgba(0,0,0,.65);">
                Your storefront is accepting real card payments. Manage payouts, view transactions, and handle any disputes from your Stripe Express dashboard.
            </p>

        @elseif ($status === 'restricted')
            <p style="margin:0;color:rgba(0,0,0,.65);">
                Stripe has temporarily disabled charges on your account. Open the Stripe dashboard to resolve the issue (usually missing info or verification).
            </p>
            <div class="pay-warning">
                <div>
                    <strong>Reason:</strong> {{ $tenant->stripe_connect_disabled_reason ?: 'See Stripe dashboard for details.' }}
                </div>
            </div>
        @endif

        @if ($tenant->hasConnect())
            <div class="pay-grid">
                <div>
                    <p class="pay-meta-label">Account ID</p>
                    <p class="pay-meta-value"><code>{{ $tenant->stripe_account_id }}</code></p>
                </div>
                <div>
                    <p class="pay-meta-label">Type</p>
                    <p class="pay-meta-value">{{ ucfirst($tenant->stripe_connect_account_type ?? 'express') }}</p>
                </div>
                <div>
                    <p class="pay-meta-label">Charges</p>
                    <p class="pay-meta-value">{{ $tenant->stripe_connect_charges_enabled ? '✓ Enabled' : '— Not yet' }}</p>
                </div>
                <div>
                    <p class="pay-meta-label">Payouts</p>
                    <p class="pay-meta-value">{{ $tenant->stripe_connect_payouts_enabled ? '✓ Enabled' : '— Not yet' }}</p>
                </div>
            </div>
        @endif

        <div class="pay-actions">
            @if ($status === 'not_connected')
                <form method="post" action="{{ route('store.payments.connect.express') }}">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-primary">
                        Set up Ganvo Payments →
                    </button>
                </form>
                <button type="button" class="pay-btn pay-btn-secondary" disabled title="Coming soon">
                    Connect your own Stripe account
                </button>

            @elseif ($status === 'onboarding')
                <form method="post" action="{{ route('store.payments.connect.express') }}">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-primary">Continue setup →</button>
                </form>
                <form method="post" action="{{ route('store.payments.sync') }}">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-secondary">Refresh status</button>
                </form>
                <form method="post" action="{{ route('store.payments.disconnect') }}"
                      onsubmit="return confirm('Disconnect this Stripe account? You can re-connect later.');">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-danger">Disconnect</button>
                </form>

            @elseif ($status === 'pending_review')
                <form method="post" action="{{ route('store.payments.sync') }}">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-primary">Refresh status</button>
                </form>
                <form method="post" action="{{ route('store.payments.dashboard') }}" target="_blank">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-secondary">Open Stripe dashboard ↗</button>
                </form>

            @elseif ($status === 'active' || $status === 'restricted')
                <form method="post" action="{{ route('store.payments.dashboard') }}" target="_blank">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-primary">Open Stripe dashboard ↗</button>
                </form>
                <form method="post" action="{{ route('store.payments.sync') }}">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-secondary">Refresh status</button>
                </form>
                <form method="post" action="{{ route('store.payments.disconnect') }}"
                      onsubmit="return confirm('Disconnect Stripe? Your storefront will revert to demo payment mode immediately.');">
                    @csrf
                    <button type="submit" class="pay-btn pay-btn-danger">Disconnect</button>
                </form>
            @endif
        </div>
    </div>

    {{-- ============ Platform fee summary ============ --}}
    <div class="pay-card">
        <div class="pay-card-head">
            <h3>Platform fee</h3>
            <span class="pay-badge" style="background:rgba(99,102,241,.12);color:#4338ca">{{ $feeRate }}</span>
        </div>
        <p style="margin:0;color:rgba(0,0,0,.65);">
            Ganvo's fee on every storefront transaction.
            @if ($feeBps === 0)
                You're currently on <strong>no fee</strong> — Ganvo doesn't take a cut of your sales right now.
            @else
                Ganvo collects {{ $feeRate }} of every successful charge ({{ $feeBps }} basis points). The remainder lands in your Stripe balance and is paid out by Stripe on your normal schedule.
            @endif
        </p>
    </div>
</x-filament-panels::page>
