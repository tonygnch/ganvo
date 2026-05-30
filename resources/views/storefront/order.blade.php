@php
    $title = 'Order ' . $order->order_number;
@endphp
@extends("themes.{$theme}.layout")

@section('content')
    <style>
        .order-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
        }

        /* -------- Success hero -------- */
        .success-hero {
            text-align: center;
            padding: 2.5rem 1.5rem 1.5rem;
            margin-bottom: 2rem;
        }
        .success-icon {
            width: 64px; height: 64px;
            margin: 0 auto 1.25rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: color-mix(in srgb, var(--primary) 15%, transparent);
            color: var(--primary-strong, var(--primary));
            box-shadow: 0 0 0 8px color-mix(in srgb, var(--primary) 6%, transparent);
        }
        .success-icon.danger    { background: #fee2e2; color: #dc2626; box-shadow: 0 0 0 8px rgba(220,38,38,0.08); }
        .success-icon.warning   { background: #fef3c7; color: #d97706; box-shadow: 0 0 0 8px rgba(217,119,6,0.08); }
        .success-icon.info      { background: #dbeafe; color: #1d4ed8; box-shadow: 0 0 0 8px rgba(29,78,216,0.08); }
        .success-hero h1 {
            margin: 0 0 .5rem;
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .success-hero p {
            color: var(--text-muted, #57534e);
            font-size: 1rem;
            margin: 0;
        }
        .success-hero .order-num {
            display: inline-block;
            margin-top: 1rem;
            background: var(--muted, #f5f5f4);
            color: var(--text, #1c1917);
            padding: .375rem 1rem;
            border-radius: 9999px;
            font: 600 0.875rem ui-monospace, SFMono-Regular, Menlo, monospace;
            letter-spacing: 0.04em;
        }

        /* -------- Shipping banner -------- */
        .ship-banner {
            background: color-mix(in srgb, var(--primary) 8%, transparent);
            border: 1px solid color-mix(in srgb, var(--primary) 28%, transparent);
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
        }
        .ship-banner .icon {
            width: 40px; height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .ship-banner .text { flex: 1; min-width: 200px; }
        .ship-banner h3 { margin: 0 0 .25rem; font-size: 1rem; color: var(--text, #1c1917); }
        .ship-banner .meta { font-size: 0.875rem; color: var(--text-muted, #57534e); }
        .ship-banner .meta code {
            background: rgba(0,0,0,0.05);
            padding: .125rem .375rem;
            border-radius: .25rem;
            font: inherit;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.8125rem;
        }
        .track-link {
            background: var(--primary);
            color: white;
            padding: .5rem 1rem;
            border-radius: .5rem;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .track-link:hover { background: var(--primary-strong, var(--primary)); }

        /* -------- Detail cards -------- */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .card-block {
            background: var(--surface, white);
            border: 1px solid var(--border, #e7e5e4);
            border-radius: 1rem;
            padding: 1.5rem;
        }
        .card-block h3 {
            margin: 0 0 .875rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--text-muted, #57534e);
        }
        .card-block .row {
            display: flex;
            justify-content: space-between;
            padding: .25rem 0;
            font-size: 0.9375rem;
        }
        .card-block .row .k { color: var(--text-muted, #57534e); }
        .card-block .row .v { color: var(--text, #1c1917); font-weight: 500; text-align: right; }
        .card-block .addr {
            color: var(--text, #1c1917);
            font-size: 0.9375rem;
            line-height: 1.5;
        }
        .badge-status {
            display: inline-block;
            padding: .25rem .75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-status.paid     { background: #dcfce7; color: #166534; }
        .badge-status.shipped  { background: #dbeafe; color: #1e40af; }
        .badge-status.pending  { background: #fef3c7; color: #92400e; }
        .badge-status.refunded { background: #fee2e2; color: #991b1b; }
        .badge-status.cancelled{ background: #f3f4f6; color: #374151; }

        /* -------- Items table -------- */
        .items-card { padding: 0; overflow: hidden; }
        .items-card h3 { padding: 1.25rem 1.5rem .25rem; margin: 0; }
        .item-line {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border, #e7e5e4);
            align-items: center;
        }
        .item-line .name { font-weight: 500; color: var(--text, #1c1917); }
        .item-line .meta { color: var(--text-muted, #57534e); font-size: 0.875rem; margin-top: .125rem; }
        .item-line .price { font-weight: 600; color: var(--text, #1c1917); }

        .totals {
            padding: 1rem 1.5rem 1.25rem;
            background: var(--muted, #f5f5f4);
            border-top: 1px solid var(--border, #e7e5e4);
        }
        .totals .row {
            display: flex;
            justify-content: space-between;
            padding: .25rem 0;
            font-size: 0.9375rem;
            color: var(--text-muted, #57534e);
        }
        .totals .row .num { color: var(--text, #1c1917); font-weight: 500; }
        .totals .row.grand {
            padding-top: .75rem;
            margin-top: .5rem;
            border-top: 1px solid var(--border, #e7e5e4);
        }
        .totals .row.grand .label { color: var(--text, #1c1917); font-weight: 700; font-size: 1rem; }
        .totals .row.grand .num {
            color: var(--primary-strong, var(--primary));
            font-weight: 800;
            font-size: 1.375rem;
        }

        .actions {
            display: flex;
            gap: .75rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: .875rem 1.5rem;
            border-radius: .625rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9375rem;
            transition: transform .12s ease, background-color .2s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-strong, var(--primary)); }
        .btn-ghost { background: var(--muted, #f5f5f4); color: var(--text, #1c1917); }
        .btn-ghost:hover { background: var(--border, #e7e5e4); }

        @media (max-width: 720px) {
            .detail-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="order-page">
        <div class="success-hero">
            @php
                $isShipped = $order->status === 'shipped';
                $isCancelled = $order->status === 'cancelled';
                $isRefunded = $order->status === 'refunded';
                $isPending = $order->status === 'pending';
                $isFailed = $order->status === 'failed';
                $iconClass = ($isCancelled || $isRefunded || $isFailed) ? 'danger' : ($isShipped ? 'info' : '');
                $iconChar = $isCancelled || $isFailed ? '×' : ($isRefunded ? '↺' : ($isPending ? '⏳' : '✓'));
            @endphp
            <div class="success-icon {{ $iconClass }}">
                <span style="font-size: 1.875rem; font-weight: 700; line-height: 1;">{{ $iconChar }}</span>
            </div>
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
            <span class="order-num">{{ $order->order_number }}</span>

            @if ($isPending)
                {{-- Auto-refresh every 4 seconds while pending — the
                     webhook (or OrderController's reconcile pull on
                     next render) flips status → paid. --}}
                <meta http-equiv="refresh" content="4">
            @endif
        </div>

        @if ($isShipped && $order->tracking_number)
            @php
                $carrierLabel = match ($order->carrier) {
                    'usps' => 'USPS', 'ups' => 'UPS', 'fedex' => 'FedEx', 'dhl' => 'DHL',
                    default => ucfirst((string) $order->carrier),
                };
            @endphp
            <div class="ship-banner">
                <div class="icon">↗</div>
                <div class="text">
                    <h3>{{ __('site.order.shipped_via', ['carrier' => $carrierLabel]) }}</h3>
                    <div class="meta">{!! __('site.order.tracking_meta', ['code' => '<code>' . e($order->tracking_number) . '</code>', 'ago' => $order->shipped_at->diffForHumans()]) !!}</div>
                </div>
                @if ($order->tracking_url)
                    <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener" class="track-link">{{ __('site.order.track_shipment') }}</a>
                @endif
            </div>
        @endif

        <div class="detail-grid">
            <div class="card-block">
                <h3>{{ __('site.order.details_h3') }}</h3>
                <div class="row"><span class="k">{{ __('site.order.status') }}</span><span class="v"><span class="badge-status {{ $order->status }}">{{ __('site.statuses.' . $order->status) }}</span></span></div>
                <div class="row"><span class="k">{{ __('site.order.placed') }}</span><span class="v">{{ $order->created_at->isoFormat('LL') }}</span></div>
                <div class="row"><span class="k">{{ __('site.order.customer') }}</span><span class="v">{{ $order->customer_name }}</span></div>
                <div class="row"><span class="k">{{ __('site.order.email_label') }}</span><span class="v">{{ $order->customer_email }}</span></div>
            </div>

            @if ($order->shipping_address)
                <div class="card-block">
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

        <div class="card-block items-card">
            <h3>{{ __('site.order.items_ordered') }}</h3>
            @php
                $itemsTotal = $order->items->sum('subtotal_cents');
                $discountAmount = (int) ($order->discount_amount_cents ?? 0);
                // Prefer the snapshotted shipping_cents (set on orders
                // placed after the better-checkout slice) — fall back to
                // derived shipping = total + discount − items for legacy
                // orders that pre-date the column.
                $shipping = $order->shipping_cents !== null && $order->shipping_cents > 0
                    ? (int) $order->shipping_cents
                    : max(0, $order->total_cents + $discountAmount - $itemsTotal);
            @endphp
            @foreach ($order->items as $item)
                <div class="item-line">
                    <div>
                        <div class="name">{{ $item->displayName() }}</div>
                        <div class="meta">{{ __('site.order.qty_unit', ['qty' => $item->quantity, 'price' => \App\Services\Money::format($item->unit_price_cents, $order->currency)]) }}</div>
                    </div>
                    <div class="price">{{ \App\Services\Money::format($item->subtotal_cents, $order->currency) }}</div>
                </div>
            @endforeach
            <div class="totals">
                <div class="row"><span>{{ __('site.order.subtotal') }}</span><span class="num">{{ \App\Services\Money::format($itemsTotal, $order->currency) }}</span></div>
                <div class="row"><span>{{ __('site.order.shipping_label') }}@if($order->shipping_method_label) <span style="opacity:.6">· {{ $order->shipping_method_label }}</span>@endif</span><span class="num">{{ $shipping === 0 ? __('site.common.free') : \App\Services\Money::format($shipping, $order->currency) }}</span></div>
                @if ($discountAmount > 0)
                    <div class="row discount"><span>{{ $order->discount_name ?: __('site.order.discount') }}@if($order->discount_code) <code class="discount-code">({{ $order->discount_code }})</code>@endif</span><span class="num">−{{ \App\Services\Money::format($discountAmount, $order->currency) }}</span></div>
                @endif
                <div class="row grand"><span class="label">{{ __('site.order.total') }}</span><span class="num">{{ \App\Services\Money::format($order->total_cents, $order->currency) }}</span></div>
                @if ($order->display_currency && $order->display_currency !== $order->currency && $order->display_total_cents)
                    <div class="row" style="margin-top: .5rem; font-size: 0.8125rem; opacity: .8;">
                        <span>{{ __('site.order.you_saw') }}</span>
                        <span class="num">{{ \App\Services\Money::formatAsDisplay($order->display_total_cents, $order->display_currency, $order->total_cents, $order->currency) }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="actions">
            <a href="/" class="btn btn-primary">{{ __('site.common.continue_shopping') }}</a>
            <a href="mailto:{{ $tenant->contact_email ?: 'support@example.com' }}?subject=Order%20{{ $order->order_number }}" class="btn btn-ghost">{{ __('site.order.need_help') }}</a>
        </div>
    </div>
@endsection
