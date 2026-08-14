{{-- Sankevi — the delivery note. The yard's own copy of the ticket: a stamped
     head, the dispatch line, the list of what left the racks, and the tally
     ruled off at the foot.

     TWO FLOWS. An order created through the enquiry checkout carries
     order_flow = 'enquiry' (see CheckoutController) and was NEVER PAID — so
     on that branch the page may not thank anyone for a payment, may not show
     a payment status, and may not poll for a webhook that will never arrive.
     It confirms that the list was received, gives the reference, restates
     what happens next and shows the lines as a submitted list with an
     estimated value. The payment flow renders exactly as it did before. --}}
@php
    // Belt and braces: order_flow is the column of record, but it defaults to
    // 'payment' and older rows predate it — payment_method is the second
    // witness, stamped in the same transaction.
    $isEnquiry = ($order->order_flow ?? \App\Models\Store::FLOW_PAYMENT) === \App\Models\Store::FLOW_ENQUIRY
        || $order->payment_method === 'enquiry';

    $title = ($isEnquiry ? __('site.storefront.sankevi.ord_ref') . ' ' : 'Order ') . $order->order_number;
@endphp
@extends('themes.sankevi.layout')

@section('content')
    <style>
        .ord {
            position: relative; max-width: 880px; margin: 0 auto; padding: 56px 0 40px;
            /* the one non-lichen ink on the ticket: rejected stock */
            --ord-bad: #c07a5c; --ord-bad-soft: color-mix(in srgb, #c07a5c 16%, var(--surface));
            --ord-wait: #c9b26a; --ord-wait-soft: color-mix(in srgb, #c9b26a 14%, var(--surface));
        }

        /* ===== stamped head ===== */
        .ord-hero { text-align: center; margin-bottom: 46px; }
        .ord-hero .mark { width: 64px; height: 64px; margin: 0 auto 26px; display: grid; place-items: center; background: var(--accent); color: var(--on-accent); font-size: 26px; line-height: 1; }
        .ord-hero .mark.danger { background: var(--ord-bad); }
        .ord-hero .mark.wait { animation: markwait 2.8s ease-in-out infinite; }
        @keyframes markwait {
            0%, 100% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--accent) 40%, transparent); }
            60% { box-shadow: 0 0 0 12px transparent; }
        }
        .ord-hero h1 { font-family: var(--display); font-weight: 500; font-size: clamp(28px, 3.8vw, 45px); line-height: 1.02; letter-spacing: 0; margin-bottom: 16px; }
        .ord-hero h1 em { font-style: normal; font-weight: 600; color: var(--accent-ink); }
        .ord-hero p { color: var(--muted); max-width: 56ch; margin: 0 auto; }
        .ord-hero .num { display: inline-flex; align-items: baseline; gap: 12px; margin-top: 24px; padding: 10px 18px; border: 1px solid var(--line2); font-size: 11px; font-weight: 500; letter-spacing: .24em; text-transform: uppercase; color: var(--accent-ink); }
        .ord-hero .num b { font-weight: 500; color: var(--faint); }

        /* ===== dispatch note ===== */
        .ord-ship { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; background: var(--surface); border: 1px solid var(--line); padding: 22px 24px; margin-bottom: 22px; }
        .ord-ship .ic { flex-shrink: 0; width: 44px; height: 44px; display: grid; place-items: center; background: var(--accent); color: var(--on-accent); font-size: 19px; }
        .ord-ship .tx { flex: 1; min-width: 200px; }
        .ord-ship h3 { font-family: var(--display); font-weight: 500; font-size: 21px; line-height: 1.1; margin-bottom: 5px; }
        .ord-ship .meta { font-size: 12.5px; color: var(--muted); }
        .ord-ship .meta code { padding: 2px 8px; background: var(--surface2); border: 1px solid var(--line); font-family: var(--body); font-size: 12px; letter-spacing: .08em; color: var(--txt); }

        /* ===== detail panels ===== */
        .ord-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 22px; }
        .ord-card { background: var(--surface); border: 1px solid var(--line); padding: 26px 26px 22px; }
        .ord-card > h3 { font-family: var(--display); font-weight: 500; font-size: 21px; line-height: 1.1; padding-bottom: 14px; margin-bottom: 16px; border-bottom: 1px solid var(--line2); }
        .ord-card .row { display: flex; justify-content: space-between; align-items: center; gap: 14px; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 14px; }
        .ord-card .row:last-child { border-bottom: none; }
        .ord-card .row .k { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); }
        .ord-card .row .v { text-align: right; color: var(--txt); }
        .ord-card .addr { font-size: 15px; line-height: 1.75; color: var(--txt); }

        .ord-badge { display: inline-flex; padding: 5px 11px; border: 1px solid var(--line2); font-size: 10px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); }
        .ord-badge.paid, .ord-badge.shipped { border-color: var(--accent); color: var(--accent-ink); }
        .ord-badge.pending { border-color: var(--ord-wait); background: var(--ord-wait-soft); color: var(--ord-wait); }
        .ord-badge.refunded, .ord-badge.cancelled, .ord-badge.failed { border-color: var(--ord-bad); background: var(--ord-bad-soft); color: var(--ord-bad); }
        /* An enquiry sitting at 'pending' is not a stalled payment — it is a
           request in the queue, and it gets the yard's own colour, not a warning. */
        .ord-badge.req { border-color: var(--accent); color: var(--accent-ink); }

        /* ===== what happens next — the enquiry's promise, ruled ===== */
        .ord-next { background: var(--surface); border: 1px solid var(--line); padding: 26px 26px 22px; margin-bottom: 22px; }
        .ord-next > h3 { font-family: var(--display); font-weight: 500; font-size: 21px; line-height: 1.1; padding-bottom: 14px; margin-bottom: 6px; border-bottom: 1px solid var(--line2); }
        .ord-next ol { list-style: none; counter-reset: nx; }
        .ord-next li { counter-increment: nx; display: grid; grid-template-columns: 34px 1fr; gap: 16px; align-items: start; padding: 14px 0; border-bottom: 1px solid var(--line); font-size: 14.5px; color: var(--muted); }
        .ord-next li:last-child { border-bottom: none; padding-bottom: 0; }
        .ord-next li::before { content: counter(nx, decimal-leading-zero); font-size: 10.5px; font-weight: 500; letter-spacing: .18em; color: var(--accent-ink); padding-top: 5px; }

        /* ===== the list + the tally ===== */
        .ord-items { background: var(--surface); border: 1px solid var(--line); counter-reset: ordline; }
        .ord-items > h3 { font-family: var(--display); font-weight: 500; font-size: 21px; padding: 20px 26px; border-bottom: 1px solid var(--line2); }
        .ord-item { display: grid; grid-template-columns: auto 1fr auto; gap: 18px; align-items: center; padding: 17px 26px; border-bottom: 1px solid var(--line); counter-increment: ordline; }
        .ord-item::before { content: counter(ordline, decimal-leading-zero); font-size: 10.5px; font-weight: 500; letter-spacing: .18em; color: var(--accent-ink); }
        .ord-items.no-sheet .ord-item { grid-template-columns: 1fr auto; }
        .ord-items.no-sheet .ord-item::before { display: none; }
        .ord-item .name { font-family: var(--display); font-weight: 500; font-size: 20px; line-height: 1.15; }
        /* the cut, on its own line — a cutting list is read by its dimensions */
        .ord-item .cutline { margin-top: 5px; font-size: 13px; color: var(--txt); }
        .ord-item .meta { margin-top: 4px; font-size: 10.5px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; color: var(--muted); }
        .ord-item .pr { font-family: var(--display); font-weight: 500; font-size: 19px; font-variant-numeric: tabular-nums; white-space: nowrap; }

        .ord-tot { padding: 22px 26px 24px; background: var(--surface2); border-top: 1px solid var(--line); }
        .ord-tot .row { display: flex; justify-content: space-between; align-items: baseline; gap: 14px; padding: 7px 0; font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); }
        .ord-tot .row .num { font-family: var(--display); font-weight: 500; font-size: 17px; letter-spacing: 0; text-transform: none; color: var(--txt); font-variant-numeric: tabular-nums; }
        .ord-tot .row.disc .num { color: var(--accent-ink); }
        .ord-tot .row code { padding: 1px 7px; background: var(--surface); border: 1px solid var(--line); font-family: var(--body); font-size: 11px; color: var(--txt); }
        .ord-tot .grand { padding-top: 16px; margin-top: 14px; border-top: 1px solid var(--line2); }
        .ord-tot .grand .label { font-family: var(--display); font-weight: 500; font-size: 19px; letter-spacing: 0; text-transform: none; color: var(--txt); }
        .ord-tot .grand .num { font-size: 27px; color: var(--accent-ink); }
        .ord-tot .grand .approx { color: var(--faint); font-size: 20px; margin-right: 3px; }
        .ord-tot .est-note { margin-top: 14px; padding-left: 13px; border-left: 1px solid var(--line2); font-size: 12.5px; line-height: 1.6; letter-spacing: 0; text-transform: none; color: var(--muted); }

        .ord-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-top: 40px; }

        @media (max-width: 720px) {
            .ord-grid { grid-template-columns: 1fr; }
            .ord-card, .ord-next { padding: 20px 20px 18px; }
            .ord-item { padding: 15px 18px; gap: 14px; }
            .ord-items > h3, .ord-tot { padding-left: 18px; padding-right: 18px; }
        }
        @media (prefers-reduced-motion: reduce) { .ord-hero .mark.wait { animation: none; } }
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
                    // An enquiry at 'pending' is a request safely received, not a
                    // payment in flight: it gets the tick, never the hourglass.
                    $iconChar = $isCancelled || $isFailed
                        ? '×'
                        : ($isRefunded ? '↺' : (($isPending && ! $isEnquiry) ? '⏳' : '✓'));

                    // The request's own state — deliberately NOT the payment status.
                    $enqState = match ($order->status) {
                        'pending' => __('site.storefront.sankevi.ord_state_pending'),
                        'paid' => __('site.storefront.sankevi.ord_state_confirmed'),
                        default => __('site.statuses.' . $order->status),
                    };
                    $enqBadge = in_array($order->status, ['cancelled', 'refunded', 'failed'], true)
                        ? $order->status
                        : 'req';
                @endphp

                <div class="ord-hero reveal">
                    <div class="mark cut cut-sm {{ $markClass }} {{ ($isPending && ! $isEnquiry) ? 'wait' : '' }}"><span>{{ $iconChar }}</span></div>
                    <h1>
                        @if ($isCancelled)
                            {{ __('site.order.cancelled_title') }}
                        @elseif ($isRefunded)
                            {{ __('site.order.refunded_title') }}
                        @elseif ($isShipped)
                            {{ __('site.order.shipped_title') }}
                        @elseif ($isEnquiry)
                            {{-- No thanks-for-your-payment: nothing was paid. --}}
                            {{ $order->customer_name
                                ? __('site.storefront.sankevi.ord_title', ['name' => explode(' ', $order->customer_name)[0]])
                                : __('site.storefront.sankevi.ord_title_anon') }}
                        @elseif ($isFailed)
                            {{ __('site.order.failed_title') }}
                        @elseif ($isPending)
                            {{ __('site.order.pending_title') }}
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
                        @elseif ($isEnquiry)
                            {{ __('site.storefront.sankevi.ord_body', ['email' => $order->customer_email]) }}
                        @elseif ($isFailed)
                            {{ __('site.order.failed_body') }}
                        @elseif ($isPending)
                            {{ __('site.order.pending_body') }}
                        @else
                            {{ __('site.order.paid_body', ['email' => $order->customer_email]) }}
                        @endif
                    </p>
                    <span class="num">@if ($isEnquiry)<b>{{ __('site.storefront.sankevi.ord_ref') }}</b>@endif{{ $order->order_number }}</span>

                    @if ($isPending && ! $isEnquiry)
                        {{-- Auto-refresh while pending — the webhook (or the controller's
                             reconcile pull on next render) flips status → paid. An
                             enquiry has no webhook coming, so it must NOT poll. --}}
                        <meta http-equiv="refresh" content="4">
                    @endif
                </div>

                @if ($isShipped && $order->tracking_number)
                    @php
                        $carrierLabel = \App\Services\Shipping\CarrierRegistry::label($order->carrier);
                        $trackingHref = $order->tracking_url
                            ?: \App\Services\Shipping\CarrierRegistry::trackingUrlFor($order->carrier, $order->tracking_number);
                    @endphp
                    <div class="ord-ship cut reveal">
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
                    <div class="ord-card cut reveal">
                        @if ($isEnquiry)
                            <h3>{{ __('site.storefront.sankevi.ord_state') }}</h3>
                            <div class="row"><span class="k">{{ __('site.order.status') }}</span><span class="v"><span class="ord-badge {{ $enqBadge }}">{{ $enqState }}</span></span></div>
                            <div class="row"><span class="k">{{ __('site.storefront.sankevi.ord_submitted') }}</span><span class="v">{{ $order->created_at->isoFormat('LL') }}</span></div>
                            <div class="row"><span class="k">{{ __('site.order.customer') }}</span><span class="v">{{ $order->customer_name }}</span></div>
                            <div class="row"><span class="k">{{ __('site.order.email_label') }}</span><span class="v">{{ $order->customer_email }}</span></div>
                            @if ($order->customer_phone)
                                <div class="row"><span class="k">{{ __('site.storefront.sankevi.ord_phone') }}</span><span class="v">{{ $order->customer_phone }}</span></div>
                            @endif
                        @else
                            <h3>{{ __('site.order.details_h3') }}</h3>
                            <div class="row"><span class="k">{{ __('site.order.status') }}</span><span class="v"><span class="ord-badge {{ $order->status }}">{{ __('site.statuses.' . $order->status) }}</span></span></div>
                            <div class="row"><span class="k">{{ __('site.order.placed') }}</span><span class="v">{{ $order->created_at->isoFormat('LL') }}</span></div>
                            <div class="row"><span class="k">{{ __('site.order.customer') }}</span><span class="v">{{ $order->customer_name }}</span></div>
                            <div class="row"><span class="k">{{ __('site.order.email_label') }}</span><span class="v">{{ $order->customer_email }}</span></div>
                        @endif
                    </div>

                    @if ($order->shipping_address)
                        <div class="ord-card cut reveal s1">
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

                @if ($isEnquiry && ! $isCancelled && ! $isShipped)
                    {{-- Same three promises the send sheet made, repeated here so
                         the confirmation is a standalone answer to "now what?". --}}
                    <div class="ord-next cut reveal">
                        <h3>{{ __('site.storefront.sankevi.next_h') }}</h3>
                        <ol>
                            <li>{{ __('site.storefront.sankevi.next_1') }}</li>
                            <li>{{ __('site.storefront.sankevi.next_2') }}</li>
                            <li>{{ __('site.storefront.sankevi.next_3') }}</li>
                        </ol>
                    </div>
                @endif

                <div class="ord-items cut reveal {{ $theme->on('sheet_marks') ? '' : 'no-sheet' }}">
                    <h3>{{ $isEnquiry ? __('site.storefront.sankevi.ord_list_h') : __('site.order.items_ordered') }}</h3>
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
                                {{-- product name and chosen cut on separate lines: the
                                     snapshot carries them separately, and a list is read
                                     by its dimensions. --}}
                                <div class="name">{{ $item->product_name }}</div>
                                @if ($item->measure_quantity)
                                    <div class="cutline">{{ rtrim(rtrim(number_format($item->measure_quantity, 2, '.', ''), '0'), '.') }} {{ $item->measure_unit }}</div>
                                @endif
                                @if ($item->variant_label)
                                    <div class="cutline">{{ $item->variant_label }}</div>
                                @endif
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
                        <div class="row grand">
                            <span class="label">{{ $isEnquiry ? __('site.storefront.sankevi.ord_est_total') : __('site.order.total') }}</span>
                            <span class="num">@if ($isEnquiry)<span class="approx" aria-hidden="true">≈</span>@endif{{ \App\Services\Money::format($order->total_cents, $order->currency) }}</span>
                        </div>
                        @if ($order->display_currency && $order->display_currency !== $order->currency && $order->display_total_cents)
                            <div class="row" style="margin-top: .5rem; opacity: .8;">
                                <span>{{ __('site.order.you_saw') }}</span>
                                <span class="num" style="font-size: 14px;">{{ \App\Services\Money::formatAsDisplay($order->display_total_cents, $order->display_currency, $order->total_cents, $order->currency) }}</span>
                            </div>
                        @endif
                        @if ($isEnquiry)
                            <p class="est-note">{{ __('site.storefront.sankevi.ord_est_note') }}</p>
                        @endif
                    </div>
                </div>

                <div class="ord-actions">
                    <a href="{{ $isEnquiry ? '/shop' : '/' }}" class="btn">{{ $isEnquiry ? __('site.storefront.sankevi.ord_back') : __('site.common.continue_shopping') }}</a>
                    <a href="mailto:{{ $tenant->contact_email ?: 'support@example.com' }}?subject={{ rawurlencode(($isEnquiry ? __('site.storefront.sankevi.ord_ref') : 'Order') . ' ' . $order->order_number) }}" class="btn outline">{{ __('site.order.need_help') }}</a>
                </div>
            </div>
        </div>
    </main>
@endsection
