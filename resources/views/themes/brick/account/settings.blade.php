@php
    $title = __('site.account.settings_title');
    $addr = $customer->default_shipping_address ?? [];
@endphp
@extends('themes.brick.layout')

@section('content')
    <style>
        .acct { max-width: 720px; margin: 0 auto; padding: 36px 1.5rem 80px; }
        .acct-head { margin-bottom: 30px; }
        .acct-head .back { font-family: var(--display); font-size: 11px; font-weight: 700; text-transform: uppercase; border: 2.5px solid var(--ink); padding: 5px 10px; display: inline-block; margin-bottom: 16px; }
        .acct-head .back:hover { background: var(--accent); }
        .acct-head .back:active { transform: translate(2px, 2px); box-shadow: var(--pop-sm); }
        .acct-head h1 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(30px, 4.5vw, 48px); line-height: .92; letter-spacing: -.02em; }
        .acct-head p { color: var(--muted); font-size: 14px; margin-top: 8px; font-weight: 600; }

        .flash { border: 2.5px solid var(--ink); background: #b6f5b6; padding: 12px 16px; margin-bottom: 24px; font-family: var(--display); font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .panel { border: 2.5px solid var(--ink); box-shadow: var(--pop); background: var(--paper); padding: 28px 30px; margin-bottom: 22px; }
        .panel > h2 { font-family: var(--display); font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 2.5px solid var(--ink); }
        .errors { border: 2.5px solid #b91c1c; background: #fff; color: var(--ink); padding: 12px 16px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }
        .errors ul { margin: 0; padding-left: 18px; }

        .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .field { margin-bottom: 16px; }
        .field.full { grid-column: 1 / -1; }
        .field label { display: block; font-family: var(--display); font-size: 11px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
        .field label small { text-transform: none; letter-spacing: 0; font-weight: 400; }
        .field input, .field select { width: 100%; border: 2.5px solid var(--ink); background: #fff; padding: 12px 13px; font-family: var(--body); font-size: 14px; color: var(--ink); }
        .field input:focus, .field select:focus { outline: none; box-shadow: var(--pop-sm); }
        .check-row { display: flex; align-items: flex-start; gap: .625rem; font-size: 13px; font-weight: 600; line-height: 1.5; cursor: pointer; margin-top: 4px; }
        .check-row input { margin-top: .15rem; width: 18px; height: 18px; accent-color: var(--ink); flex-shrink: 0; }
        .save-btn { font-family: var(--display); font-size: 11px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; background: var(--accent); color: var(--ink); border: 2.5px solid var(--ink); box-shadow: var(--pop-sm); padding: 13px 26px; margin-top: 8px; cursor: pointer; }
        .save-btn:hover { background: var(--ink); color: var(--accent); }
        .save-btn:active { transform: translate(5px, 5px); box-shadow: 0 0 0 var(--shadow); }
        @media (prefers-reduced-motion: reduce) { .save-btn:active, .acct-head .back:active { transform: none; box-shadow: var(--pop-sm); } }

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

            <form method="post" action="/account/settings" class="rv">
                @csrf
                <div class="panel">
                    <h2>{{ __('site.account.profile_section') }}</h2>
                    @if ($errors->any() && ! session('account.password_open'))
                        <div class="errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
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

            <form method="post" action="/account/password" class="rv">
                @csrf
                <div class="panel">
                    <h2>{{ __('site.account.password_section') }}</h2>
                    @if ($errors->password->any())
                        <div class="errors"><ul>@foreach ($errors->password->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
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
