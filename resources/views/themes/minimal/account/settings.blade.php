@php $title = __('site.account.settings_title'); $addr = $customer->default_shipping_address ?? []; @endphp
@extends('themes.minimal.layout')

@section('content')
    <style>
        .acct { max-width: 720px; margin: 0 auto; padding: 50px 40px 90px; }
        .acct-head { margin-bottom: 30px; }
        .acct-head .back { font-size: 13px; color: var(--muted); } .acct-head .back:hover { color: var(--accent); }
        .acct-head h1 { font-family: var(--display); font-size: clamp(30px,4vw,46px); margin-top: 12px; }
        .acct-head p { color: var(--muted); font-size: 14px; margin-top: 8px; }
        .flash { background: #dcefe0; color: #2f7d4f; border-radius: 14px; padding: 12px 16px; margin-bottom: 22px; font-size: 13px; }
        .panel { background: var(--card); border-radius: 22px; padding: 28px; margin-bottom: 18px; }
        .panel > h2 { font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--line); }
        .errors { border: 1.5px solid #d98b7a; background: #fbe9e2; color: #9a4a37; padding: 12px 16px; border-radius: 14px; margin-bottom: 18px; font-size: 13px; } .errors ul { padding-left: 18px; }
        .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; } .field { margin-bottom: 14px; } .field.full { grid-column: 1/-1; }
        .field label { display: block; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
        .field input, .field select { width: 100%; border: 1.5px solid var(--line); border-radius: 14px; background: var(--bg); padding: 13px 15px; font-family: inherit; font-size: 14px; color: var(--ink); }
        .field input:focus, .field select:focus { outline: none; border-color: var(--accent); }
        .check-row { display: flex; align-items: flex-start; gap: 9px; font-size: 13px; color: #7a5e54; cursor: pointer; } .check-row input { accent-color: var(--accent); margin-top: 3px; }
        .save-btn { background: var(--accent); color: #fff; border: 0; border-radius: 99px; padding: 13px 28px; font-weight: 600; font-size: 13px; cursor: pointer; margin-top: 6px; }
        @media (max-width: 540px) { .frow { grid-template-columns: 1fr; } }
    </style>

    <main>
        <div class="acct">
            <div class="acct-head rv">
                <a href="/account" class="back">← {{ __('site.account.back_to_account') }}</a>
                <h1>{{ __('site.account.settings_title') }}</h1>
                <p>{{ __('site.account.settings_lead') }}</p>
            </div>
            @if (session('account.flash'))<div class="flash rv">{{ session('account.flash') }}</div>@endif
            <form method="post" action="/account/settings" class="rv">
                @csrf
                <div class="panel">
                    <h2>{{ __('site.account.profile_section') }}</h2>
                    @if ($errors->any() && ! session('account.password_open'))<div class="errors"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                    <div class="frow">
                        <div class="field full"><label>{{ __('site.account.name') }}</label><input type="text" name="name" value="{{ old('name', $customer->name) }}" required></div>
                        <div class="field full"><label>{{ __('site.account.email') }}</label><input type="email" name="email" value="{{ old('email', $customer->email) }}" required></div>
                        <div class="field"><label>{{ __('site.account.phone') }}</label><input type="tel" name="phone" value="{{ old('phone', $customer->phone) }}"></div>
                        <div class="field"><label>{{ __('site.account.birthday') }}</label><input type="date" name="birthday" value="{{ old('birthday', optional($customer->birthday)->format('Y-m-d')) }}"></div>
                    </div>
                </div>
                <div class="panel">
                    <h2>{{ __('site.account.address_section') }}</h2>
                    <div class="frow">
                        <div class="field full"><label>{{ __('site.account.address_line') }}</label><input type="text" name="address_line" value="{{ old('address_line', $addr['line'] ?? '') }}"></div>
                        <div class="field"><label>{{ __('site.account.city') }}</label><input type="text" name="city" value="{{ old('city', $addr['city'] ?? '') }}"></div>
                        <div class="field"><label>{{ __('site.account.postal_code') }}</label><input type="text" name="postal_code" value="{{ old('postal_code', $addr['postal_code'] ?? '') }}"></div>
                        <div class="field"><label>{{ __('site.account.address_region') }}</label><input type="text" name="address_region" value="{{ old('address_region', $addr['region'] ?? '') }}"></div>
                        <div class="field"><label>{{ __('site.account.country') }}</label>@php $sc = old('country', $addr['country'] ?? 'BG'); @endphp<select name="country">@foreach ($countries as $code => $name)<option value="{{ $code }}" @selected($sc === $code)>{{ $name }}</option>@endforeach</select></div>
                        <div class="field full"><label class="check-row"><input type="checkbox" name="marketing_optin" value="1" @checked(old('marketing_optin', (bool) $customer->marketing_optin_at))> <span>{{ __('site.account.marketing_optin') }}</span></label></div>
                    </div>
                    <button type="submit" class="save-btn">{{ __('site.account.save_profile') }}</button>
                </div>
            </form>
            <form method="post" action="/account/password" class="rv">
                @csrf
                <div class="panel">
                    <h2>{{ __('site.account.password_section') }}</h2>
                    @if ($errors->password->any())<div class="errors"><ul>@foreach ($errors->password->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                    <div class="field"><label>{{ __('site.account.current_password') }}</label><input type="password" name="current_password" required></div>
                    <div class="frow">
                        <div class="field"><label>{{ __('site.account.new_password') }}</label><input type="password" name="password" required></div>
                        <div class="field"><label>{{ __('site.account.confirm_password') }}</label><input type="password" name="password_confirmation" required></div>
                    </div>
                    <button type="submit" class="save-btn">{{ __('site.account.change_password') }}</button>
                </div>
            </form>
        </div>
    </main>
@endsection
