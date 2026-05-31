@php
    $title = __('site.account.settings_title');
    $addr = $customer->default_shipping_address ?? [];
@endphp
@extends('themes.default.layout')

@section('content')
    <style>
        .acct {
            max-width: 720px;
            margin: 0 auto;
            padding: 56px 1.75rem 96px;
        }
        .acct-head { margin-bottom: 36px; }
        .acct-head .back {
            font-size: 11px;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--muted);
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-bottom: 18px;
            transition: color .15s ease;
        }
        .acct-head .back:hover { color: var(--accent); }
        .acct-head h1 {
            font-family: var(--display);
            font-size: clamp(32px, 4.5vw, 46px);
            font-weight: 500;
            line-height: 1.02;
        }
        .acct-head p { color: var(--muted); font-size: 14px; margin-top: 8px; }

        .flash {
            border: 1px solid color-mix(in srgb, #16a34a 40%, var(--line));
            background: color-mix(in srgb, #16a34a 8%, var(--paper));
            color: #15803d;
            padding: 12px 16px;
            margin-bottom: 24px;
            font-size: 13px;
        }

        .panel {
            border: 1px solid var(--line);
            background: var(--paper);
            padding: 30px 32px;
            margin-bottom: 22px;
        }
        .panel > h2 {
            font-size: 11px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 700;
            margin-bottom: 22px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--line);
        }

        .errors {
            border: 1px solid #b91c1c;
            background: rgba(185, 28, 28, .04);
            color: #b91c1c;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .errors ul { margin: 0; padding-left: 18px; }

        .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .field { margin-bottom: 16px; }
        .field.full { grid-column: 1 / -1; }
        .field label {
            display: block;
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 600;
            margin-bottom: 8px;
        }
        .field label small { text-transform: none; letter-spacing: 0; font-weight: 400; }
        .field input, .field select {
            width: 100%;
            border: 1px solid var(--line);
            background: #fff;
            padding: 13px 14px;
            font-family: var(--body);
            font-size: 14px;
            color: var(--ink);
            transition: border-color .15s ease;
        }
        .field input:focus, .field select:focus { outline: none; border-color: var(--ink); }

        .check-row {
            display: flex;
            align-items: flex-start;
            gap: .625rem;
            font-size: 13px;
            color: var(--ink-soft, #4f4a40);
            line-height: 1.5;
            cursor: pointer;
            margin-top: 4px;
        }
        .check-row input { margin-top: .15rem; accent-color: var(--accent); flex-shrink: 0; }

        .save-btn {
            background: var(--ink);
            color: var(--paper);
            border: 1px solid var(--ink);
            padding: 14px 30px;
            margin-top: 8px;
            font-size: 11px;
            letter-spacing: .16em;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--body);
            transition: background-color .2s ease, border-color .2s ease;
        }
        .save-btn:hover { background: var(--accent); border-color: var(--accent); }

        @media (max-width: 540px) { .frow { grid-template-columns: 1fr; } }
    </style>

    <main>
        <div class="acct">
            <div class="acct-head rv">
                <a href="/account" class="back">← {{ __('site.account.back_to_account') }}</a>
                <h1>{{ __('site.account.settings_title') }}</h1>
                <p>{{ __('site.account.settings_lead') }}</p>
            </div>

            @if (session('account.flash'))
                <div class="flash rv">{{ session('account.flash') }}</div>
            @endif

            {{-- ===== Profile + address ===== --}}
            <form method="post" action="/account/settings" class="rv">
                @csrf
                <div class="panel">
                    <h2>{{ __('site.account.profile_section') }}</h2>

                    @if ($errors->any() && ! session('account.password_open'))
                        <div class="errors">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="frow">
                        <div class="field full">
                            <label for="name">{{ __('site.account.name') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $customer->name) }}" required>
                        </div>
                        <div class="field full">
                            <label for="email">{{ __('site.account.email') }}</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $customer->email) }}" required>
                        </div>
                        <div class="field">
                            <label for="phone">{{ __('site.account.phone') }} <small>({{ __('site.account.optional') }})</small></label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}">
                        </div>
                        <div class="field">
                            <label for="birthday">{{ __('site.account.birthday') }} <small>({{ __('site.account.optional') }})</small></label>
                            <input type="date" name="birthday" id="birthday" value="{{ old('birthday', optional($customer->birthday)->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <h2>{{ __('site.account.address_section') }}</h2>
                    <div class="frow">
                        <div class="field full">
                            <label for="address_line">{{ __('site.account.address_line') }} <small>({{ __('site.account.optional') }})</small></label>
                            <input type="text" name="address_line" id="address_line" value="{{ old('address_line', $addr['line'] ?? '') }}">
                        </div>
                        <div class="field">
                            <label for="city">{{ __('site.account.city') }}</label>
                            <input type="text" name="city" id="city" value="{{ old('city', $addr['city'] ?? '') }}">
                        </div>
                        <div class="field">
                            <label for="postal_code">{{ __('site.account.postal_code') }}</label>
                            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $addr['postal_code'] ?? '') }}">
                        </div>
                        <div class="field">
                            <label for="address_region">{{ __('site.account.address_region') }}</label>
                            <input type="text" name="address_region" id="address_region" value="{{ old('address_region', $addr['region'] ?? '') }}">
                        </div>
                        <div class="field">
                            <label for="country">{{ __('site.account.country') }}</label>
                            @php $selCountry = old('country', $addr['country'] ?? 'BG'); @endphp
                            <select name="country" id="country">
                                @foreach ($countries as $code => $name)
                                    <option value="{{ $code }}" @selected($selCountry === $code)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field full">
                            <label class="check-row">
                                <input type="checkbox" name="marketing_optin" value="1" @checked(old('marketing_optin', (bool) $customer->marketing_optin_at))>
                                <span>{{ __('site.account.marketing_optin') }}</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="save-btn">{{ __('site.account.save_profile') }}</button>
                </div>
            </form>

            {{-- ===== Password ===== --}}
            <form method="post" action="/account/password" class="rv">
                @csrf
                <div class="panel">
                    <h2>{{ __('site.account.password_section') }}</h2>

                    @if ($errors->password->any())
                        <div class="errors">
                            <ul>
                                @foreach ($errors->password->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="field">
                        <label for="current_password">{{ __('site.account.current_password') }}</label>
                        <input type="password" name="current_password" id="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="frow">
                        <div class="field">
                            <label for="password">{{ __('site.account.new_password') }}</label>
                            <input type="password" name="password" id="password" autocomplete="new-password" required>
                        </div>
                        <div class="field">
                            <label for="password_confirmation">{{ __('site.account.confirm_password') }}</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" required>
                        </div>
                    </div>
                    <button type="submit" class="save-btn">{{ __('site.account.change_password') }}</button>
                </div>
            </form>
        </div>
    </main>
@endsection
