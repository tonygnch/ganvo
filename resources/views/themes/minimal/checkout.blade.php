@php
    $title = __('site.checkout.title');
@endphp
@extends('themes.minimal.layout')

@section('content')
    <style>
        .checkout-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 4rem 2rem 5rem;
        }
        .checkout-head {
            text-align: center;
            margin-bottom: 3rem;
        }
        .checkout-head .eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 .75rem;
        }
        .checkout-head h1 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 400;
            font-size: clamp(2.25rem, 4vw, 3.25rem);
            letter-spacing: -0.01em;
            margin: 0;
            line-height: 1.1;
        }
        .checkout-head .back {
            display: inline-block;
            margin-top: 1.25rem;
            color: var(--text-muted);
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            transition: color .15s ease;
        }
        .checkout-head .back:hover { color: var(--text); }

        .account-prompt {
            border: 1px solid var(--hair);
            padding: 1.125rem 1.5rem;
            margin-bottom: 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1.0625rem;
            color: var(--text-muted);
        }
        .account-prompt a {
            color: var(--text);
            font-style: normal;
            font-family: system-ui, sans-serif;
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            border-bottom: 1px solid var(--text);
            padding-bottom: 1px;
            transition: color .2s ease, border-color .2s ease;
        }
        .account-prompt a:hover { color: var(--primary); border-color: var(--primary); }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 4rem;
            align-items: start;
        }

        /* -------- Form sections -------- */
        .section {
            padding: 2rem 0;
            border-bottom: 1px solid var(--hair);
        }
        .section:first-of-type { padding-top: 0; }
        .section-title {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 0 0 1.75rem;
            font-size: 0.75rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            font-weight: 500;
            color: var(--text);
        }
        .section-title .step {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1.5rem;
            color: var(--primary);
            letter-spacing: 0;
            text-transform: none;
            font-weight: 400;
            line-height: 1;
        }

        .signed-in {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1.0625rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        .signed-in strong { color: var(--text); font-style: normal; }

        .field { margin-bottom: 1.5rem; }
        .field:last-child { margin-bottom: 0; }
        .fields-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        label.field-label {
            display: block;
            font-size: 0.625rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: .5rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }
        input.field-input {
            width: 100%;
            padding: .625rem 0;
            border: 0;
            border-bottom: 1px solid var(--hair);
            background: transparent;
            color: var(--text);
            font: inherit;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.125rem;
            transition: border-color .2s ease;
        }
        input.field-input:focus {
            outline: none;
            border-bottom-color: var(--text);
        }
        input.field-input::placeholder { color: var(--text-soft); font-style: italic; }

        .stub-notice {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem 0 0;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1.0625rem;
            color: var(--text-muted);
            line-height: 1.6;
        }
        .stub-notice .icon {
            flex-shrink: 0;
            width: 28px; height: 28px;
            border: 1px solid var(--text);
            color: var(--text);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1rem;
        }
        .stub-notice strong {
            display: block;
            margin-bottom: .25rem;
            color: var(--text);
            font-style: normal;
            font-family: system-ui, sans-serif;
            font-size: 0.6875rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            font-weight: 500;
        }

        .errors {
            border-top: 1px solid var(--text);
            border-bottom: 1px solid var(--text);
            padding: 1.25rem 0;
            margin-bottom: 2rem;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1.0625rem;
            color: var(--text);
        }
        .errors-label {
            font-family: system-ui, sans-serif;
            font-size: 0.6875rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--primary);
            font-style: normal;
            font-weight: 500;
            margin-bottom: .5rem;
            display: block;
        }
        .errors ul { margin: 0; padding-left: 1.25rem; }

        /* -------- Summary -------- */
        .summary {
            position: sticky;
            top: 8rem;
        }
        .summary h2 {
            font-size: 0.6875rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            margin: 0 0 2rem;
            font-weight: 500;
            color: var(--text);
        }
        .line-items {
            border-top: 1px solid var(--hair);
            border-bottom: 1px solid var(--hair);
            padding: 1rem 0;
            margin-bottom: 1.5rem;
        }
        .line {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: .75rem 0;
        }
        .line + .line { border-top: 1px solid var(--hair); }
        .line-thumb {
            width: 56px;
            aspect-ratio: 4 / 5;
            background: var(--muted);
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
        }
        .line-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .line-thumb .placeholder {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-soft);
            font-size: 0.55rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .line-thumb .qty-pill {
            position: absolute;
            top: -6px; right: -6px;
            background: var(--text);
            color: white;
            font-size: 0.625rem;
            font-weight: 500;
            padding: 1px 6px;
            border-radius: 9999px;
            letter-spacing: 0.05em;
        }
        .line-name {
            flex: 1;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1rem;
            color: var(--text);
            line-height: 1.3;
        }
        .line-price {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1rem;
            color: var(--text);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: .625rem 0;
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .summary-row .label {
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .summary-row .num {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.0625rem;
            color: var(--text);
        }
        .summary-row.free .num { color: var(--primary); font-style: italic; }
        .summary-row.total {
            padding: 1.25rem 0;
            margin-top: .5rem;
            border-top: 1px solid var(--text);
            border-bottom: 1px solid var(--text);
        }
        .summary-row.total .label {
            font-size: 0.75rem;
            letter-spacing: 0.25em;
            color: var(--text);
            font-weight: 500;
        }
        .summary-row.total .num {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 2rem;
            color: var(--primary);
        }
        .pay-btn {
            width: 100%;
            margin-top: 1.5rem;
            background: var(--text);
            color: white;
            border: 0;
            padding: 1.25rem;
            font-size: 0.7rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .75rem;
            font-family: system-ui, sans-serif;
            transition: background-color .2s ease;
        }
        .pay-btn:hover { background: var(--primary); }
        .pay-btn .sep {
            opacity: .6;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            text-transform: none;
            letter-spacing: 0;
        }
        .secure-line {
            text-align: center;
            margin-top: 1.25rem;
            color: var(--text-soft);
            font-size: 0.625rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }

        @media (max-width: 880px) {
            .checkout-grid { grid-template-columns: 1fr; gap: 3rem; }
            .summary { position: static; }
        }
        @media (max-width: 560px) {
            .checkout-page { padding: 2.5rem 1.25rem 3rem; }
            .fields-row { grid-template-columns: 1fr; gap: 0; }
            .checkout-head { margin-bottom: 2rem; }
        }
    </style>

    @php
        $defaultAddress = $customer?->default_shipping_address ?? [];
    @endphp

    <div class="checkout-page">
        <div class="checkout-head">
            <p class="eyebrow">{{ __('site.checkout.summary') }}</p>
            <h1>{{ __('site.checkout.title') }}</h1>
            <a href="/cart" class="back">← {{ __('site.checkout.back_to_cart') }}</a>
        </div>

        @if (! $customer && $store->showsAccountUi())
            <div class="account-prompt">
                <span>
                    {{ __('site.checkout.have_account') }}
                    <a href="/account/login">{{ __('site.checkout.sign_in_link') }}</a>
                    {{ __('site.checkout.for_faster') }}
                </span>
                @if ($store->allow_registration)
                    <a href="/account/register">{{ __('site.checkout.create_account_link') }}</a>
                @endif
            </div>
        @endif

        <form method="post" action="/checkout">
            @csrf

            @if ($errors->any())
                <div class="errors">
                    <span class="errors-label">{{ __('site.common.error') ?? 'Error' }}</span>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="checkout-grid">
                <div>
                    <div class="section">
                        <h2 class="section-title"><span class="step">i.</span>{{ __('site.checkout.sec_contact') }}</h2>
                        @if ($customer)
                            <div class="signed-in">
                                {!! __('site.checkout.signed_in_as', ['email' => '<strong>' . e($customer->email) . '</strong>']) !!}
                            </div>
                        @endif
                        <div class="field">
                            <label class="field-label" for="customer_email">{{ __('site.checkout.email') }}</label>
                            <input class="field-input" type="email" name="customer_email" id="customer_email" value="{{ old('customer_email', $customer?->email) }}" placeholder="you@example.com" required>
                        </div>
                        <div class="field">
                            <label class="field-label" for="customer_name">{{ __('site.checkout.full_name') }}</label>
                            <input class="field-input" type="text" name="customer_name" id="customer_name" value="{{ old('customer_name', $customer?->name) }}" required>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title"><span class="step">ii.</span>{{ __('site.checkout.sec_shipping') }}</h2>
                        <div class="field">
                            <label class="field-label" for="address_line">{{ __('site.checkout.street') }}</label>
                            <input class="field-input" type="text" name="address_line" id="address_line" value="{{ old('address_line', $defaultAddress['line'] ?? '') }}" required>
                        </div>
                        <div class="fields-row">
                            <div class="field">
                                <label class="field-label" for="city">{{ __('site.checkout.city') }}</label>
                                <input class="field-input" type="text" name="city" id="city" value="{{ old('city', $defaultAddress['city'] ?? '') }}" required>
                            </div>
                            <div class="field">
                                <label class="field-label" for="postal_code">{{ __('site.checkout.postal') }}</label>
                                <input class="field-input" type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $defaultAddress['postal_code'] ?? '') }}" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="field-label" for="country">{{ __('site.checkout.country') }}</label>
                            <input class="field-input" type="text" name="country" id="country" maxlength="2" value="{{ old('country', $defaultAddress['country'] ?? 'US') }}" required>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title"><span class="step">iii.</span>{{ __('site.checkout.sec_payment') }}</h2>
                        <div class="stub-notice">
                            <span class="icon">i</span>
                            <div>
                                <strong>{{ __('site.checkout.stub_title') }}</strong>
                                {!! __('site.checkout.stub_body', ['action' => '<em>' . __('site.checkout.action_place_order') . '</em>']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $subtotal = $total_cents;
                    $shipping = $subtotal >= 5000 ? 0 : 500;
                    $grand = $subtotal + $shipping;
                @endphp
                <aside class="summary">
                    <h2>{{ __('site.checkout.summary') }}</h2>
                    <div class="line-items">
                        @foreach ($items as $row)
                            <div class="line">
                                <div class="line-thumb">
                                    @if ($row['product']->image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($row['product']->image_path) }}" alt="">
                                    @else
                                        <span class="placeholder">·</span>
                                    @endif
                                    <span class="qty-pill">{{ $row['quantity'] }}</span>
                                </div>
                                <div class="line-name">
                                    {{ $row['product']->name }}
                                    @if (! empty($row['variant']))
                                        <span class="line-variant">— {{ $row['variant']->label }}</span>
                                    @endif
                                </div>
                                <div class="line-price">@money($row['subtotal_cents'])</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="summary-row">
                        <span class="label">{{ __('site.cart.subtotal') }}</span>
                        <span class="num">@money($subtotal)</span>
                    </div>
                    <div class="summary-row {{ $shipping === 0 ? 'free' : '' }}">
                        <span class="label">{{ __('site.cart.shipping') }}</span>
                        <span class="num">@if($shipping === 0){{ __('site.common.free') }}@else @money($shipping) @endif</span>
                    </div>
                    <div class="summary-row total">
                        <span class="label">{{ __('site.cart.total') }}</span>
                        <span class="num">@money($grand)</span>
                    </div>

                    <button type="submit" class="pay-btn">
                        <span>{{ __('site.checkout.pay_now') }}</span>
                        <span class="sep">·</span>
                        <span>@money($grand)</span>
                    </button>
                    @if ($displayCurrency !== $baseCurrency)
                        <div class="secure-line">{{ __('site.checkout.charged_in', ['amount' => \App\Services\Money::format($grand, $baseCurrency)]) }}</div>
                    @endif
                    <div class="secure-line">{{ __('site.checkout.secure') }}</div>
                </aside>
            </div>
        </form>
    </div>
@endsection
