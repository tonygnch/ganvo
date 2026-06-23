@php
    $title = 'Order ' . $order->order_number;
@endphp
@extends('themes.brick.layout')

@section('content')
    <style>
        .ord { max-width: 880px; margin: 0 auto; padding: 48px 0 24px; }

        /* ===== success header ===== */
        .ord-hero { text-align: center; margin-bottom: 36px; }
        .ord-hero .mark {
            width: 66px; height: 66px; margin: 0 auto 22px; display: grid; place-items: center;
            background: var(--accent); border: 2.5px solid var(--ink); box-shadow: var(--pop);
            font-size: 30px; font-weight: 800; line-height: 1; color: var(--ink);
        }
        .ord-hero .mark.danger { background: #ff5a5a; color: var(--ink); }
        .ord-hero h1 {
            font-family: var(--display); font-weight: 900; text-transform: uppercase;
            font-size: clamp(28px, 4vw, 46px); line-height: .95; letter-spacing: -.02em; margin-bottom: 12px;
        }
        .ord-hero p { color: var(--text-muted); max-width: 54ch; margin: 0 auto; }
        .ord-hero .num {
            display: inline-flex; margin-top: 18px; background: var(--ink); color: var(--accent);
            border: 2.5px solid var(--ink); box-shadow: var(--pop-sm);
            font-family: var(--display); font-weight: 800; font-size: 14px; letter-spacing: .1em; padding: 9px 16px;
        }

        /* ===== shipped tracking banner ===== */
        .ord-ship {
            border: 2.5px solid var(--ink); box-shadow: var(--pop); background: var(--accent);
            padding: 18px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        .ord-ship .ic { width: 44px; height: 44px; background: var(--ink); color: var(--accent); display: grid; place-items: center; font-size: 20px; border: 2.5px solid var(--ink); flex-shrink: 0; }
        .ord-ship .tx { flex: 1; min-width: 200px; }
        .ord-ship h3 { font-family: var(--display); font-weight: 800; text-transform: uppercase; font-size: 13px; margin-bottom: 4px; color: var(--ink); }
        .ord-ship .meta { font-size: 13px; color: var(--ink); }
        .ord-ship .meta code { background: var(--paper); border: 2px solid var(--ink); padding: 1px 6px; font-family: var(--display); font-weight: 700; font-size: 12px; }
        .ord-ship .tl { font-family: var(--display); font-size: 11px; font-weight: 800; text-transform: uppercase; border: 2.5px solid var(--ink); background: var(--paper); color: var(--ink); box-shadow: var(--pop-sm); padding: 9px 14px; white-space: nowrap; transition: transform .12s ease, box-shadow .12s ease; }
        .ord-ship .tl:hover { transform: translate(-1px, -1px); box-shadow: var(--pop); }
        @media (prefers-reduced-motion: reduce) { .ord-ship .tl:hover { transform: none; } }

        /* ===== detail cards ===== */
        .ord-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .ord-card { border: 2.5px solid var(--ink); box-shadow: var(--pop); background: var(--paper); padding: 22px 24px; }
        .ord-card > h3 {
            font-family: var(--display); font-weight: 800; text-transform: uppercase; font-size: 12px; letter-spacing: .08em;
            color: var(--ink); margin-bottom: 14px; padding-bottom: 12px; border-bottom: 2.5px solid var(--ink);
        }
        .ord-card .row { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; font-size: 14px; align-items: center; }
        .ord-card .row .k { color: var(--text-muted); font-family: var(--display); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .ord-card .row .v { color: var(--ink); font-weight: 700; text-align: right; }
        .ord-card .addr { font-size: 15px; line-height: 1.65; color: var(--ink); }

        /* status badge — hard-bordered chip, semantic fill, ink text */
        .ord-badge { display: inline-flex; font-family: var(--display); font-weight: 800; font-size: 11px; letter-spacing: .05em; text-transform: uppercase; padding: 3px 10px; border: 2.5px solid var(--ink); background: var(--accent); color: var(--ink); }
        .ord-badge.paid { background: #9ff0b4; }
        .ord-badge.shipped { background: var(--accent); }
        .ord-badge.pending { background: #ffdf7a; }
        .ord-badge.refunded, .ord-badge.cancelled, .ord-badge.failed { background: #ff9b9b; }

        /* ===== items + totals ===== */
        .ord-items { border: 2.5px solid var(--ink); box-shadow: var(--pop); background: var(--paper); }
        .ord-items > h3 { font-family: var(--display); font-weight: 800; text-transform: uppercase; font-size: 12px; letter-spacing: .08em; color: var(--ink); padding: 18px 24px; border-bottom: 2.5px solid var(--ink); }
        .ord-item { display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: center; padding: 16px 24px; border-bottom: 2.5px solid var(--ink); }
        .ord-item .name { font-family: var(--display); font-weight: 700; font-size: 15px; text-transform: uppercase; line-height: 1.15; color: var(--ink); }
        .ord-item .meta { color: var(--text-muted); font-size: 13px; margin-top: 3px; }
        .ord-item .pr { font-family: var(--display); font-weight: 800; font-size: 15px; white-space: nowrap; color: var(--ink); }

        .ord-tot { padding: 18px 24px 20px; background: var(--ink); color: var(--paper); }
        .ord-tot .row { display: flex; justify-content: space-between; gap: 12px; padding: 5px 0; font-size: 14px; color: rgba(253, 251, 240, .82); }
        .ord-tot .row .num { font-weight: 700; color: var(--paper); }
        .ord-tot .row.disc .num { color: var(--accent); }
        .ord-tot .row code { background: rgba(253, 251, 240, .12); border: 1px solid rgba(253, 251, 240, .35); padding: 0 5px; font-family: var(--display); font-size: 11px; }
        .ord-tot .grand { padding-top: 14px; margin-top: 10px; border-top: 2.5px solid rgba(253, 251, 240, .3); }
        .ord-tot .grand .label { font-family: var(--display); font-weight: 800; text-transform: uppercase; font-size: 14px; color: var(--paper); }
        .ord-tot .grand .num { font-family: var(--display); font-weight: 900; font-size: 23px; color: var(--accent); }

        .ord-actions { display: flex; gap: 14px; justify-content: center; margin-top: 32px; flex-wrap: wrap; }

        @media (max-width: 720px) {
            .ord-grid { grid-template-columns: 1fr; }
        }
    </style>

    <main>
        <div class="wrap">
            <div class="ord">
                @php
                    $isShipped = $order->status === 'shipped';
                    $isCancelled = $order->status === 'cancelled';
                    $isRefunded = $order->status === 'refunded';
                    $isPending = $order->status === 'pending';
                    $isFailed = $order->status === 'failed';
                    $markClass = ($isCancelled || $isRefunded || $isFailed) ? 'danger' : '';
                    $iconChar = $isCancelled || $isFailed ? '×' : ($isRefunded ? '↺' : ($isPending ? '⏳' : '✓'));
                @endphp

                <div class="ord-hero">
                    <div class="mark {{ $markClass }}"><span>{{ $iconChar }}</span></div>
                    <h1>
                        @if ($isCancelled)
                            {{ __('site.order.cancelled_title') }}
                        @elseif ($isRefunded)
                            {{ __('site.order.refunded_title') }}
                        @elseif ($isShipped)
                            {{ __('site.order.shipped_title') }}
                        @elseif ($isFailed)
                            Payment failed
                        @elseif ($isPending)
                            Processing your payment…
                        @else
                            {{ __('site.order.thanks_name', ['name' => explode(' ', $order->customer_name)[0]]) }}
                        @endif
                    </h1>
                    <p>
                        @if ($isCancelled)
                            {{ __('site.order.cancelled_body') }}
                        @elseif ($isRefunded)
                            {{ __('site.order.refunded_body') }}
                        @elseif ($isShipped)
                            {{ __('site.order.shipped_body') }}
                        @elseif ($isFailed)
                            Your card was declined or the payment didn't complete. Head back to your cart to try again.
                        @elseif ($isPending)
                            Your payment is being confirmed by your bank. This page will refresh automatically.
                        @else
                            {{ __('site.order.paid_body', ['email' => $order->customer_email]) }}
                        @endif
                    </p>
                    <span class="num">{{ $order->order_number }}</span>

                    @if ($isPending)
                        {{-- Auto-refresh while pending — the webhook (or the controller's
                             reconcile pull on next render) flips status → paid. --}}
                        <meta http-equiv="refresh" content="4">
                    @endif
                </div>

                @if ($isShipped && $order->tracking_number)
                    @php
                        $carrierLabel = \App\Services\Shipping\CarrierRegistry::label($order->carrier);
                        $trackingHref = $order->tracking_url
                            ?: \App\Services\Shipping\CarrierRegistry::trackingUrlFor($order->carrier, $order->tracking_number);
                    @endphp
                    <div class="ord-ship">
                        <div class="ic">↗</div>
                        <div class="tx">
                            <h3>{{ __('site.order.shipped_via', ['carrier' => $carrierLabel]) }}</h3>
                            <div class="meta">{!! __('site.order.tracking_meta', ['code' => '<code>' . e($order->tracking_number) . '</code>', 'ago' => $order->shipped_at->diffForHumans()]) !!}</div>
                        </div>
                        @if ($trackingHref)
                            <a href="{{ $trackingHref }}" target="_blank" rel="noopener" class="tl">{{ __('site.order.track_shipment') }} →</a>
                        @endif
                    </div>
                @endif

                <div class="ord-grid">
                    <div class="ord-card">
                        <h3>{{ __('site.order.details_h3') }}</h3>
                        <div class="row"><span class="k">{{ __('site.order.status') }}</span><span class="v"><span class="ord-badge {{ $order->status }}">{{ __('site.statuses.' . $order->status) }}</span></span></div>
                        <div class="row"><span class="k">{{ __('site.order.placed') }}</span><span class="v">{{ $order->created_at->isoFormat('LL') }}</span></div>
                        <div class="row"><span class="k">{{ __('site.order.customer') }}</span><span class="v">{{ $order->customer_name }}</span></div>
                        <div class="row"><span class="k">{{ __('site.order.email_label') }}</span><span class="v">{{ $order->customer_email }}</span></div>
                    </div>

                    @if ($order->shipping_address)
                        <div class="ord-card">
                            <h3>{{ __('site.order.shipping_to') }}</h3>
                            <div class="addr">
                                {{ $order->customer_name }}<br>
                                {{ $order->shipping_address['line'] }}<br>
                                {{ $order->shipping_address['city'] }}, {{ $order->shipping_address['postal_code'] }}<br>
                                {{ $order->shipping_address['country'] }}
                            </div>
                        </div>
                    @endif
                </div>

                <div class="ord-items">
                    <h3>{{ __('site.order.items_ordered') }}</h3>
                    @php
                        $itemsTotal = $order->items->sum('subtotal_cents');
                        $discountAmount = (int) ($order->discount_amount_cents ?? 0);
                        $shipping = $order->shipping_cents !== null && $order->shipping_cents > 0
                            ? (int) $order->shipping_cents
                            : max(0, $order->total_cents + $discountAmount - $itemsTotal);
                    @endphp
                    @foreach ($order->items as $item)
                        <div class="ord-item">
                            <div>
                                <div class="name">{{ $item->displayName() }}</div>
                                <div class="meta">{{ __('site.order.qty_unit', ['qty' => $item->quantity, 'price' => \App\Services\Money::format($item->unit_price_cents, $order->currency)]) }}</div>
                            </div>
                            <div class="pr">{{ \App\Services\Money::format($item->subtotal_cents, $order->currency) }}</div>
                        </div>
                    @endforeach
                    <div class="ord-tot">
                        <div class="row"><span>{{ __('site.order.subtotal') }}</span><span class="num">{{ \App\Services\Money::format($itemsTotal, $order->currency) }}</span></div>
                        <div class="row"><span>{{ __('site.order.shipping_label') }}@if($order->shipping_method_label) <span style="opacity:.65">· {{ $order->shipping_method_label }}</span>@endif</span><span class="num">{{ $shipping === 0 ? __('site.common.free') : \App\Services\Money::format($shipping, $order->currency) }}</span></div>
                        @if ($discountAmount > 0)
                            <div class="row disc"><span>{{ $order->discount_name ?: __('site.order.discount') }}@if($order->discount_code) <code>{{ $order->discount_code }}</code>@endif</span><span class="num">−{{ \App\Services\Money::format($discountAmount, $order->currency) }}</span></div>
                        @endif
                        <div class="row grand"><span class="label">{{ __('site.order.total') }}</span><span class="num">{{ \App\Services\Money::format($order->total_cents, $order->currency) }}</span></div>
                        @if ($order->display_currency && $order->display_currency !== $order->currency && $order->display_total_cents)
                            <div class="row" style="margin-top: .5rem; font-size: 12px; opacity: .8;">
                                <span>{{ __('site.order.you_saw') }}</span>
                                <span class="num">{{ \App\Services\Money::formatAsDisplay($order->display_total_cents, $order->display_currency, $order->total_cents, $order->currency) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="ord-actions">
                    <a href="/" class="btn accent">{{ __('site.common.continue_shopping') }}</a>
                    <a href="mailto:{{ $tenant->contact_email ?: 'support@example.com' }}?subject=Order%20{{ $order->order_number }}" class="btn">{{ __('site.order.need_help') }}</a>
                </div>
            </div>
        </div>
    </main>
@endsection
