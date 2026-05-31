@php
    $title = 'Order ' . $order->order_number;
    $isShipped = $order->status === 'shipped'; $isPending = $order->status === 'pending';
    $isBad = in_array($order->status, ['cancelled','refunded','failed'], true);
    $itemsTotal = $order->items->sum('subtotal_cents');
    $discountAmount = (int) ($order->discount_amount_cents ?? 0);
    $shipping = $order->shipping_cents !== null && $order->shipping_cents > 0 ? (int) $order->shipping_cents : max(0, $order->total_cents + $discountAmount - $itemsTotal);
@endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .ord { max-width: 760px; margin: 0 auto; padding: 56px 36px 90px; }
        .ord-hero { text-align: center; margin-bottom: 44px; }
        .ord-icon { width: 60px; height: 60px; border-radius: 12px; margin: 0 auto 22px; display: grid; place-items: center; font-size: 26px; background: color-mix(in srgb, var(--accent) 16%, var(--surface)); color: var(--accent); border: 1px solid var(--line); }
        .ord-icon.bad { background: rgba(255,92,92,.1); color: #ff8a8a; }
        .ord-hero h1 { font-family: var(--archivo); font-weight: 800; font-size: clamp(30px,4vw,44px); letter-spacing: -.02em; }
        .ord-hero p { color: var(--muted); margin-top: 10px; max-width: 46ch; margin-left: auto; margin-right: auto; }
        .ord-num { display: inline-block; margin-top: 18px; padding: 8px 16px; border: 1px solid var(--line); border-radius: 6px; font-family: var(--mono); font-size: 13px; color: var(--accent); }
        .ord-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .ord-card { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 24px; }
        .ord-card h3 { font-family: var(--mono); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--faint); margin-bottom: 16px; }
        .ord-card .r { display: flex; justify-content: space-between; gap: 1rem; padding: 6px 0; font-size: 14px; }
        .ord-card .r .k { color: var(--muted); } .ord-card .addr { font-size: 14px; line-height: 1.7; }
        .ost { display: inline-block; padding: 3px 10px; border-radius: 5px; font-family: var(--mono); font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .ost.paid { background: color-mix(in srgb,#16a34a 22%,var(--surface)); color: #6ee7a0; } .ost.shipped { background: var(--surface2); color: var(--accent); }
        .ost.pending { background: color-mix(in srgb,#d97706 22%,var(--surface)); color: #fbbf6b; } .ost.failed,.ost.cancelled,.ost.refunded { background: rgba(255,92,92,.14); color: #ff8a8a; }
        .ord-items { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 24px; }
        .ord-items > h3 { font-family: var(--mono); font-size: 11px; text-transform: uppercase; color: var(--faint); margin-bottom: 8px; }
        .ord-line { display: flex; justify-content: space-between; gap: 1rem; padding: 14px 0; border-bottom: 1px solid var(--line); }
        .ord-line:first-of-type { border-top: 1px solid var(--line); margin-top: 12px; }
        .ord-line .nm { font-size: 15px; } .ord-line .meta { font-family: var(--mono); font-size: 12px; color: var(--muted); margin-top: 3px; }
        .ord-line .pr { font-family: var(--mono); color: var(--accent); white-space: nowrap; }
        .ord-tot { padding-top: 16px; } .ord-tot .r { display: flex; justify-content: space-between; font-size: 14px; padding: 5px 0; color: var(--muted); }
        .ord-tot .r .num { font-family: var(--mono); } .ord-tot .grand { display: flex; justify-content: space-between; border-top: 1px solid var(--line); margin-top: 10px; padding-top: 14px; font-size: 18px; font-weight: 700; }
        .ord-tot .grand .num { font-family: var(--mono); color: var(--accent); }
        .ord-actions { display: flex; gap: 12px; justify-content: center; margin-top: 36px; flex-wrap: wrap; }
        @media (max-width: 640px) { .ord-grid { grid-template-columns: 1fr; } }
    </style>

    <main>
        <div class="ord">
            <div class="ord-hero rv">
                <div class="ord-icon {{ $isBad ? 'bad' : '' }}">@if ($isBad) × @elseif ($isPending) ⏳ @elseif ($isShipped) ↗ @else ✓ @endif</div>
                <h1>@if ($order->status==='cancelled'){{ __('site.order.cancelled_title') }}@elseif($order->status==='refunded'){{ __('site.order.refunded_title') }}@elseif($isShipped){{ __('site.order.shipped_title') }}@elseif($order->status==='failed'){{ __('site.order.failed_title') }}@elseif($isPending){{ __('site.order.pending_title') }}@else{{ __('site.order.thanks_name', ['name' => explode(' ', $order->customer_name)[0]]) }}@endif</h1>
                <p>@if ($order->status==='cancelled'){{ __('site.order.cancelled_body') }}@elseif($order->status==='refunded'){{ __('site.order.refunded_body') }}@elseif($isShipped){{ __('site.order.shipped_body') }}@elseif($order->status==='failed'){{ __('site.order.failed_body') }}@elseif($isPending){{ __('site.order.pending_body') }}@else{{ __('site.order.paid_body', ['email' => $order->customer_email]) }}@endif</p>
                <span class="ord-num">{{ $order->order_number }}</span>
                @if ($isPending)<meta http-equiv="refresh" content="4">@endif
            </div>

            <div class="ord-grid rv">
                <div class="ord-card">
                    <h3>{{ __('site.order.details_h3') }}</h3>
                    <div class="r"><span class="k">{{ __('site.order.status') }}</span><span class="ost {{ $order->status }}">{{ __('site.statuses.' . $order->status) }}</span></div>
                    <div class="r"><span class="k">{{ __('site.order.placed') }}</span><span>{{ $order->created_at->isoFormat('LL') }}</span></div>
                    <div class="r"><span class="k">{{ __('site.order.customer') }}</span><span>{{ $order->customer_name }}</span></div>
                    <div class="r"><span class="k">{{ __('site.order.email_label') }}</span><span>{{ $order->customer_email }}</span></div>
                </div>
                @if ($order->shipping_address)
                    <div class="ord-card">
                        <h3>{{ __('site.order.shipping_to') }}</h3>
                        <div class="addr">{{ $order->customer_name }}<br>{{ $order->shipping_address['line'] }}<br>{{ $order->shipping_address['city'] }}, {{ $order->shipping_address['postal_code'] }}<br>{{ $order->shipping_address['country'] }}</div>
                    </div>
                @endif
            </div>

            <div class="ord-items rv">
                <h3>{{ __('site.order.items_ordered') }}</h3>
                @foreach ($order->items as $item)
                    <div class="ord-line"><div><div class="nm">{{ $item->displayName() }}</div><div class="meta">{{ __('site.order.qty_unit', ['qty' => $item->quantity, 'price' => \App\Services\Money::format($item->unit_price_cents, $order->currency)]) }}</div></div><div class="pr">{{ \App\Services\Money::format($item->subtotal_cents, $order->currency) }}</div></div>
                @endforeach
                <div class="ord-tot">
                    <div class="r"><span>{{ __('site.order.subtotal') }}</span><span class="num">{{ \App\Services\Money::format($itemsTotal, $order->currency) }}</span></div>
                    <div class="r"><span>{{ __('site.order.shipping_label') }}@if($order->shipping_method_label) · {{ $order->shipping_method_label }}@endif</span><span class="num">{{ $shipping === 0 ? __('site.common.free') : \App\Services\Money::format($shipping, $order->currency) }}</span></div>
                    @if ($discountAmount > 0)<div class="r"><span>{{ $order->discount_name ?: __('site.order.discount') }}</span><span class="num">−{{ \App\Services\Money::format($discountAmount, $order->currency) }}</span></div>@endif
                    <div class="grand"><span>{{ __('site.order.total') }}</span><span class="num">{{ \App\Services\Money::format($order->total_cents, $order->currency) }}</span></div>
                </div>
            </div>

            <div class="ord-actions rv">
                <a href="/" class="btn">{{ __('site.common.continue_shopping') }}</a>
                <a href="mailto:{{ $tenant->contact_email ?: 'support@example.com' }}?subject=Order%20{{ $order->order_number }}" class="btn ghost">{{ __('site.order.need_help') }}</a>
            </div>
        </div>
    </main>
@endsection
