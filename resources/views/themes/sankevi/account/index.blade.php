{{-- Sankevi — the account ledger. The customer's card stands in the margin;
     their delivery notes are stacked beside it, newest first. --}}
@php
    $title = __('site.common.my_account');
@endphp
@extends('themes.sankevi.layout')

@section('content')
    <style>
        .account { display: grid; grid-template-columns: 236px 1fr; gap: 56px; padding: 34px 0 40px; align-items: start; }

        /* ===== the customer's card ===== */
        .acct-side { position: sticky; top: calc(var(--header-height) + 26px); background: var(--surface); border: 1px solid var(--line); padding: 28px 24px 22px; }
        .acct-side .who { text-align: center; padding-bottom: 20px; margin-bottom: 14px; border-bottom: 1px solid var(--line2); }
        .acct-side .who .av { width: 56px; height: 56px; margin: 0 auto 14px; display: grid; place-items: center; background: var(--accent); color: var(--on-accent); font-family: var(--display); font-weight: 500; font-size: 25px; }
        .acct-side .who .hi { font-family: var(--display); font-weight: 500; font-size: 22px; line-height: 1.1; }
        .acct-side .who .em { margin-top: 6px; font-size: 11.5px; letter-spacing: .04em; color: var(--muted); word-break: break-word; }
        .acct-side a, .acct-side button.navlink { display: block; width: 100%; text-align: left; padding: 11px 12px; border: 1px solid transparent; background: none; color: var(--muted); font-family: var(--body); font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; cursor: pointer; transition: color .25s ease, background-color .25s ease; }
        .acct-side a:hover, .acct-side button.navlink:hover { background: var(--surface2); color: var(--txt); }
        .acct-side a.on { color: var(--accent); border-color: var(--line2); }
        .acct-side form { margin: 0; }

        .acct-main h2 { font-family: var(--display); font-weight: 500; font-size: clamp(28px, 3.4vw, 42px); line-height: 1.04; letter-spacing: -.015em; padding-bottom: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--line); }

        /* ===== delivery notes ===== */
        .order { background: var(--surface); border: 1px solid var(--line); margin-bottom: 16px; transition: border-color .3s ease; }
        .order:hover { border-color: var(--line2); }
        .order .head { display: flex; flex-wrap: wrap; gap: 22px; justify-content: space-between; align-items: center; padding: 18px 22px; border-bottom: 1px solid var(--line); }
        .order .head .k { font-size: 10px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); margin-bottom: 5px; }
        .order .head b { font-family: var(--display); font-weight: 500; font-size: 19px; line-height: 1.15; }
        .order .head .total b { font-variant-numeric: tabular-nums; color: var(--accent); }
        .order .head .status { font-size: 10px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--accent); border: 1px solid var(--accent); padding: 5px 11px; }
        .order .head .status.delivered, .order .head .status.cancelled, .order .head .status.refunded { color: var(--muted); border-color: var(--line2); }
        .order .body { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; padding: 16px 22px; }
        .order .act { margin-left: auto; }
        .order .act a { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); border-bottom: 1px solid var(--line2); padding-bottom: 3px; transition: color .25s ease, border-color .25s ease; }
        .order .act a:hover { color: var(--accent); border-color: var(--accent); }

        .acct-empty { padding: 70px 24px; text-align: center; border: 1px solid var(--line); background: var(--surface); }
        .acct-empty p { font-family: var(--display); font-style: italic; font-size: 24px; line-height: 1.2; color: var(--muted); margin-bottom: 26px; }

        @media (max-width: 900px) {
            .account { grid-template-columns: 1fr; gap: 30px; }
            .acct-side { position: static; }
        }
        @media (max-width: 560px) {
            .order .head { gap: 14px; }
            .order .act { margin-left: 0; }
        }
    </style>

    <main>
        <div class="wrap">
            <div class="page-head">
                <h1>{{ __('site.common.my_account') }}</h1>
            </div>

            <div class="account">
                <aside class="acct-side cut reveal">
                    <div class="who">
                        <div class="av cut cut-sm">{{ strtoupper(mb_substr($customer->name, 0, 1)) }}</div>
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
                        <div class="acct-empty cut reveal">
                            <p>{{ __('site.account.empty') }}</p>
                            <a href="/" class="btn">{{ __('site.account.start_shopping') }}</a>
                        </div>
                    @else
                        @foreach ($orders as $order)
                            <div class="order cut reveal">
                                <div class="head">
                                    <div>
                                        <div class="k">{{ __('site.order.details_h3') }}</div>
                                        <b>{{ $order->order_number }}</b>
                                    </div>
                                    <div>
                                        <div class="k">{{ __('site.order.placed') }}</div>
                                        <b style="font-size: 15px;">{{ $order->created_at->isoFormat('LL') }}</b>
                                    </div>
                                    <div class="total">
                                        <div class="k">{{ __('site.order.total') }}</div>
                                        <b style="font-size: 15px;">{{ \App\Services\Money::format($order->total_cents, $order->currency) }}</b>
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
