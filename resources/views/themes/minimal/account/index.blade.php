@php $title = __('site.common.my_account'); @endphp
@extends('themes.minimal.layout')

@section('content')
    <style>
        .acct { max-width: 880px; margin: 0 auto; padding: 50px 40px 90px; }
        .acct-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; padding-bottom: 22px; margin-bottom: 28px; border-bottom: 1px solid var(--line); }
        .acct-head .eyebrow { font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }
        .acct-head h1 { font-family: var(--display); font-size: clamp(30px,4vw,46px); }
        .acct-head p { color: var(--muted); font-size: 14px; margin-top: 6px; }
        .acct-actions { display: flex; align-items: center; gap: 16px; }
        .settings-link { font-size: 13px; font-weight: 600; color: var(--accent); }
        .logout-btn { background: none; border: 1.5px solid var(--line); color: var(--ink); border-radius: 99px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .logout-btn:hover { border-color: var(--ink); }
        .sec-t { font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin-bottom: 16px; }
        .orders { background: var(--card); border-radius: 22px; overflow: hidden; }
        .order-row { display: grid; grid-template-columns: 1fr auto auto; gap: 1.5rem; align-items: center; padding: 18px 24px; border-top: 1px solid var(--line); }
        .order-row:first-child { border-top: 0; }
        .order-row .num { font-family: var(--display); font-size: 17px; }
        .order-row .date { font-size: 13px; color: var(--muted); margin-top: 3px; }
        .ost { padding: 3px 12px; border-radius: 99px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
        .ost.paid { background: #dcefe0; color: #2f7d4f; } .ost.shipped { background: var(--blush); color: var(--accent); } .ost.pending { background: #fbe5cf; color: #9a6a1e; } .ost.failed,.ost.cancelled,.ost.refunded { background: #fbe0d9; color: #9a4a37; }
        .order-row .view { font-size: 13px; font-weight: 600; color: var(--accent); white-space: nowrap; }
        .acct-empty { background: var(--card); border-radius: 22px; padding: 60px; text-align: center; color: var(--muted); }
        .acct-empty .btn { margin-top: 18px; }
        @media (max-width: 600px) { .order-row { grid-template-columns: 1fr auto; row-gap: 8px; } .order-row .view { grid-column: 1/-1; } }
    </style>

    <main>
        <div class="acct">
            <div class="acct-head rv">
                <div><div class="eyebrow">{{ __('site.common.my_account') }}</div><h1>{{ __('site.account.hi', ['name' => explode(' ', $customer->name)[0]]) }}</h1><p>{{ __('site.account.signed_in_as', ['email' => $customer->email]) }}</p></div>
                <div class="acct-actions">
                    <a href="/account/settings" class="settings-link">{{ __('site.account.settings') }}</a>
                    <form method="post" action="/account/logout">@csrf<button type="submit" class="logout-btn">{{ __('site.account.sign_out') }}</button></form>
                </div>
            </div>
            <div class="sec-t rv">{{ __('site.account.recent_orders') }}</div>
            @if ($orders->isEmpty())
                <div class="acct-empty rv"><div>{{ __('site.account.empty') }}</div><a href="/" class="btn">{{ __('site.account.start_shopping') }}</a></div>
            @else
                <div class="orders rv">
                    @foreach ($orders as $order)
                        <div class="order-row"><div><div class="num">{{ $order->order_number }}</div><div class="date">{{ $order->created_at->isoFormat('LL') }} · {{ \App\Services\Money::format($order->total_cents, $order->currency) }}</div></div><span class="ost {{ $order->status }}">{{ __('site.statuses.' . $order->status) }}</span><a href="/orders/{{ $order->order_number }}" class="view">{{ __('site.account.view') }}</a></div>
                    @endforeach
                </div>
            @endif
        </div>
    </main>
@endsection
