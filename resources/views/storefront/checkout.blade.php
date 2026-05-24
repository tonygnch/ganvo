@php
    $title = 'Checkout';
@endphp
@extends("themes.{$theme}.layout")

@section('content')
    <style>
        .checkout-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 4rem;
        }
        .checkout-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .checkout-head h1 {
            margin: 0;
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .checkout-head .back {
            color: var(--text-muted, #57534e);
            font-size: 0.9375rem;
            text-decoration: none;
        }
        .checkout-head .back:hover { color: var(--primary); }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
            align-items: start;
        }

        /* -------- Form -------- */
        .card-block {
            background: var(--surface, white);
            border: 1px solid var(--border, #e7e5e4);
            border-radius: 1rem;
            padding: 1.75rem;
            margin-bottom: 1.25rem;
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: .625rem;
            margin: 0 0 1.25rem;
            font-size: 1.0625rem;
            font-weight: 700;
        }
        .section-title .step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px; height: 26px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .field { margin-bottom: 1rem; }
        .field:last-child { margin-bottom: 0; }
        .fields-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        label.field-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text, #1c1917);
            margin-bottom: .375rem;
            letter-spacing: 0.01em;
        }
        input.field-input {
            width: 100%;
            padding: .75rem .875rem;
            border: 1px solid var(--border, #e7e5e4);
            border-radius: .625rem;
            background: var(--surface, white);
            color: var(--text, #1c1917);
            font: inherit;
            font-size: 0.9375rem;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        input.field-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 18%, transparent);
        }
        input.field-input::placeholder { color: var(--text-soft, #a8a29e); }

        .stub-notice {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            background: color-mix(in srgb, var(--primary) 8%, transparent);
            border: 1px solid color-mix(in srgb, var(--primary) 24%, transparent);
            color: var(--primary-strong, var(--primary));
            padding: 1rem 1.125rem;
            border-radius: .75rem;
            font-size: 0.875rem;
        }
        .stub-notice .icon {
            flex-shrink: 0;
            width: 22px; height: 22px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
        }
        .stub-notice strong { display: block; margin-bottom: .125rem; color: var(--primary-strong, var(--primary)); }

        .errors {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 1rem 1.125rem;
            border-radius: .75rem;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
        }
        .errors ul { margin: 0; padding-left: 1.25rem; }

        /* -------- Summary card -------- */
        .summary {
            background: var(--surface, white);
            border: 1px solid var(--border, #e7e5e4);
            border-radius: 1rem;
            padding: 1.5rem;
            position: sticky;
            top: 7rem;
        }
        .summary h2 { margin: 0 0 1rem; font-size: 1.125rem; font-weight: 700; }
        .line-items {
            display: flex;
            flex-direction: column;
            gap: .875rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--border, #e7e5e4);
        }
        .line {
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .line-thumb {
            width: 48px; height: 48px;
            border-radius: .5rem;
            background: var(--muted, #f5f5f4);
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-soft, #a8a29e);
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        .line-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .line-thumb .qty-pill {
            position: absolute;
            top: -6px; right: -6px;
            background: var(--text, #1c1917);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 9999px;
        }
        .line-name {
            flex: 1;
            font-size: 0.875rem;
            color: var(--text, #1c1917);
            font-weight: 500;
        }
        .line-price {
            font-size: 0.875rem;
            color: var(--text, #1c1917);
            font-weight: 600;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: .375rem 0;
            font-size: 0.9375rem;
            color: var(--text-muted, #57534e);
        }
        .summary-row .num { color: var(--text, #1c1917); font-weight: 500; }
        .summary-row.free .num { color: #16a34a; font-weight: 600; }
        .summary-row.total {
            padding-top: .875rem;
            margin-top: .5rem;
            border-top: 1px solid var(--border, #e7e5e4);
        }
        .summary-row.total .label { color: var(--text, #1c1917); font-weight: 700; font-size: 1rem; }
        .summary-row.total .num {
            color: var(--primary-strong, var(--primary));
            font-weight: 800;
            font-size: 1.375rem;
        }
        .pay-btn {
            width: 100%;
            margin-top: 1.25rem;
            background: var(--primary);
            color: white;
            border: 0;
            padding: 1.125rem;
            border-radius: .75rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            transition: background-color .2s ease, transform .12s ease, box-shadow .15s ease;
        }
        .pay-btn:hover {
            background: var(--primary-strong, var(--primary));
            transform: translateY(-1px);
            box-shadow: 0 14px 28px -6px color-mix(in srgb, var(--primary) 50%, transparent);
        }
        .secure-line {
            text-align: center;
            margin-top: .875rem;
            color: var(--text-soft, #a8a29e);
            font-size: 0.75rem;
        }

        @media (max-width: 880px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .summary { position: static; }
        }
        @media (max-width: 560px) {
            .fields-row { grid-template-columns: 1fr; }
        }
    </style>

    @php
        $defaultAddress = $customer?->default_shipping_address ?? [];
    @endphp

    <div class="checkout-page">
        <div class="checkout-head">
            <h1>{{ __('site.checkout.title') }}</h1>
            <a href="/cart" class="back">{{ __('site.checkout.back_to_cart') }}</a>
        </div>

        @if (! $customer && $store->showsAccountUi())
            <div style="background: var(--surface, white); border: 1px solid var(--border, #e7e5e4); border-radius: .75rem; padding: .875rem 1.125rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <span style="font-size: 0.9375rem; color: var(--text-muted, #57534e);">
                    {{ __('site.checkout.have_account') }} <a href="/account/login" style="color: var(--primary); font-weight: 600; text-decoration: none;">{{ __('site.checkout.sign_in_link') }}</a> {{ __('site.checkout.for_faster') }}
                </span>
                @if ($store->allow_registration)
                    <a href="/account/register" style="color: var(--primary); font-size: 0.875rem; font-weight: 600; text-decoration: none;">{{ __('site.checkout.create_account_link') }}</a>
                @endif
            </div>
        @endif

        <form method="post" action="/checkout">
            @csrf

            @if ($errors->any())
                <div class="errors">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="checkout-grid">
                <div>
                    <div class="card-block">
                        <h2 class="section-title"><span class="step">1</span> {{ __('site.checkout.sec_contact') }}</h2>
                        @if ($customer)
                            <div style="background: color-mix(in srgb, var(--primary) 8%, transparent); border: 1px solid color-mix(in srgb, var(--primary) 20%, transparent); border-radius: .5rem; padding: .625rem .875rem; margin-bottom: 1rem; font-size: 0.875rem; color: var(--text, #1c1917);">
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
                        <div class="field">
                            <label class="field-label" for="customer_phone">{{ __('site.checkout.phone') }} <small style="color:var(--text-soft,#a8a29e);font-weight:400">({{ __('site.common.optional') }})</small></label>
                            <input class="field-input" type="tel" name="customer_phone" id="customer_phone" value="{{ old('customer_phone', $customer?->phone) }}">
                        </div>
                    </div>

                    <div class="card-block">
                        <h2 class="section-title"><span class="step">2</span> {{ __('site.checkout.sec_shipping') }}</h2>
                        <div class="field">
                            <label class="field-label" for="address_line">{{ __('site.checkout.street') }}</label>
                            <input class="field-input" type="text" name="address_line" id="address_line" value="{{ old('address_line', $defaultAddress['line'] ?? '') }}" required>
                        </div>
                        <div class="field">
                            <label class="field-label" for="address_region">{{ __('site.checkout.region') }} <small style="color:var(--text-soft,#a8a29e);font-weight:400">({{ __('site.common.optional') }})</small></label>
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

                    <div class="card-block">
                        <h2 class="section-title"><span class="step">3</span> {{ __('site.checkout.sec_shipping_method') }}</h2>
                        @include('storefront.partials.shipping-methods')
                    </div>

                    <div class="card-block">
                        <h2 class="section-title"><span class="step">4</span> {{ __('site.checkout.sec_extras') }}</h2>
                        @include('storefront.partials.checkout-extras')
                    </div>

                    <div class="card-block">
                        <h2 class="section-title"><span class="step">5</span> {{ __('site.checkout.sec_payment') }}</h2>
                        <div class="stub-notice">
                            <span class="icon">!</span>
                            <div>
                                <strong>{{ __('site.checkout.stub_title') }}</strong>
                                {!! __('site.checkout.stub_body', ['action' => '<em>' . __('site.checkout.action_place_order') . '</em>']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $subtotal = $total_cents;
                    $shipping = $shipping_cents ?? ($subtotal >= 5000 ? 0 : 500);
                    $discountCents = $discount_cents ?? 0;
                    $grand = max(0, $subtotal + $shipping - $discountCents);
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
                                        IMG
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
                        {{ __('site.checkout.pay_now') }}
                        <span aria-hidden="true">·</span>
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
