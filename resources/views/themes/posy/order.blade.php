@php
    $title = 'Order ' . $order->order_number;
@endphp
@extends('themes.posy.layout')

@section('content')
    <style>
        .ord { max-width: 820px; margin: 0 auto; padding: 56px 0 28px; }

        /* ===== success hero ===== */
        .ord-hero { text-align: center; margin-bottom: 40px; }
        .ord-hero .mark {
            width: 74px; height: 74px; margin: 0 auto 24px; display: grid; place-items: center;
            border-radius: 99px; background: var(--accent); color: #fbfcf5;
            font-size: 32px; line-height: 1; box-shadow: 0 16px 38px -22px rgba(40, 50, 31, .4);
        }
        .ord-hero .mark.danger { background: var(--bloom); }
        .ord-hero h1 { font-family: var(--display); font-weight: 400; font-size: clamp(34px, 5vw, 56px); line-height: 1.02; margin-bottom: 14px; }
        .ord-hero h1 em { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .ord-hero p { color: var(--muted); max-width: 54ch; margin: 0 auto; }
        .ord-hero .num {
            display: inline-flex; margin-top: 20px; align-items: center; gap: 8px;
            background: var(--card); border: 1px solid var(--line); border-radius: 99px;
            font-family: var(--serif); font-size: 16px; letter-spacing: .04em; color: var(--accent);
            padding: 9px 18px; box-shadow: 0 16px 38px -22px rgba(40, 50, 31, .4);
        }
        .ord-hero .num::before { content: "❧"; color: var(--accent); }

        /* ===== shipped tracking banner ===== */
        .ord-ship {
            background: var(--card); border: 1px solid var(--line); border-radius: 14px;
            box-shadow: 0 16px 38px -22px rgba(40, 50, 31, .4);
            padding: 20px 22px; margin-bottom: 26px; display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
        }
        .ord-ship .ic { width: 48px; height: 48px; border-radius: 99px; background: var(--accent); color: #fbfcf5; display: grid; place-items: center; font-size: 22px; flex-shrink: 0; }
        .ord-ship .tx { flex: 1; min-width: 200px; }
        .ord-ship h3 { font-family: var(--display); font-weight: 400; font-size: 18px; margin-bottom: 4px; color: var(--ink); }
        .ord-ship .meta { font-size: 14px; color: var(--muted); }
        .ord-ship .meta code { background: var(--soft); border-radius: 6px; padding: 1px 7px; font-family: var(--serif); font-size: 14px; color: var(--ink); }

        /* ===== detail cards ===== */
        .ord-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 22px; }
        .ord-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 16px 38px -22px rgba(40, 50, 31, .4); padding: 24px 26px; }
        .ord-card > h3 {
            font-family: var(--display); font-weight: 400; font-size: 20px; color: var(--ink);
            margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid var(--line);
        }
        .ord-card > h3::before { content: "❧ "; color: var(--accent); }
        .ord-card .row { display: flex; justify-content: space-between; gap: 12px; padding: 7px 0; font-size: 14px; align-items: center; }
        .ord-card .row .k { color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .08em; font-weight: 600; }
        .ord-card .row .v { color: var(--ink); font-weight: 600; text-align: right; }
        .ord-card .addr { font-size: 15px; line-height: 1.7; color: var(--ink); }

        /* status badge — soft pill chip */
        .ord-badge { display: inline-flex; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; font-weight: 600; padding: 4px 11px; border-radius: 99px; background: var(--soft); color: var(--ink); }
        .ord-badge.paid { background: color-mix(in srgb, var(--accent) 22%, var(--card)); color: var(--accent); }
        .ord-badge.shipped { background: color-mix(in srgb, var(--accent) 22%, var(--card)); color: var(--accent); }
        .ord-badge.pending { background: #f3e7b8; color: #6b5a14; }
        .ord-badge.refunded, .ord-badge.cancelled, .ord-badge.failed { background: #f0d3c6; color: #9c5a3e; }

        /* ===== items + totals ===== */
        .ord-items { background: var(--card); border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 16px 38px -22px rgba(40, 50, 31, .4); overflow: hidden; }
        .ord-items > h3 { font-family: var(--display); font-weight: 400; font-size: 20px; color: var(--ink); padding: 20px 26px; border-bottom: 1px solid var(--line); }
        .ord-items > h3::before { content: "❧ "; color: var(--accent); }
        .ord-item { display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: center; padding: 16px 26px; border-bottom: 1px solid var(--line); }
        .ord-item .name { font-family: var(--display); font-weight: 400; font-size: 18px; line-height: 1.2; color: var(--ink); }
        .ord-item .meta { color: var(--muted); font-size: 13px; margin-top: 3px; }
        .ord-item .pr { font-family: var(--body); font-weight: 600; font-size: 18px; font-variant-numeric: tabular-nums; white-space: nowrap; color: var(--accent); }

        .ord-tot { padding: 20px 26px 24px; background: var(--soft); }
        .ord-tot .row { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; font-size: 14px; color: var(--muted); }
        .ord-tot .row .num { font-weight: 600; color: var(--ink); font-family: var(--body); font-variant-numeric: tabular-nums; font-size: 16px; }
        .ord-tot .row.disc .num { color: var(--accent); }
        .ord-tot .row code { background: var(--card); border: 1px solid var(--line); border-radius: 5px; padding: 0 6px; font-family: var(--serif); font-size: 13px; color: var(--ink); }
        .ord-tot .grand { padding-top: 16px; margin-top: 12px; border-top: 1px solid var(--line); }
        .ord-tot .grand .label { font-family: var(--display); font-weight: 400; font-size: 18px; color: var(--ink); }
        .ord-tot .grand .num { font-family: var(--body); font-weight: 700; font-size: 27px; font-variant-numeric: tabular-nums; color: var(--accent); }

        .ord-actions { display: flex; gap: 14px; justify-content: center; margin-top: 36px; flex-wrap: wrap; }

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

                <div class="ord-hero reveal">
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
                    <div class="ord-ship reveal">
                        <div class="ic">↗</div>
                        <div class="tx">
                            <h3>{{ __('site.order.shipped_via', ['carrier' => $carrierLabel]) }}</h3>
                            <div class="meta">{!! __('site.order.tracking_meta', ['code' => '<code>' . e($order->tracking_number) . '</code>', 'ago' => $order->shipped_at->diffForHumans()]) !!}</div>
                        </div>
                        @if ($trackingHref)
                            <a href="{{ $trackingHref }}" target="_blank" rel="noopener" class="btn outline">{{ __('site.order.track_shipment') }} →</a>
                        @endif
                    </div>
                @endif

                <div class="ord-grid">
                    <div class="ord-card reveal">
                        <h3>{{ __('site.order.details_h3') }}</h3>
                        <div class="row"><span class="k">{{ __('site.order.status') }}</span><span class="v"><span class="ord-badge {{ $order->status }}">{{ __('site.statuses.' . $order->status) }}</span></span></div>
                        <div class="row"><span class="k">{{ __('site.order.placed') }}</span><span class="v">{{ $order->created_at->isoFormat('LL') }}</span></div>
                        <div class="row"><span class="k">{{ __('site.order.customer') }}</span><span class="v">{{ $order->customer_name }}</span></div>
                        <div class="row"><span class="k">{{ __('site.order.email_label') }}</span><span class="v">{{ $order->customer_email }}</span></div>
                    </div>

                    @if ($order->shipping_address)
                        <div class="ord-card reveal s1">
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

                <div class="ord-items reveal">
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
                    <a href="/" class="btn">{{ __('site.common.continue_shopping') }}</a>
                    <a href="mailto:{{ $tenant->contact_email ?: 'support@example.com' }}?subject=Order%20{{ $order->order_number }}" class="btn outline">{{ __('site.order.need_help') }}</a>
                </div>
            </div>
        </div>
    </main>
@endsection
