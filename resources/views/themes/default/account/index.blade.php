@php
    $title = __('site.common.my_account');
@endphp
@extends('themes.default.layout')

@section('content')
    <style>
        .acct {
            max-width: 880px;
            margin: 0 auto;
            padding: 56px 1.75rem 96px;
        }

        .acct-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1rem;
            flex-wrap: wrap;
            padding-bottom: 24px;
            margin-bottom: 32px;
            border-bottom: 1px solid var(--line);
        }
        .acct-head .eyebrow {
            font-size: 11px;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 12px;
        }
        .acct-head h1 {
            font-family: var(--display);
            font-size: clamp(32px, 4.5vw, 46px);
            font-weight: 500;
            line-height: 1.02;
        }
        .acct-head p {
            color: var(--muted);
            font-size: 14px;
            margin-top: 8px;
        }
        .logout-btn {
            background: transparent;
            color: var(--ink);
            border: 1px solid var(--ink);
            padding: 11px 22px;
            font-size: 11px;
            letter-spacing: .16em;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--body);
            transition: background-color .2s ease, color .2s ease;
            white-space: nowrap;
        }
        .logout-btn:hover { background: var(--ink); color: var(--paper); }
        .acct-head-actions { display: flex; align-items: center; gap: 18px; }
        .settings-link {
            font-size: 11px;
            letter-spacing: .16em;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--ink);
            border-bottom: 1px solid currentColor;
            padding-bottom: 1px;
            transition: color .15s ease;
            white-space: nowrap;
        }
        .settings-link:hover { color: var(--accent); }

        .acct-section-title {
            font-size: 11px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 700;
            margin-bottom: 18px;
        }

        /* ----- orders list ----- */
        .orders { border: 1px solid var(--line); background: var(--paper); }
        .order-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1.5rem;
            align-items: center;
            padding: 20px 24px;
            border-top: 1px solid var(--line);
            transition: background-color .15s ease;
        }
        .order-row:first-child { border-top: 0; }
        .order-row:hover { background: color-mix(in srgb, var(--ink) 3%, var(--paper)); }
        .order-row .num {
            font-family: "Hanken Grotesk", monospace;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: .04em;
            color: var(--ink);
        }
        .order-row .date {
            color: var(--muted);
            font-size: 13px;
            margin-top: 4px;
        }
        .order-row .date .amount { color: var(--ink-soft, #4f4a40); font-weight: 500; }

        .ord-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .ord-status.paid     { background: color-mix(in srgb, #16a34a 16%, var(--paper)); color: #15803d; }
        .ord-status.shipped  { background: color-mix(in srgb, var(--ink) 8%, var(--paper)); color: var(--ink); }
        .ord-status.pending  { background: color-mix(in srgb, #d97706 16%, var(--paper)); color: #b45309; }
        .ord-status.failed,
        .ord-status.cancelled,
        .ord-status.refunded { background: color-mix(in srgb, #b91c1c 12%, var(--paper)); color: #b91c1c; }

        .order-row .view {
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--ink);
            border-bottom: 1px solid currentColor;
            padding-bottom: 1px;
            transition: color .15s ease;
            white-space: nowrap;
        }
        .order-row .view:hover { color: var(--accent); }

        .acct-empty {
            border: 1px solid var(--line);
            background: var(--paper);
            text-align: center;
            padding: 64px 24px;
            color: var(--muted);
            font-size: 14px;
        }
        .acct-empty .btn { margin-top: 20px; }

        @media (max-width: 600px) {
            .order-row { grid-template-columns: 1fr auto; row-gap: 10px; }
            .order-row .view { grid-column: 1 / -1; }
        }
    </style>

    <main>
        <div class="acct">
            <div class="acct-head rv">
                <div>
                    <div class="eyebrow">{{ __('site.common.my_account') }}</div>
                    <h1>{{ __('site.account.hi', ['name' => explode(' ', $customer->name)[0]]) }}</h1>
                    <p>{{ __('site.account.signed_in_as', ['email' => $customer->email]) }}</p>
                </div>
                <div class="acct-head-actions">
                    <a href="/account/settings" class="settings-link">{{ __('site.account.settings') }}</a>
                    <form method="post" action="/account/logout">
                        @csrf
                        <button type="submit" class="logout-btn">{{ __('site.account.sign_out') }}</button>
                    </form>
                </div>
            </div>

            <div class="acct-section-title rv">{{ __('site.account.recent_orders') }}</div>

            @if ($orders->isEmpty())
                <div class="acct-empty rv">
                    <div>{{ __('site.account.empty') }}</div>
                    <a href="/" class="btn">{{ __('site.account.start_shopping') }}</a>
                </div>
            @else
                <div class="orders rv">
                    @foreach ($orders as $order)
                        <div class="order-row">
                            <div>
                                <div class="num">{{ $order->order_number }}</div>
                                <div class="date">
                                    {{ $order->created_at->isoFormat('LL') }}
                                    · <span class="amount">{{ \App\Services\Money::format($order->total_cents, $order->currency) }}</span>
                                </div>
                            </div>
                            <span class="ord-status {{ $order->status }}">{{ __('site.statuses.' . $order->status) }}</span>
                            <a href="/orders/{{ $order->order_number }}" class="view">{{ __('site.account.view') }} →</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </main>
@endsection
