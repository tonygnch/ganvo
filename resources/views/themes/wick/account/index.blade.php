@php
    $title = __('site.common.my_account');
@endphp
@extends('themes.wick.layout')

@section('content')
    <style>
        .account { display: grid; grid-template-columns: 250px 1fr; gap: 50px; padding: 20px 0 40px; align-items: start; }

        .acct-side { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 26px; position: sticky; top: 100px; box-shadow: 0 16px 38px -26px rgba(0, 0, 0, .55); }
        .acct-side .who { text-align: center; padding-bottom: 18px; border-bottom: 1px solid var(--line); margin-bottom: 12px; }
        .acct-side .who .av { width: 62px; height: 62px; border-radius: 50%; background: var(--jar); margin: 0 auto 12px; display: grid; place-items: center; font-family: var(--display); font-size: 24px; color: var(--bg); }
        .acct-side .who .hi { font-family: var(--display); font-size: 20px; }
        .acct-side .who .em { font-size: 12px; color: var(--muted); word-break: break-word; }
        .acct-side a, .acct-side button.navlink { display: block; width: 100%; text-align: left; padding: 13px 14px; border-radius: 10px; font-size: 14px; cursor: pointer; color: var(--ink); background: none; border: none; font-family: var(--body); transition: .2s; }
        .acct-side a:hover, .acct-side button.navlink:hover { background: var(--bg); }
        .acct-side a.on { background: var(--accent); color: var(--bg); }
        .acct-side form { margin: 0; }

        .acct-main h2 { font-family: var(--display); font-size: clamp(28px, 3.4vw, 42px); margin-bottom: 22px; font-weight: 800; letter-spacing: -.02em; }
        .acct-main h2 em { font-family: var(--serif); font-style: italic; color: var(--accent); }

        .order { background: var(--card); border: 1px solid var(--line); border-radius: 12px; margin-bottom: 18px; overflow: hidden; box-shadow: 0 12px 30px -24px rgba(0, 0, 0, .55); transition: border-color .25s ease; }
        .order:hover { border-color: var(--line2); }
        .order .head { display: flex; flex-wrap: wrap; gap: 18px; justify-content: space-between; align-items: center; padding: 18px 22px; background: var(--soft); font-size: 13px; }
        .order .head .k { font-size: 10px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
        .order .head b { font-family: var(--display); font-size: 16px; font-weight: 800; letter-spacing: -.02em; }
        .order .head .total b { font-family: var(--body); font-weight: 600; font-variant-numeric: tabular-nums; color: var(--accent); }
        .order .head .status { color: var(--accent); font-weight: 600; font-size: 12px; text-transform: uppercase; }
        .order .head .status.delivered { color: var(--muted); }
        .order .body { display: flex; gap: 18px; padding: 20px 22px; align-items: center; flex-wrap: wrap; }
        .order .act { margin-left: auto; display: flex; gap: 10px; }
        .order .act a { font-size: 12px; border: 1px solid var(--line); background: none; border-radius: 99px; padding: 9px 16px; color: var(--ink); transition: .2s; }
        .order .act a:hover { background: var(--accent); border-color: var(--accent); color: var(--bg); }

        .acct-empty { background: var(--card); border: 1px solid var(--line); border-radius: 14px; text-align: center; padding: 56px 24px; box-shadow: 0 16px 38px -26px rgba(0, 0, 0, .55); }
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
