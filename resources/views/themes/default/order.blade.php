@php
    $title = 'Order ' . $order->order_number;
@endphp
@extends('themes.default.layout')

@section('content')
    @php
        $isShipped   = $order->status === 'shipped';
        $isCancelled = $order->status === 'cancelled';
        $isRefunded  = $order->status === 'refunded';
        $isPending   = $order->status === 'pending';
        $isFailed    = $order->status === 'failed';
        $isBad       = $isCancelled || $isRefunded || $isFailed;

        $itemsTotal = $order->items->sum('subtotal_cents');
        $discountAmount = (int) ($order->discount_amount_cents ?? 0);
        $shipping = $order->shipping_cents !== null && $order->shipping_cents > 0
            ? (int) $order->shipping_cents
            : max(0, $order->total_cents + $discountAmount - $itemsTotal);
    @endphp

    <style>
        .ord {
            max-width: 760px;
            margin: 0 auto;
            padding: 56px 1.75rem 80px;
        }

        /* ----- hero ----- */
        .ord-hero { text-align: center; margin-bottom: 48px; }
        .ord-icon {
            width: 64px; height: 64px;
            border-radius: 50%;
            margin: 0 auto 24px;
            display: grid;
            place-items: center;
            font-size: 26px;
            line-height: 1;
            background: color-mix(in srgb, var(--accent) 14%, var(--paper));
            color: var(--accent);
            border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);
        }
        .ord-icon.bad {
            background: color-mix(in srgb, #b91c1c 10%, var(--paper));
            color: #b91c1c;
            border-color: color-mix(in srgb, #b91c1c 28%, transparent);
        }
        .ord-icon.info {
            background: color-mix(in srgb, var(--ink) 6%, var(--paper));
            color: var(--ink);
            border-color: var(--line);
        }
        .ord-hero h1 {
            font-family: var(--display);
            font-size: clamp(32px, 4.5vw, 48px);
            font-weight: 500;
            line-height: 1.05;
            margin-bottom: 12px;
        }
        .ord-hero p {
            color: var(--muted);
            font-size: 15px;
            max-width: 46ch;
            margin: 0 auto;
            line-height: 1.6;
        }
        .ord-num {
            display: inline-block;
            margin-top: 22px;
            padding: 8px 18px;
            border: 1px solid var(--line);
            border-radius: 9999px;
            font-family: "Hanken Grotesk", monospace;
            font-size: 13px;
            letter-spacing: .16em;
            color: var(--ink);
            background: var(--paper);
        }

        /* ----- shipped tracking banner ----- */
        .ord-ship {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            border: 1px solid var(--line);
            background: color-mix(in srgb, var(--ink) 4%, var(--paper));
            margin-bottom: 28px;
        }
        .ord-ship .ic {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: var(--paper);
            border: 1px solid var(--line);
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .ord-ship .tx { flex: 1; }
        .ord-ship h3 { font-size: 14px; font-weight: 600; margin-bottom: 2px; }
        .ord-ship .meta { font-size: 13px; color: var(--muted); }
        .ord-ship .meta code { font-family: "Hanken Grotesk", monospace; letter-spacing: .04em; }
        .ord-ship .track {
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            font-weight: 600;
            border-bottom: 1px solid currentColor;
            padding-bottom: 1px;
            white-space: nowrap;
        }
        .ord-ship .track:hover { color: var(--accent); }

        /* ----- detail cards ----- */
        .ord-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .ord-card {
            border: 1px solid var(--line);
            padding: 24px 26px;
            background: var(--paper);
        }
        .ord-card h3 {
            font-size: 11px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 18px;
            font-weight: 700;
        }
        .ord-card .r {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 7px 0;
            font-size: 14px;
        }
        .ord-card .r .k { color: var(--muted); }
        .ord-card .r .v { color: var(--ink); font-weight: 500; text-align: right; }
        .ord-addr { font-size: 14px; line-height: 1.7; color: var(--ink); }

        /* status pill */
        .ord-status {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
        .ord-status.paid     { background: color-mix(in srgb, #16a34a 16%, var(--paper)); color: #15803d; }
        .ord-status.shipped  { background: color-mix(in srgb, var(--ink) 8%, var(--paper)); color: var(--ink); }
        .ord-status.pending  { background: color-mix(in srgb, #d97706 16%, var(--paper)); color: #b45309; }
        .ord-status.failed,
        .ord-status.cancelled,
        .ord-status.refunded { background: color-mix(in srgb, #b91c1c 12%, var(--paper)); color: #b91c1c; }

        /* ----- items + totals ----- */
        .ord-items { border: 1px solid var(--line); background: var(--paper); }
        .ord-items > h3 {
            font-size: 11px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 700;
            padding: 24px 26px 0;
        }
        .ord-line {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 18px 26px;
            border-bottom: 1px solid var(--line);
        }
        .ord-line:first-of-type { border-top: 1px solid var(--line); margin-top: 18px; }
        .ord-line .name { font-size: 15px; font-weight: 500; }
        .ord-line .meta { font-size: 13px; color: var(--muted); margin-top: 3px; }
        .ord-line .price { font-family: var(--display); font-size: 17px; white-space: nowrap; }

        .ord-totals { padding: 20px 26px 26px; }
        .ord-totals .r {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #4f4a40;
            padding: 6px 0;
        }
        .ord-totals .r.discount { color: var(--accent); }
        .ord-totals .r .num { font-variant-numeric: tabular-nums; }
        .ord-totals .r code {
            font-family: "Hanken Grotesk", monospace;
            font-size: 12px;
            opacity: .7;
        }
        .ord-totals .grand {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            border-top: 1px solid var(--ink);
            margin-top: 12px;
            padding-top: 16px;
            font-size: 19px;
            font-weight: 600;
        }
        .ord-totals .grand .num { font-family: var(--display); }
        .ord-totals .fx { display: flex; justify-content: space-between; font-size: 12px; color: var(--muted); margin-top: 8px; }

        /* ----- actions ----- */
        .ord-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        @media (max-width: 640px) {
            .ord-grid { grid-template-columns: 1fr; }
            .ord-ship { flex-wrap: wrap; }
        }
    </style>

    <main>
        <div class="ord">
            <div class="ord-hero rv">
                <div class="ord-icon {{ $isBad ? 'bad' : ($isShipped ? 'info' : '') }}">
                    @if ($isCancelled || $isFailed) ×
                    @elseif ($isRefunded) ↺
                    @elseif ($isPending) ⏳
                    @elseif ($isShipped) ↗
                    @else ✓
                    @endif
                </div>
                <h1>
                    @if ($isCancelled) {{ __('site.order.cancelled_title') }}
                    @elseif ($isRefunded) {{ __('site.order.refunded_title') }}
                    @elseif ($isShipped) {{ __('site.order.shipped_title') }}
                    @elseif ($isFailed) {{ __('site.order.failed_title') }}
                    @elseif ($isPending) {{ __('site.order.pending_title') }}
                    @else {{ __('site.order.thanks_name', ['name' => explode(' ', $order->customer_name)[0]]) }}
                    @endif
                </h1>
                <p>
                    @if ($isCancelled) {{ __('site.order.cancelled_body') }}
                    @elseif ($isRefunded) {{ __('site.order.refunded_body') }}
                    @elseif ($isShipped) {{ __('site.order.shipped_body') }}
                    @elseif ($isFailed) {{ __('site.order.failed_body') }}
                    @elseif ($isPending) {{ __('site.order.pending_body') }}
                    @else {{ __('site.order.paid_body', ['email' => $order->customer_email]) }}
                    @endif
                </p>
                <span class="ord-num">{{ $order->order_number }}</span>
                @if ($isPending)
                    <meta http-equiv="refresh" content="4">
                @endif
            </div>

            @if ($isShipped && $order->tracking_number)
                @php
                    $carrierLabel = \App\Services\Shipping\CarrierRegistry::label($order->carrier);
                    $trackingHref = $order->tracking_url
                        ?: \App\Services\Shipping\CarrierRegistry::trackingUrlFor($order->carrier, $order->tracking_number);
                @endphp
                <div class="ord-ship rv">
                    <div class="ic">↗</div>
                    <div class="tx">
                        <h3>{{ __('site.order.shipped_via', ['carrier' => $carrierLabel]) }}</h3>
                        <div class="meta">{!! __('site.order.tracking_meta', ['code' => '<code>' . e($order->tracking_number) . '</code>', 'ago' => $order->shipped_at->diffForHumans()]) !!}</div>
                    </div>
                    @if ($trackingHref)
                        <a href="{{ $trackingHref }}" target="_blank" rel="noopener" class="track">{{ __('site.order.track_shipment') }}</a>
                    @endif
                </div>
            @endif

            <div class="ord-grid rv">
                <div class="ord-card">
                    <h3>{{ __('site.order.details_h3') }}</h3>
                    <div class="r"><span class="k">{{ __('site.order.status') }}</span><span class="v"><span class="ord-status {{ $order->status }}">{{ __('site.statuses.' . $order->status) }}</span></span></div>
                    <div class="r"><span class="k">{{ __('site.order.placed') }}</span><span class="v">{{ $order->created_at->isoFormat('LL') }}</span></div>
                    <div class="r"><span class="k">{{ __('site.order.customer') }}</span><span class="v">{{ $order->customer_name }}</span></div>
                    <div class="r"><span class="k">{{ __('site.order.email_label') }}</span><span class="v">{{ $order->customer_email }}</span></div>
                </div>

                @if ($order->shipping_address)
                    <div class="ord-card">
                        <h3>{{ __('site.order.shipping_to') }}</h3>
                        <div class="ord-addr">
                            {{ $order->customer_name }}<br>
                            {{ $order->shipping_address['line'] }}<br>
                            {{ $order->shipping_address['city'] }}, {{ $order->shipping_address['postal_code'] }}<br>
                            {{ $order->shipping_address['country'] }}
                        </div>
                    </div>
                @endif
            </div>

            <div class="ord-items rv">
                <h3>{{ __('site.order.items_ordered') }}</h3>
                @foreach ($order->items as $item)
                    <div class="ord-line">
                        <div>
                            <div class="name">{{ $item->displayName() }}</div>
                            <div class="meta">{{ __('site.order.qty_unit', ['qty' => $item->quantity, 'price' => \App\Services\Money::format($item->unit_price_cents, $order->currency)]) }}</div>
                        </div>
                        <div class="price">{{ \App\Services\Money::format($item->subtotal_cents, $order->currency) }}</div>
                    </div>
                @endforeach
                <div class="ord-totals">
                    <div class="r"><span>{{ __('site.order.subtotal') }}</span><span class="num">{{ \App\Services\Money::format($itemsTotal, $order->currency) }}</span></div>
                    <div class="r"><span>{{ __('site.order.shipping_label') }}@if($order->shipping_method_label) · {{ $order->shipping_method_label }}@endif</span><span class="num">{{ $shipping === 0 ? __('site.common.free') : \App\Services\Money::format($shipping, $order->currency) }}</span></div>
                    @if ($discountAmount > 0)
                        <div class="r discount"><span>{{ $order->discount_name ?: __('site.order.discount') }}@if($order->discount_code) <code>({{ $order->discount_code }})</code>@endif</span><span class="num">−{{ \App\Services\Money::format($discountAmount, $order->currency) }}</span></div>
                    @endif
                    <div class="grand"><span>{{ __('site.order.total') }}</span><span class="num">{{ \App\Services\Money::format($order->total_cents, $order->currency) }}</span></div>
                    @if ($order->display_currency && $order->display_currency !== $order->currency && $order->display_total_cents)
                        <div class="fx">
                            <span>{{ __('site.order.you_saw') }}</span>
                            <span>{{ \App\Services\Money::formatAsDisplay($order->display_total_cents, $order->display_currency, $order->total_cents, $order->currency) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="ord-actions rv">
                <a href="/" class="btn">{{ __('site.common.continue_shopping') }}</a>
                <a href="mailto:{{ $tenant->contact_email ?: 'support@example.com' }}?subject=Order%20{{ $order->order_number }}" class="btn outline">{{ __('site.order.need_help') }}</a>
            </div>
        </div>
    </main>
@endsection
