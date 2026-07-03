@php
    $title = __('site.common.my_account');
@endphp
@extends('themes.forma.layout')

@section('content')
    <style>
        .account { display: grid; grid-template-columns: 240px 1fr; gap: 44px; padding: 30px 0 70px; align-items: start; }

        .acct-side { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 22px; position: sticky; top: 90px; }
        .acct-side .who { display: flex; gap: 12px; align-items: center; padding-bottom: 18px; border-bottom: 1px solid var(--line); margin-bottom: 12px; }
        .acct-side .who .av { width: 46px; height: 46px; border-radius: 12px; background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 40%, #fff), var(--accent)); display: grid; place-items: center; font-family: var(--display); font-weight: 800; font-size: 18px; color: #fff; flex-shrink: 0; }
        .acct-side .who .hi { font-weight: 600; }
        .acct-side .who .em { font-family: var(--mono); font-size: 11px; color: var(--muted); word-break: break-word; }
        .acct-side a, .acct-side button.navlink { display: block; width: 100%; text-align: left; padding: 12px 12px; border-radius: 9px; font-size: 14px; cursor: pointer; color: var(--muted); background: none; border: none; font-family: var(--body); transition: .18s; }
        .acct-side a:hover, .acct-side button.navlink:hover { background: var(--soft); color: var(--ink); }
        .acct-side a.on { background: var(--ink); color: #fff; font-weight: 600; }
        .acct-side form { margin: 0; }

        .acct-main h2 { font-family: var(--display); font-weight: 800; font-size: clamp(24px, 3vw, 36px); letter-spacing: -.02em; margin-bottom: 20px; }
        .acct-main h2 em { font-style: normal; color: var(--accent); }

        .order { background: var(--card); border: 1px solid var(--line); border-radius: 14px; margin-bottom: 16px; overflow: hidden; }
        .order .head { display: flex; flex-wrap: wrap; gap: 18px; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--line); font-size: 13px; }
        .order .head .k { font-family: var(--mono); font-size: 10px; text-transform: uppercase; color: var(--muted); }
        .order .head b { font-family: var(--display); font-size: 15px; }
        .order .head .total b { font-family: var(--display); font-weight: 600; font-variant-numeric: tabular-nums; }
        .order .head .status { color: var(--accent); font-family: var(--mono); font-size: 12px; }
        .order .head .status.delivered { color: var(--muted); }
        .order .body { display: flex; gap: 18px; padding: 18px 20px; align-items: center; flex-wrap: wrap; }
        .order .act { margin-left: auto; display: flex; gap: 10px; }
        .order .act a { font-size: 12px; border: 1px solid var(--line2); background: none; border-radius: 8px; padding: 9px 14px; color: var(--ink); transition: .2s; }
        .order .act a:hover { border-color: var(--accent); color: var(--accent); }

        .acct-empty { background: var(--card); border: 1px solid var(--line); border-radius: 16px; text-align: center; padding: 56px 24px; }
        .acct-empty p { font-family: var(--mono); font-size: 14px; color: var(--muted); margin-bottom: 22px; }

        @media (max-width: 880px) {
            .account { grid-template-columns: 1fr; gap: 28px; }
            .acct-side { position: static; }
        }
        @media (max-width: 560px) {
            .order .head { gap: 12px; }
            .order .act { margin-left: 0; width: 100%; }
        }
    </style>

    <main>
        <div class="wrap">
            <div class="page-head" style="padding-bottom: 14px;">
                <h1>{{ __('site.common.my_account') }}</h1>
            </div>

            <div class="account">
                <aside class="acct-side reveal">
                    <div class="who">
                        <div class="av">{{ strtoupper(mb_substr($customer->name, 0, 1)) }}</div>
                        <div class="hi">{{ explode(' ', $customer->name)[0] }}</div>
                        <div class="em">{{ $customer->email }}</div>
                    </div>
                    <a href="/account" class="on">{{ __('site.account.recent_orders') }}</a>
                    <a href="/account/settings">{{ __('site.account.settings') }}</a>
                    <form method="post" action="/account/logout">
                        @csrf
                        <button type="submit" class="navlink">{{ __('site.account.sign_out') }}</button>
                    </form>
                </aside>

                <div class="acct-main">
                    <h2>{{ __('site.account.recent_orders') }}</h2>

                    @if ($orders->isEmpty())
                        <div class="acct-empty reveal">
                            <p>{{ __('site.account.empty') }}</p>
                            <a href="/" class="btn">{{ __('site.account.start_shopping') }}</a>
                        </div>
                    @else
                        @foreach ($orders as $order)
                            <div class="order reveal">
                                <div class="head">
                                    <div>
                                        <div class="k">Order</div>
                                        <b>{{ $order->order_number }}</b>
                                    </div>
                                    <div>
                                        <div class="k">{{ __('site.order.placed') }}</div>
                                        <b style="font-size: 13px;">{{ $order->created_at->isoFormat('LL') }}</b>
                                    </div>
                                    <div class="total">
                                        <div class="k">{{ __('site.order.total') }}</div>
                                        <b style="font-size: 13px;">{{ \App\Services\Money::format($order->total_cents, $order->currency) }}</b>
                                    </div>
                                    <div class="status {{ $order->status }}">{{ __('site.statuses.' . $order->status) }}</div>
                                </div>
                                <div class="body">
                                    <div class="act">
                                        <a href="/orders/{{ $order->order_number }}">{{ __('site.account.view') }} →</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </main>
@endsection
