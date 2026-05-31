@php $title = __('site.common.my_account'); @endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .acct { max-width: 880px; margin: 0 auto; padding: 50px 36px 90px; }
        .acct-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; padding-bottom: 22px; margin-bottom: 28px; border-bottom: 1px solid var(--line); }
        .acct-head .eyebrow { font-family: var(--mono); font-size: 11px; color: var(--faint); text-transform: uppercase; margin-bottom: 10px; }
        .acct-head h1 { font-family: var(--archivo); font-weight: 800; font-size: clamp(28px,4vw,42px); letter-spacing: -.02em; }
        .acct-head p { color: var(--muted); font-size: 13px; margin-top: 6px; font-family: var(--mono); }
        .acct-actions { display: flex; align-items: center; gap: 16px; }
        .settings-link { font-family: var(--mono); font-size: 12px; color: var(--accent); }
        .logout-btn { background: none; border: 1px solid var(--line); color: var(--txt); border-radius: 6px; padding: 10px 18px; font-size: 12px; cursor: pointer; }
        .logout-btn:hover { border-color: var(--txt); }
        .sec-t { font-family: var(--mono); font-size: 11px; color: var(--faint); text-transform: uppercase; margin-bottom: 16px; }
        .orders { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; }
        .order-row { display: grid; grid-template-columns: 1fr auto auto; gap: 1.5rem; align-items: center; padding: 18px 22px; border-top: 1px solid var(--line); }
        .order-row:first-child { border-top: 0; }
        .order-row .num { font-family: var(--mono); font-size: 14px; color: var(--accent); }
        .order-row .date { font-size: 13px; color: var(--muted); margin-top: 3px; }
        .ost { padding: 3px 10px; border-radius: 5px; font-family: var(--mono); font-size: 10px; font-weight: 700; text-transform: uppercase; white-space: nowrap; }
        .ost.paid { background: color-mix(in srgb,#16a34a 22%,var(--surface)); color: #6ee7a0; } .ost.shipped { background: var(--surface2); color: var(--accent); }
        .ost.pending { background: color-mix(in srgb,#d97706 22%,var(--surface)); color: #fbbf6b; } .ost.failed,.ost.cancelled,.ost.refunded { background: rgba(255,92,92,.14); color: #ff8a8a; }
        .order-row .view { font-family: var(--mono); font-size: 12px; color: var(--muted); white-space: nowrap; } .order-row .view:hover { color: var(--accent); }
        .acct-empty { border: 1px solid var(--line); border-radius: 12px; padding: 60px; text-align: center; color: var(--muted); font-family: var(--mono); font-size: 13px; }
        .acct-empty .btn { margin-top: 18px; }
        @media (max-width: 600px) { .order-row { grid-template-columns: 1fr auto; row-gap: 8px; } .order-row .view { grid-column: 1/-1; } }
    </style>

    <main>
        <div class="acct">
            <div class="acct-head rv">
                <div>
                    <div class="eyebrow">// {{ __('site.common.my_account') }}</div>
                    <h1>{{ __('site.account.hi', ['name' => explode(' ', $customer->name)[0]]) }}</h1>
                    <p>{{ __('site.account.signed_in_as', ['email' => $customer->email]) }}</p>
                </div>
                <div class="acct-actions">
                    <a href="/account/settings" class="settings-link">{{ __('site.account.settings') }}</a>
                    <form method="post" action="/account/logout">@csrf<button type="submit" class="logout-btn">{{ __('site.account.sign_out') }}</button></form>
                </div>
            </div>
            <div class="sec-t rv">// {{ __('site.account.recent_orders') }}</div>
            @if ($orders->isEmpty())
                <div class="acct-empty rv"><div>{{ __('site.account.empty') }}</div><a href="/" class="btn">{{ __('site.account.start_shopping') }}</a></div>
            @else
                <div class="orders rv">
                    @foreach ($orders as $order)
                        <div class="order-row">
                            <div><div class="num">{{ $order->order_number }}</div><div class="date">{{ $order->created_at->isoFormat('LL') }} · {{ \App\Services\Money::format($order->total_cents, $order->currency) }}</div></div>
                            <span class="ost {{ $order->status }}">{{ __('site.statuses.' . $order->status) }}</span>
                            <a href="/orders/{{ $order->order_number }}" class="view">{{ __('site.account.view') }}</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </main>
@endsection
