@php
    $title = __('site.common.my_account');
@endphp
@extends("themes.{$theme}.layout")

@section('content')
    <style>
        .account-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
        }
        .account-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .account-head h1 {
            margin: 0;
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .account-head p {
            margin: .25rem 0 0;
            color: var(--text-muted, #57534e);
            font-size: 0.9375rem;
        }
        .logout-btn {
            background: var(--muted, #f5f5f4);
            color: var(--text, #1c1917);
            border: 0;
            padding: .625rem 1rem;
            border-radius: .5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
        }
        .logout-btn:hover { background: var(--border, #e7e5e4); }

        .card-block {
            background: var(--surface, white);
            border: 1px solid var(--border, #e7e5e4);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
        }
        .card-block h3 {
            margin: 0 0 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--text-muted, #57534e);
        }
        .order-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            padding: 1rem 0;
            border-top: 1px solid var(--border, #e7e5e4);
            align-items: center;
        }
        .order-row:first-of-type { border-top: 0; padding-top: 0; }
        .order-row .num { font-weight: 700; font-size: 0.9375rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: 0.03em; }
        .order-row .date { color: var(--text-muted, #57534e); font-size: 0.8125rem; margin-top: .125rem; }
        .order-row .total { font-weight: 700; color: var(--text, #1c1917); }
        .badge-status {
            display: inline-block;
            padding: .25rem .75rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-decoration: none;
        }
        .badge-status.paid     { background: #dcfce7; color: #166534; }
        .badge-status.shipped  { background: #dbeafe; color: #1e40af; }
        .badge-status.pending  { background: #fef3c7; color: #92400e; }
        .badge-status.refunded { background: #fee2e2; color: #991b1b; }
        .badge-status.cancelled{ background: #f3f4f6; color: #374151; }
        .order-row a.view {
            color: var(--primary);
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
        }
        .order-row a.view:hover { text-decoration: underline; }

        .empty {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--text-muted, #57534e);
        }
        .empty a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
    </style>

    <div class="account-page">
        <div class="account-head">
            <div>
                <h1>{{ __('site.account.hi', ['name' => explode(' ', $customer->name)[0]]) }}</h1>
                <p>{{ __('site.account.signed_in_as', ['email' => $customer->email]) }}</p>
            </div>
            <form method="post" action="/account/logout">
                @csrf
                <button type="submit" class="logout-btn">{{ __('site.account.sign_out') }}</button>
            </form>
        </div>

        <div class="card-block">
            <h3>{{ __('site.account.recent_orders') }}</h3>
            @if ($orders->isEmpty())
                <div class="empty">
                    {{ __('site.account.empty') }} <a href="/">{{ __('site.account.start_shopping') }}</a>
                </div>
            @else
                @foreach ($orders as $order)
                    <div class="order-row">
                        <div>
                            <div class="num">{{ $order->order_number }}</div>
                            <div class="date">{{ $order->created_at->isoFormat('LL') }} · {{ \App\Services\Money::format($order->total_cents, $order->currency) }}</div>
                        </div>
                        <span class="badge-status {{ $order->status }}">{{ __('site.statuses.' . $order->status) }}</span>
                        <a href="/orders/{{ $order->order_number }}" class="view">{{ __('site.account.view') }}</a>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection
