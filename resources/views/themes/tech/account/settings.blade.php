@php $title = __('site.account.settings_title'); $addr = $customer->default_shipping_address ?? []; @endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .acct { max-width: 720px; margin: 0 auto; padding: 50px 36px 90px; }
        .acct-head { margin-bottom: 32px; }
        .acct-head .back { font-family: var(--mono); font-size: 12px; color: var(--muted); } .acct-head .back:hover { color: var(--accent); }
        .acct-head h1 { font-family: var(--archivo); font-weight: 800; font-size: clamp(28px,4vw,42px); letter-spacing: -.02em; margin-top: 14px; }
        .acct-head p { color: var(--muted); font-size: 13px; margin-top: 8px; font-family: var(--mono); }
        .flash { border: 1px solid color-mix(in srgb,#16a34a 40%,var(--line)); background: color-mix(in srgb,#16a34a 10%,var(--surface)); color: #6ee7a0; padding: 12px 16px; border-radius: 8px; margin-bottom: 22px; font-family: var(--mono); font-size: 13px; }
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 28px; margin-bottom: 18px; }
        .panel > h2 { font-family: var(--mono); font-size: 11px; text-transform: uppercase; color: var(--faint); margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--line); }
        .errors { border: 1px solid #ff5c5c; background: rgba(255,92,92,.06); color: #ff8a8a; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 13px; } .errors ul { padding-left: 18px; }
        .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; } .field { margin-bottom: 14px; } .field.full { grid-column: 1/-1; }
        .field label { display: block; font-family: var(--mono); font-size: 11px; color: var(--muted); margin-bottom: 7px; }
        .field input, .field select { width: 100%; background: var(--bg); border: 1px solid var(--line); border-radius: 8px; padding: 13px 14px; color: var(--txt); font-family: inherit; font-size: 14px; }
        .field input:focus, .field select:focus { outline: none; border-color: var(--accent); }
        .check-row { display: flex; align-items: flex-start; gap: 9px; font-size: 13px; color: var(--muted); cursor: pointer; }
        .check-row input { accent-color: var(--accent); margin-top: 3px; }
        .save-btn { background: var(--accent); color: #0a0b0e; border: 0; border-radius: 6px; padding: 13px 26px; font-weight: 700; font-size: 13px; cursor: pointer; margin-top: 6px; }
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
                    <h2>// {{ __('site.account.profile_section') }}</h2>
                    @if ($errors->any() && ! session('account.password_open'))<div class="errors"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                    <div class="frow">
                        <div class="field full"><label>{{ __('site.account.name') }}</label><input type="text" name="name" value="{{ old('name', $customer->name) }}" required></div>
                        <div class="field full"><label>{{ __('site.account.email') }}</label><input type="email" name="email" value="{{ old('email', $customer->email) }}" required></div>
                        <div class="field"><label>{{ __('site.account.phone') }}</label><input type="tel" name="phone" value="{{ old('phone', $customer->phone) }}"></div>
                        <div class="field"><label>{{ __('site.account.birthday') }}</label><input type="date" name="birthday" value="{{ old('birthday', optional($customer->birthday)->format('Y-m-d')) }}"></div>
                    </div>
                </div>
                <div class="panel">
                    <h2>// {{ __('site.account.address_section') }}</h2>
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
                    <h2>// {{ __('site.account.password_section') }}</h2>
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
