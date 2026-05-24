@php
    $title = __('site.checkout.title');
    $subtotal = $total_cents;
    $shipping = $shipping_cents ?? ($subtotal >= 5000 ? 0 : 500);
    $discountCents = $discount_cents ?? 0;
    $grand = max(0, $subtotal + $shipping - $discountCents);
    $defaultAddress = $customer?->default_shipping_address ?? [];
@endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .checkout-page { max-width: 1280px; margin: 0 auto; padding: 3rem 1.5rem 5rem; }
        .checkout-head { margin: 0 0 2rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--hair); }
        .checkout-head h1 { margin: 0 0 .25rem; font-size: 1.625rem; font-weight: 700; letter-spacing: -0.02em; }
        .checkout-head .back { font-size: 0.8125rem; color: var(--text-muted); }
        .checkout-head .back:hover { color: var(--primary); }

        .account-prompt {
            background: var(--primary-soft);
            border-radius: 10px;
            padding: .875rem 1.25rem;
            margin: 0 0 2rem;
            font-size: 0.875rem;
            color: var(--primary-strong);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .account-prompt a { color: var(--primary-strong); font-weight: 700; text-decoration: underline; }

        .errors {
            background: var(--surface-2);
            border: 1px solid var(--hair);
            border-radius: 8px;
            padding: .75rem 1rem;
            margin: 0 0 1.5rem;
            font-size: 0.875rem;
        }
        .errors-label { font-weight: 700; }
        .errors ul { margin: .25rem 0 0; padding-left: 1.125rem; }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            align-items: start;
        }
        .section {
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 0 0 1.25rem;
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: .625rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text);
            margin: 0 0 1rem;
        }
        .section-title .step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px; height: 24px;
            background: var(--primary-soft);
            color: var(--primary-strong);
            border-radius: 6px;
            font-family: var(--mono);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .field { margin: 0 0 .875rem; }
        .field-label {
            display: block;
            font-family: var(--mono);
            font-size: 0.6875rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-soft);
            margin: 0 0 .375rem;
        }
        .field-input {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--hair);
            border-radius: 8px;
            padding: .75rem .875rem;
            font-size: 0.9375rem;
            color: var(--text);
            font-family: inherit;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .field-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
        .fields-row { display: grid; grid-template-columns: 1fr 1fr; gap: .625rem; }

        .signed-in {
            background: var(--surface-2);
            border-radius: 8px;
            padding: .625rem .875rem;
            margin: 0 0 .875rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .stub-notice {
            display: flex;
            gap: .875rem;
            padding: 1rem 1.25rem;
            background: var(--surface-2);
            border-radius: 8px;
            font-size: 0.875rem;
            color: var(--text-muted);
            align-items: flex-start;
        }
        .stub-notice .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px; height: 22px;
            background: var(--text);
            color: var(--bg);
            border-radius: 50%;
            font-family: var(--mono);
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .stub-notice strong { display: block; color: var(--text); margin: 0 0 .25rem; }

        .summary {
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 12px;
            padding: 1.5rem;
            position: sticky;
            top: 6rem;
        }
        .summary h2 {
            margin: 0 0 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-soft);
        }
        .line-items { margin: 0 0 1rem; border-top: 1px solid var(--hair); }
        .line {
            display: grid;
            grid-template-columns: 48px 1fr auto;
            gap: .75rem;
            align-items: center;
            padding: .625rem 0;
            font-size: 0.875rem;
            border-bottom: 1px solid var(--hair);
        }
        .line-thumb {
            width: 48px; height: 48px;
            background: var(--surface-2);
            border-radius: 6px;
            overflow: hidden;
            position: relative;
        }
        .line-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .line-thumb .placeholder { display: flex; width: 100%; height: 100%; align-items: center; justify-content: center; color: var(--text-soft); font-size: .75rem; }
        .line-thumb .qty-pill {
            position: absolute; top: -4px; right: -4px;
            background: var(--text); color: var(--bg);
            font-family: var(--mono);
            font-size: 0.625rem;
            font-weight: 700;
            min-width: 18px; height: 18px;
            line-height: 18px;
            border-radius: 999px;
            text-align: center;
            padding: 0 5px;
        }
        .line .name { font-weight: 500; color: var(--text); }
        .line .line-price { font-family: var(--mono); font-variant-numeric: tabular-nums; color: var(--text); }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: .5rem 0;
            font-size: 0.9375rem;
        }
        .summary-row .num { font-family: var(--mono); font-variant-numeric: tabular-nums; }
        .summary-row.free .num { color: var(--primary); font-weight: 700; }
        .summary-row.total {
            margin-top: .75rem;
            padding-top: .875rem;
            border-top: 1px dashed var(--hair);
            font-size: 1.0625rem;
            font-weight: 700;
        }
        .summary-row.total .num { font-size: 1.5rem; }

        .pay-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .625rem;
            width: 100%;
            background: var(--text);
            color: var(--bg);
            border: 0;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-top: 1rem;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: background-color .15s ease;
        }
        .pay-btn:hover { background: var(--primary); }
        .pay-btn .sep { opacity: .6; }
        .secure-line { text-align: center; margin-top: .75rem; font-family: var(--mono); font-size: 0.6875rem; letter-spacing: 0.06em; color: var(--text-soft); }

        @media (max-width: 880px) { .checkout-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="checkout-page">
        <div class="checkout-head">
            <h1>{{ __('site.checkout.title') }}</h1>
            <a href="/cart" class="back">← {{ __('site.checkout.back_to_cart') }}</a>
        </div>

        @if (! $customer && $store->showsAccountUi())
            <div class="account-prompt">
                <span>{{ __('site.checkout.have_account') }} <a href="/account/login">{{ __('site.checkout.sign_in_link') }}</a> {{ __('site.checkout.for_faster') }}</span>
                @if ($store->allow_registration)<a href="/account/register">{{ __('site.checkout.create_account_link') }}</a>@endif
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
                    <section class="section">
                        <h2 class="section-title"><span class="step">1</span>{{ __('site.checkout.sec_contact') }}</h2>
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
                    </section>

                    <section class="section">
                        <h2 class="section-title"><span class="step">2</span>{{ __('site.checkout.sec_shipping') }}</h2>
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
                    </section>

                    <section class="section">
                        <h2 class="section-title"><span class="step">3</span>{{ __('site.checkout.sec_payment') }}</h2>
                        <div class="stub-notice">
                            <span class="icon">i</span>
                            <div>
                                <strong>{{ __('site.checkout.stub_title') }}</strong>
                                {!! __('site.checkout.stub_body', ['action' => '<em>' . __('site.checkout.action_place_order') . '</em>']) !!}
                            </div>
                        </div>
                    </section>
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
                    <div class="summary-row {{ $shipping === 0 ? 'free' : '' }}">
                        <span>{{ __('site.cart.shipping') }}</span>
                        <span class="num">@if($shipping === 0){{ __('site.common.free') }}@else @money($shipping) @endif</span>
                    </div>
                    @if (! empty($discount) && $discountCents > 0)
                        <div class="summary-row discount">
                            <span>{{ $discount->name }}</span>
                            <span class="num">−@money($discountCents)</span>
                        </div>
                    @endif
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
