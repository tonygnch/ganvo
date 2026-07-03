@php
    $title = __('site.common.my_account');
@endphp
@extends('themes.kiln.layout')

@section('content')
    <style>
        .account { display: grid; grid-template-columns: 240px 1fr; gap: 56px; padding: 20px 0 40px; align-items: start; }

        .acct-side { background: none; border-top: 1px solid var(--ink); padding: 0; position: sticky; top: 100px; }
        .acct-side .who { padding: 22px 0; border-bottom: 1px solid var(--line); margin-bottom: 0; text-align: left; }
        .acct-side .who .av { width: 56px; height: 56px; border-radius: 2px; background: var(--ink); margin: 0 0 12px; display: grid; place-items: center; font-family: var(--display); font-size: 22px; color: var(--bg); }
        .acct-side .who .hi { font-family: var(--serif); font-size: 24px; }
        .acct-side .who .em { font-size: 12px; color: var(--muted); word-break: break-word; }
        .acct-side a, .acct-side button.navlink { display: block; width: 100%; text-align: left; padding: 15px 0; border-bottom: 1px solid var(--line); font-family: var(--display); font-size: 11px; letter-spacing: .14em; text-transform: uppercase; cursor: pointer; color: var(--ink); background: none; border-left: none; border-right: none; border-top: none; transition: color .2s; }
        .acct-side a:hover, .acct-side button.navlink:hover { color: var(--accent); }
        .acct-side a.on { color: var(--accent); }
        .acct-side form { margin: 0; }

        .acct-main h2 { font-family: var(--serif); font-size: clamp(28px, 3.4vw, 44px); margin-bottom: 24px; font-weight: 400; border-bottom: 1px solid var(--ink); padding-bottom: 16px; }
        .acct-main h2 em { font-style: italic; color: var(--accent); }

        .order { background: none; border: 1px solid var(--line); border-radius: 2px; margin-bottom: 18px; overflow: hidden; }
        .order .head { display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between; align-items: center; padding: 18px 22px; background: var(--soft); font-size: 12px; }
        .order .head .k { font-family: var(--display); font-size: 10px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
        .order .head b { font-family: var(--serif); font-size: 18px; font-weight: 400; }
        .order .head .total b { font-family: var(--body); font-weight: 600; font-variant-numeric: tabular-nums; color: var(--ink); }
        .order .head .status { font-family: var(--display); color: var(--accent); font-weight: 600; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; }
        .order .head .status.delivered { color: var(--muted); }
        .order .body { display: flex; gap: 18px; padding: 20px 22px; align-items: center; flex-wrap: wrap; }
        .order .act { margin-left: auto; display: flex; gap: 10px; }
        .order .act a { font-family: var(--display); font-size: 10px; letter-spacing: .1em; text-transform: uppercase; border: 1px solid var(--ink); background: none; border-radius: 2px; padding: 10px 14px; color: var(--ink); transition: .2s; }
        .order .act a:hover { background: var(--ink); border-color: var(--ink); color: var(--bg); }

        .acct-empty { background: var(--card); border: 1px solid var(--line); border-radius: 2px; text-align: center; padding: 56px 24px; }
        .acct-empty p { font-family: var(--serif); font-style: italic; font-size: 22px; color: var(--muted); margin-bottom: 22px; }

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
