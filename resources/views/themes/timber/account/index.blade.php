{{-- Timber — the account ledger. A stencilled counter card on the left, the
     customer's order dockets stacked on the right like delivery notes spiked
     at the yard gate: number, date, total, grade stamp. --}}
@php
    $title = __('site.common.my_account');
@endphp
@extends('themes.timber.layout')

@section('content')
    <style>
        .account { display: grid; grid-template-columns: 250px 1fr; gap: 50px; padding: 20px 0 40px; align-items: start; }

        /* ===== COUNTER CARD — the customer's own board, sticky at the desk. */
        .acct-side { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 24px 22px; position: sticky; top: calc(var(--header-height) + 24px); box-shadow: 0 2px 0 0 var(--line); }
        .acct-side .rule-ticks { margin: -6px -6px 18px; }
        .acct-side.no-rule .rule-ticks { display: none; }
        .acct-side .who { text-align: center; padding-bottom: 18px; border-bottom: 2px solid var(--txt); margin-bottom: 12px; }
        /* account chip — a treated end-cut with the initial branded on it */
        .acct-side .who .av { width: 58px; height: 58px; border-radius: 6px; margin: 0 auto 12px; display: grid; place-items: center; font-family: var(--display); font-weight: 700; font-size: 26px; color: var(--on-accent); background: linear-gradient(94deg, color-mix(in srgb, var(--accent) 70%, #b09a72), var(--accent)); border: 1px solid var(--accent-deep); box-shadow: 0 2px 0 0 var(--accent-deep); }
        .acct-side .who .hi { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .02em; font-size: 21px; line-height: 1.1; }
        .acct-side .who .em { font-family: var(--mono); font-size: 11px; letter-spacing: .02em; color: var(--muted); word-break: break-word; margin-top: 5px; }
        .acct-side a, .acct-side button.navlink { display: block; width: 100%; text-align: left; padding: 11px 13px; border: 1px solid transparent; border-radius: 6px; font-family: var(--mono); font-size: 11.5px; letter-spacing: .06em; text-transform: uppercase; cursor: pointer; color: var(--muted); background: none; transition: background-color .2s ease, color .2s ease, border-color .2s ease; }
        .acct-side a:hover, .acct-side button.navlink:hover { background: var(--surface2); color: var(--txt); }
        .acct-side a.on { background: var(--accent); border-color: var(--accent-deep); color: var(--on-accent); }
        .acct-side form { margin: 0; }

        .acct-main h2 { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .01em; font-size: clamp(28px, 3.4vw, 42px); line-height: 1; border-bottom: 2px solid var(--txt); padding-bottom: 14px; margin-bottom: 22px; }
        .acct-main h2 em { font-style: normal; color: var(--accent-deep); }

        /* ===== ORDER DOCKETS — ruled slips: mono spec labels up top, the
           action row underneath. Hover lifts the slip off the stack. */
        .order { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; margin-bottom: 16px; overflow: hidden; box-shadow: 0 2px 0 0 var(--line); transition: transform .22s cubic-bezier(.19, .7, .16, 1), border-color .25s ease, box-shadow .25s ease; }
        .order:hover { transform: translateY(-3px); border-color: var(--line2); box-shadow: 0 4px 0 0 var(--line2), 0 18px 30px -24px rgba(60, 44, 22, .45); }
        .order .head { display: flex; flex-wrap: wrap; gap: 18px; justify-content: space-between; align-items: center; padding: 16px 20px; background: var(--surface2); border-bottom: 1px solid var(--line); font-size: 13px; }
        .order .head .k { font-family: var(--mono); font-size: 10px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
        .order .head b { font-family: var(--display); font-weight: 700; font-size: 18px; text-transform: uppercase; letter-spacing: .02em; line-height: 1.15; }
        .order .head .total b { font-variant-numeric: tabular-nums; color: var(--accent-deep); }
        /* status reads as an inked grading stamp, knocked off-square */
        .order .head .status { font-family: var(--mono); font-weight: 600; font-size: 10.5px; letter-spacing: .1em; text-transform: uppercase; color: var(--accent-deep); border: 2px solid var(--accent-deep); border-radius: 4px; padding: 4px 10px; transform: rotate(-2deg); }
        .order .head .status.delivered, .order .head .status.cancelled, .order .head .status.refunded { color: var(--muted); border-color: var(--line2); }
        .order .body { display: flex; gap: 18px; padding: 16px 20px; align-items: center; flex-wrap: wrap; }
        .order .act { margin-left: auto; display: flex; gap: 10px; }
        .order .act a { font-family: var(--mono); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; border: 1px solid var(--line2); border-radius: 6px; padding: 9px 16px; color: var(--txt); background: transparent; box-shadow: 0 2px 0 0 var(--line2); transition: background-color .2s ease, color .2s ease, border-color .2s ease, box-shadow .2s ease; }
        .order .act a:hover { background: var(--accent); border-color: var(--accent-deep); color: var(--on-accent); box-shadow: 0 2px 0 0 var(--accent-deep); }

        /* ===== EMPTY RACK — nothing cut for this account yet. */
        .acct-empty { position: relative; overflow: hidden; background: var(--surface); border: 1px solid var(--line); border-radius: 10px; text-align: center; padding: 56px 24px; box-shadow: 0 2px 0 0 var(--line); }
        .acct-empty .ring.e1 { width: 190px; height: 190px; right: -60px; bottom: -70px; opacity: .45; }
        .acct-empty p { position: relative; z-index: 1; font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .02em; font-size: 24px; line-height: 1.15; color: var(--muted); margin-bottom: 22px; }
        .acct-empty p::before { content: "▮"; display: block; font-size: 13px; color: var(--accent); margin-bottom: 12px; }
        .acct-empty .btn { position: relative; z-index: 1; }

        @media (prefers-reduced-motion: reduce) {
            .order, .order:hover { transform: none; transition: none; }
        }
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
                <aside class="acct-side reveal {{ $theme->on('ruler') ? '' : 'no-rule' }}">
                    <div class="rule-ticks" aria-hidden="true"></div>
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
                            @if ($theme->on('grain_rings'))<div class="ring e1" aria-hidden="true"></div>@endif
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
                                        <b style="font-size: 14px;">{{ $order->created_at->isoFormat('LL') }}</b>
                                    </div>
                                    <div class="total">
                                        <div class="k">{{ __('site.order.total') }}</div>
                                        <b style="font-size: 14px;">{{ \App\Services\Money::format($order->total_cents, $order->currency) }}</b>
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
