@php
    $title = __('site.checkout.title');
    $subtotal = $total_cents;
    $shipping = $shipping_cents ?? ($subtotal >= 5000 ? 0 : 500);
    $discountCents = $discount_cents ?? 0;
    $grand = max(0, $subtotal + $shipping - $discountCents);
    $defaultAddress = $customer?->default_shipping_address ?? [];
@endphp
@extends('themes.gallery.layout')

@section('content')
    <style>
        .checkout-page { max-width: 1280px; margin: 0 auto; padding: 4rem 2.5rem 6rem; }
        .checkout-head { margin: 0 0 2.5rem; }
        .checkout-head .eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 .75rem;
        }
        .checkout-head h1 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 600;
            letter-spacing: -0.025em;
            margin: 0 0 .5rem;
        }
        .checkout-head .back {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .checkout-head .back:hover { color: var(--text); }

        .account-prompt {
            background: var(--muted);
            border-left: 3px solid var(--text);
            padding: .875rem 1.25rem;
            margin: 0 0 2rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .account-prompt a { font-weight: 600; color: var(--text); border-bottom: 1px solid var(--text); }

        .errors {
            border: 1px solid var(--hair);
            background: var(--muted);
            padding: .875rem 1rem;
            margin: 0 0 2rem;
            color: var(--text);
            font-size: 0.875rem;
        }
        .errors-label { font-weight: 700; }
        .errors ul { margin: .25rem 0 0; padding-left: 1.125rem; }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 3rem;
            align-items: start;
        }
        .section { margin: 0 0 2.5rem; }
        .section-title {
            display: flex;
            align-items: baseline;
            gap: .75rem;
            font-size: 0.6875rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            margin: 0 0 1.25rem;
        }
        .section-title .step {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            color: var(--text);
            font-size: 0.75rem;
        }

        .field { margin: 0 0 1rem; }
        .field-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text);
            margin: 0 0 .375rem;
        }
        .field-input {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--hair);
            padding: .75rem 1rem;
            font-size: 0.9375rem;
            color: var(--text);
            font-family: inherit;
            transition: border-color .2s ease;
        }
        .field-input:focus { outline: none; border-color: var(--text); }
        .fields-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }

        .signed-in {
            background: var(--muted);
            padding: .625rem .875rem;
            margin: 0 0 .75rem;
            font-size: 0.8125rem;
            color: var(--text);
        }
        .stub-notice {
            display: flex;
            gap: .875rem;
            padding: 1rem 1.25rem;
            background: var(--muted);
            border: 1px solid var(--hair);
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .stub-notice .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px; height: 20px;
            border-radius: 50%;
            background: var(--text);
            color: var(--bg);
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .stub-notice strong { display: block; color: var(--text); margin: 0 0 .25rem; }

        .summary {
            background: var(--surface);
            border: 1px solid var(--hair);
            padding: 1.75rem;
            position: sticky;
            top: 6rem;
        }
        .summary h2 {
            margin: 0 0 1.5rem;
            font-size: 0.6875rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
        }
        .line-items { margin: 0 0 1.5rem; }
        .line {
            display: grid;
            grid-template-columns: 56px 1fr auto;
            gap: .875rem;
            padding: .625rem 0;
            align-items: center;
            font-size: 0.875rem;
            border-bottom: 1px solid var(--hair);
        }
        .line:last-child { border-bottom: 0; }
        .line-thumb {
            width: 56px; height: 56px;
            background: var(--muted);
            overflow: hidden;
            position: relative;
        }
        .line-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .line-thumb .placeholder { display: flex; width: 100%; height: 100%; align-items: center; justify-content: center; color: var(--text-soft); }
        .line-thumb .qty-pill {
            position: absolute; top: -6px; right: -6px;
            background: var(--text); color: var(--bg);
            font-size: 0.625rem;
            font-weight: 700;
            min-width: 18px; height: 18px;
            line-height: 18px;
            border-radius: 999px;
            text-align: center;
            padding: 0 5px;
        }
        .line .name { font-size: 0.9375rem; color: var(--text); }
        .line .line-price {
            font-variant-numeric: tabular-nums;
            color: var(--text);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: .5rem 0;
            font-size: 0.9375rem;
        }
        .summary-row.free .num { color: var(--primary); font-weight: 600; }
        .summary-row .num { font-variant-numeric: tabular-nums; }
        .summary-row.total {
            margin-top: .75rem;
            padding-top: .875rem;
            border-top: 1px solid var(--hair);
            font-size: 1.0625rem;
            font-weight: 600;
        }
        .summary-row.total .num { font-size: 1.5rem; }

        .pay-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .75rem;
            width: 100%;
            background: var(--text);
            color: var(--bg);
            border: 0;
            padding: 1.125rem 1.5rem;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            cursor: pointer;
            transition: opacity .2s ease;
        }
        .pay-btn:hover { opacity: .85; }
        .pay-btn .sep { opacity: .6; }
        .secure-line { text-align: center; margin-top: 1rem; font-size: 0.75rem; color: var(--text-soft); }

        @media (max-width: 900px) { .checkout-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="checkout-page">
        <header class="checkout-head">
            <p class="eyebrow">{{ __('site.checkout.summary') }}</p>
            <h1>{{ __('site.checkout.title') }}</h1>
            <a href="/cart" class="back">← {{ __('site.checkout.back_to_cart') }}</a>
        </header>

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
                    <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="checkout-grid">
                <div>
                    <div class="section">
                        <h2 class="section-title"><span class="step">i.</span>{{ __('site.checkout.sec_contact') }}</h2>
                        @if ($customer)
                            <div class="signed-in">{!! __('site.checkout.signed_in_as', ['email' => '<strong>' . e($customer->email) . '</strong>']) !!}</div>
                        @endif
                        <div class="field">
                            <label class="field-label" for="customer_email">{{ __('site.checkout.email') }}</label>
                            <input class="field-input" type="email" name="customer_email" id="customer_email" value="{{ old('customer_email', $customer?->email) }}" required>
                        </div>
                        <div class="field">
                            <label class="field-label" for="customer_name">{{ __('site.checkout.full_name') }}</label>
                            <input class="field-input" type="text" name="customer_name" id="customer_name" value="{{ old('customer_name', $customer?->name) }}" required>
                        </div>
                        <div class="field">
                            <label class="field-label" for="customer_phone">{{ __('site.checkout.phone') }} <small>({{ __('site.common.optional') }})</small></label>
                            <input class="field-input" type="tel" name="customer_phone" id="customer_phone" value="{{ old('customer_phone', $customer?->phone) }}">
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title"><span class="step">ii.</span>{{ __('site.checkout.sec_shipping') }}</h2>
                        <div class="field">
                            <label class="field-label" for="address_line">{{ __('site.checkout.street') }}</label>
                            <input class="field-input" type="text" name="address_line" id="address_line" value="{{ old('address_line', $defaultAddress['line'] ?? '') }}" required>
                        </div>
                        <div class="field">
                            <label class="field-label" for="address_region">{{ __('site.checkout.region') }} <small>({{ __('site.common.optional') }})</small></label>
                            <input class="field-input" type="text" name="address_region" id="address_region" value="{{ old('address_region', $defaultAddress['region'] ?? '') }}">
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
                            <select class="field-input" name="country" id="country" required>
                                @php $selectedCountry = old('country', $defaultAddress['country'] ?? 'BG'); @endphp
                                @foreach ($countries as $code => $name)
                                    <option value="{{ $code }}" @selected($selectedCountry === $code)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title"><span class="step">iii.</span>{{ __('site.checkout.sec_shipping_method') }}</h2>
                        @include('storefront.partials.shipping-methods')
                    </div>

                    <div class="section">
                        <h2 class="section-title"><span class="step">iv.</span>{{ __('site.checkout.sec_extras') }}</h2>
                        @include('storefront.partials.checkout-extras')
                    </div>

                    <div class="section">
                        <h2 class="section-title"><span class="step">v.</span>{{ __('site.checkout.sec_payment') }}</h2>
                        <div class="stub-notice">
                            <span class="icon">i</span>
                            <div>
                                <strong>{{ __('site.checkout.stub_title') }}</strong>
                                {!! __('site.checkout.stub_body', ['action' => '<em>' . __('site.checkout.action_place_order') . '</em>']) !!}
                            </div>
                        </div>
                    </div>
                </div>

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
                                <div class="name">
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
                        <span>{{ __('site.cart.subtotal') }}</span>
                        <span class="num">@money($subtotal)</span>
                    </div>
                    <div class="summary-row {{ $shipping === 0 ? 'free' : '' }}" data-sm-shipping-row>
                        <span>{{ __('site.cart.shipping') }}</span>
                        <span class="num" data-sm-shipping>@if($shipping === 0){{ __('site.common.free') }}@else @money($shipping) @endif</span>
                    </div>
                    @if (! empty($discount) && $discountCents > 0)
                        <div class="summary-row discount">
                            <span>{{ $discount->name }}</span>
                            <span class="num">−@money($discountCents)</span>
                        </div>
                    @endif
                    <div class="summary-row total">
                        <span class="label">{{ __('site.cart.total') }}</span>
                        <span class="num" data-sm-grand>@money($grand)</span>
                    </div>
                    <button type="submit" class="pay-btn">
                        <span>{{ __('site.checkout.pay_now') }}</span>
                        <span class="sep">·</span>
                        <span data-sm-grand>@money($grand)</span>
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
