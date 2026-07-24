{{-- Timber — the order docket. The yard's own copy of the ticket: a stamped
     status head, the delivery note, the cutting list of everything that left
     the racks, and the tally ruled off at the foot. --}}
@php
    $title = 'Order ' . $order->order_number;
@endphp
@extends('themes.timber.layout')

@section('content')
    <style>
        /* ===== Order docket — a single ruled ticket down the middle of the
           yard office desk. Light surfaces, hard 2px walnut rules, mono spec
           labels, condensed-caps totals. ===== */
        .ord {
            position: relative; max-width: 860px; margin: 0 auto; padding: 46px 0 28px;
            /* rejected-stock ink — the one non-amber signal on the ticket */
            --ord-bad: #b06a4a; --ord-bad-ink: #9c5a3e; --ord-bad-soft: #f2ddd1;
            --ord-wait: #6b5a14; --ord-wait-soft: #f6ecc4; --ord-wait-line: #b79c3f;
        }
        .ord .ring.o1 { width: 200px; height: 200px; right: -76px; top: 4px; opacity: .45; }
        .ord .ring.o2 { width: 92px; height: 92px; left: -66px; top: 178px; opacity: .32; }

        /* ===== stamped head ===== */
        .ord-hero { position: relative; z-index: 1; text-align: center; margin-bottom: 34px; }
        .ord-hero .mark {
            width: 72px; height: 72px; margin: 0 auto 22px; display: grid; place-items: center;
            border: 1px solid var(--accent-deep); border-radius: 8px;
            background: var(--accent); color: var(--on-accent);
            font-size: 30px; line-height: 1; box-shadow: 0 2px 0 0 var(--accent-deep);
        }
        .ord-hero .mark.danger { background: var(--ord-bad); border-color: var(--ord-bad-ink); box-shadow: 0 2px 0 0 var(--ord-bad-ink); }
        /* still on the saw — the stamp breathes while the bank confirms */
        .ord-hero .mark.wait { animation: markwait 2.6s ease-in-out infinite; }
        @keyframes markwait {
            0%, 100% { box-shadow: 0 2px 0 0 var(--accent-deep); }
            50% { box-shadow: 0 2px 0 0 var(--accent-deep), 0 0 0 9px color-mix(in srgb, var(--accent) 14%, transparent); }
        }
        .ord-hero h1 {
            font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .01em;
            font-size: clamp(34px, 5vw, 56px); line-height: .98; margin-bottom: 12px;
        }
        .ord-hero h1 em { font-style: normal; color: var(--accent-deep); }
        .ord-hero p { color: var(--muted); max-width: 54ch; margin: 0 auto; }
        /* the docket number — inked mono plate, the way a load is tagged */
        .ord-hero .num {
            display: inline-flex; margin-top: 20px; align-items: center; gap: 9px;
            background: var(--surface); border: 1px solid var(--line2); border-radius: 6px;
            font-family: var(--mono); font-weight: 600; font-size: 13px; letter-spacing: .12em;
            text-transform: uppercase; color: var(--accent-deep);
            padding: 9px 16px; box-shadow: 0 2px 0 0 var(--line);
        }
        .ord-hero .num::before { content: "▮"; color: var(--accent); }
        .ord-hero .rule-ticks { max-width: 340px; margin: 26px auto 0; }

        /* ===== dispatch note — the load has left the yard ===== */
        .ord-ship {
            position: relative; z-index: 1;
            background: var(--surface); border: 1px solid var(--line); border-radius: 10px;
            box-shadow: 0 2px 0 0 var(--line);
            padding: 20px 22px; margin-bottom: 22px; display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
        }
        .ord-ship .ic {
            width: 46px; height: 46px; border-radius: 6px; border: 1px solid var(--accent-deep);
            background: var(--accent); color: var(--on-accent); display: grid; place-items: center;
            font-size: 20px; flex-shrink: 0; box-shadow: 0 2px 0 0 var(--accent-deep);
        }
        .ord-ship .tx { flex: 1; min-width: 200px; }
        .ord-ship h3 { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .02em; font-size: 20px; line-height: 1.1; margin-bottom: 4px; color: var(--txt); }
        .ord-ship .meta { font-family: var(--mono); font-size: 12px; letter-spacing: .04em; color: var(--muted); }
        .ord-ship .meta code { background: var(--surface2); border: 1px solid var(--line); border-radius: 4px; padding: 1px 7px; font-family: var(--mono); font-size: 12px; color: var(--txt); }

        /* ===== detail panels ===== */
        .ord-grid { position: relative; z-index: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .ord-card { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 2px 0 0 var(--line); padding: 22px 24px; }
        .ord-card > h3 {
            font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .02em;
            font-size: 20px; line-height: 1.1; color: var(--txt);
            margin-bottom: 14px; padding-bottom: 12px; border-bottom: 2px solid var(--txt);
        }
        .ord-card > h3::before { content: "▮ "; color: var(--accent); }
        .ord-card .row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; font-size: 14px; align-items: center; border-bottom: 1px solid var(--line); }
        .ord-card .row:last-child { border-bottom: none; }
        .ord-card .row .k { font-family: var(--mono); color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .08em; }
        .ord-card .row .v { color: var(--txt); font-weight: 600; text-align: right; }
        .ord-card .addr { font-size: 15px; line-height: 1.7; color: var(--txt); }

        /* status stamp — square grading chip, not a soft pill */
        .ord-badge {
            display: inline-flex; font-family: var(--mono); font-weight: 600; font-size: 10.5px;
            letter-spacing: .1em; text-transform: uppercase; padding: 4px 10px; border-radius: 4px;
            border: 1px solid var(--line2); background: var(--surface2); color: var(--txt);
        }
        .ord-badge.paid, .ord-badge.shipped { background: color-mix(in srgb, var(--accent) 16%, var(--surface)); border-color: var(--accent-deep); color: var(--accent-deep); }
        .ord-badge.pending { background: var(--ord-wait-soft); border-color: var(--ord-wait-line); color: var(--ord-wait); }
        .ord-badge.refunded, .ord-badge.cancelled, .ord-badge.failed { background: var(--ord-bad-soft); border-color: var(--ord-bad-ink); color: var(--ord-bad-ink); }

        /* ===== cutting list + tally ===== */
        .ord-items {
            position: relative; z-index: 1;
            background: var(--surface); border: 1px solid var(--line); border-radius: 10px;
            box-shadow: 0 2px 0 0 var(--line); overflow: hidden; counter-reset: ordline;
        }
        .ord-items > h3 {
            font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .02em;
            font-size: 20px; color: var(--txt); padding: 18px 24px; border-bottom: 2px solid var(--txt);
        }
        .ord-items > h3::before { content: "▮ "; color: var(--accent); }
        .ord-item { display: grid; grid-template-columns: auto 1fr auto; gap: 16px; align-items: center; padding: 15px 24px; border-bottom: 1px solid var(--line); counter-increment: ordline; }
        /* line numbering — the docket counts its rows like a cutting list */
        .ord-item::before {
            content: counter(ordline, decimal-leading-zero);
            font-family: var(--mono); font-size: 11px; letter-spacing: .1em; color: var(--faint);
            border: 1px solid var(--line); border-radius: 4px; padding: 3px 7px;
        }
        .ord-items.no-lot .ord-item { grid-template-columns: 1fr auto; }
        .ord-items.no-lot .ord-item::before { display: none; }
        .ord-item .name { font-family: var(--display); font-weight: 600; text-transform: uppercase; letter-spacing: .02em; font-size: 19px; line-height: 1.15; color: var(--txt); }
        .ord-item .meta { font-family: var(--mono); color: var(--muted); font-size: 11.5px; letter-spacing: .04em; text-transform: uppercase; margin-top: 4px; }
        .ord-item .pr { font-family: var(--display); font-weight: 700; font-size: 20px; font-variant-numeric: tabular-nums; white-space: nowrap; color: var(--txt); }

        .ord-tot { padding: 20px 24px 22px; background: var(--surface2); border-top: 1px solid var(--line); }
        .ord-tot .row { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; align-items: baseline; font-family: var(--mono); font-size: 11.5px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
        .ord-tot .row .num { font-family: var(--display); font-weight: 700; font-size: 17px; letter-spacing: 0; text-transform: none; color: var(--txt); font-variant-numeric: tabular-nums; }
        .ord-tot .row.disc .num { color: var(--accent-deep); }
        .ord-tot .row code { background: var(--surface); border: 1px solid var(--line); border-radius: 4px; padding: 0 6px; font-family: var(--mono); font-size: 11px; color: var(--txt); }
        .ord-tot .grand { padding-top: 14px; margin-top: 12px; border-top: 2px solid var(--txt); }
        .ord-tot .grand .label { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .04em; font-size: 19px; color: var(--txt); }
        .ord-tot .grand .num { font-size: 28px; color: var(--accent-deep); }

        .ord-actions { position: relative; z-index: 1; display: flex; gap: 14px; justify-content: center; margin-top: 34px; flex-wrap: wrap; }

        @media (max-width: 720px) {
            .ord-grid { grid-template-columns: 1fr; }
            .ord .ring.o1, .ord .ring.o2 { display: none; }
            .ord-card { padding: 18px 18px; }
            .ord-item { padding: 14px 18px; gap: 12px; }
            .ord-items > h3 { padding: 16px 18px; }
            .ord-tot { padding: 18px 18px 20px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .ord-hero .mark.wait { animation: none; }
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

                @if ($theme->on('grain_rings'))
                    <div class="ring o1" aria-hidden="true"></div>
                    <div class="ring o2" aria-hidden="true"></div>
                @endif

                <div class="ord-hero reveal">
                    <div class="mark {{ $markClass }} {{ $isPending ? 'wait' : '' }}"><span>{{ $iconChar }}</span></div>
                    <h1>
                        @if ($isCancelled)
                            {{ __('site.order.cancelled_title') }}
                        @elseif ($isRefunded)
                            {{ __('site.order.refunded_title') }}
                        @elseif ($isShipped)
                            {{ __('site.order.shipped_title') }}
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
                        @elseif ($isFailed)
                            {{ __('site.order.failed_body') }}
                        @elseif ($isPending)
                            {{ __('site.order.pending_body') }}
                        @else
                            {{ __('site.order.paid_body', ['email' => $order->customer_email]) }}
                        @endif
                    </p>
                    <span class="num">{{ $order->order_number }}</span>

                    @if ($theme->on('ruler'))
                        <div class="rule-ticks" aria-hidden="true"></div>
                    @endif

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

                <div class="ord-items reveal {{ $theme->on('lot_stamps') ? '' : 'no-lot' }}">
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
