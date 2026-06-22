@php
    $title = __('site.common.my_account');
@endphp
@extends('themes.brick.layout')

@section('content')
    <style>
        .acct { max-width: 900px; margin: 0 auto; padding: 36px 1.5rem 80px; }
        .acct-head { border: 2.5px solid var(--ink); box-shadow: var(--pop); background: var(--accent); padding: 28px 30px; margin-bottom: 32px; display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; }
        .acct-head .eyebrow { font-family: var(--display); font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 10px; }
        .acct-head h1 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(30px, 4.5vw, 50px); line-height: .92; letter-spacing: -.02em; }
        .acct-head p { font-size: 14px; font-weight: 600; margin-top: 8px; }
        .acct-head-actions { display: flex; align-items: center; gap: 12px; }
        .settings-link { font-family: var(--display); font-size: 11px; font-weight: 700; text-transform: uppercase; border: 2.5px solid var(--ink); background: var(--paper); padding: 9px 14px; box-shadow: var(--pop-sm); }
        .settings-link:hover { background: var(--ink); color: var(--accent); }
        .logout-btn { font-family: var(--display); font-size: 11px; font-weight: 700; text-transform: uppercase; border: 2.5px solid var(--ink); background: var(--ink); color: var(--paper); padding: 10px 18px; box-shadow: var(--pop-sm); cursor: pointer; }
        .logout-btn:hover { background: var(--paper); color: var(--ink); }

        .acct-section-title { font-family: var(--display); font-size: 12px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 16px; }

        .orders { border: 2.5px solid var(--ink); box-shadow: var(--pop); background: var(--paper); }
        .order-row { display: grid; grid-template-columns: 1fr auto auto; gap: 1.5rem; align-items: center; padding: 18px 22px; border-top: 2.5px solid var(--ink); }
        .order-row:first-child { border-top: 0; }
        .order-row:hover { background: var(--soft); }
        .order-row .num { font-family: var(--display); font-weight: 800; font-size: 15px; }
        .order-row .date { color: var(--muted); font-size: 13px; margin-top: 4px; font-weight: 600; }
        .ord-status { display: inline-block; font-family: var(--display); padding: 5px 11px; border: 2px solid var(--ink); font-size: 10px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
        .ord-status.paid { background: #b6f5b6; }
        .ord-status.shipped { background: var(--soft); }
        .ord-status.pending { background: var(--accent); }
        .ord-status.failed, .ord-status.cancelled, .ord-status.refunded { background: #f5b6b6; }
        .order-row .view { font-family: var(--display); font-size: 11px; font-weight: 700; text-transform: uppercase; border: 2px solid var(--ink); padding: 7px 11px; white-space: nowrap; }
        .order-row .view:hover { background: var(--accent); }

        .acct-empty { border: 2.5px solid var(--ink); box-shadow: var(--pop); text-align: center; padding: 60px 24px; font-family: var(--display); font-weight: 700; text-transform: uppercase; }
        .acct-empty .btn { margin-top: 20px; }

        @media (max-width: 600px) { .order-row { grid-template-columns: 1fr auto; row-gap: 10px; } .order-row .view { grid-column: 1 / -1; } }
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
                    <a href="/" class="btn accent">{{ __('site.account.start_shopping') }}</a>
                </div>
            @else
                <div class="orders rv">
                    @foreach ($orders as $order)
                        <div class="order-row">
                            <div>
                                <div class="num">{{ $order->order_number }}</div>
                                <div class="date">{{ $order->created_at->isoFormat('LL') }} · {{ \App\Services\Money::format($order->total_cents, $order->currency) }}</div>
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
