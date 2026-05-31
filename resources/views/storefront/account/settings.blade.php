@php
    $title = __('site.account.settings_title');
    $addr = $customer->default_shipping_address ?? [];
@endphp
@extends("themes.{$theme}.layout")

@section('content')
    <style>
        .settings-page { max-width: 680px; margin: 0 auto; padding: 3rem 1.5rem; }
        .settings-head { margin-bottom: 1.5rem; }
        .settings-head a.back { color: var(--text-muted, #57534e); font-size: .8125rem; text-decoration: none; }
        .settings-head a.back:hover { color: var(--primary); }
        .settings-head h1 { margin: .5rem 0 .25rem; font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 800; letter-spacing: -0.02em; }
        .settings-head p { margin: 0; color: var(--text-muted, #57534e); font-size: .9375rem; }
        .flash { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; padding: .75rem 1rem; border-radius: .625rem; margin-bottom: 1.25rem; font-size: .875rem; }
        .errors { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: .75rem 1rem; border-radius: .625rem; margin-bottom: 1rem; font-size: .875rem; }
        .errors ul { margin: 0; padding-left: 1.25rem; }
        .card-block { background: var(--surface, white); border: 1px solid var(--border, #e7e5e4); border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.25rem; }
        .card-block h2 { margin: 0 0 1.25rem; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: var(--text-muted, #57534e); }
        .frow { display: grid; grid-template-columns: 1fr 1fr; gap: .875rem; }
        .field { margin-bottom: .875rem; }
        .field.full { grid-column: 1 / -1; }
        .field label { display: block; font-size: .8125rem; font-weight: 600; margin-bottom: .375rem; }
        .field input, .field select { width: 100%; padding: .75rem .875rem; border: 1px solid var(--border, #e7e5e4); border-radius: .625rem; background: var(--surface, white); font: inherit; font-size: .9375rem; }
        .field input:focus, .field select:focus { outline: none; border-color: var(--primary); }
        .check-row { display: flex; align-items: flex-start; gap: .5rem; font-size: .875rem; cursor: pointer; }
        .save-btn { background: var(--primary); color: white; border: 0; padding: .75rem 1.5rem; border-radius: .625rem; font-weight: 700; font-size: .9375rem; cursor: pointer; margin-top: .5rem; }
        .save-btn:hover { background: var(--primary-strong, var(--primary)); }
        @media (max-width: 540px) { .frow { grid-template-columns: 1fr; } }
    </style>

    <div class="settings-page">
        <div class="settings-head">
            <a href="/account" class="back">← {{ __('site.account.back_to_account') }}</a>
            <h1>{{ __('site.account.settings_title') }}</h1>
            <p>{{ __('site.account.settings_lead') }}</p>
        </div>

        @if (session('account.flash'))
            <div class="flash">{{ session('account.flash') }}</div>
        @endif

        <form method="post" action="/account/settings">
            @csrf
            <div class="card-block">
                <h2>{{ __('site.account.profile_section') }}</h2>
                @if ($errors->any() && ! session('account.password_open'))
                    <div class="errors"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif
                <div class="frow">
                    <div class="field full"><label for="name">{{ __('site.account.name') }}</label><input type="text" name="name" id="name" value="{{ old('name', $customer->name) }}" required></div>
                    <div class="field full"><label for="email">{{ __('site.account.email') }}</label><input type="email" name="email" id="email" value="{{ old('email', $customer->email) }}" required></div>
                    <div class="field"><label for="phone">{{ __('site.account.phone') }}</label><input type="tel" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}"></div>
                    <div class="field"><label for="birthday">{{ __('site.account.birthday') }}</label><input type="date" name="birthday" id="birthday" value="{{ old('birthday', optional($customer->birthday)->format('Y-m-d')) }}"></div>
                </div>
            </div>

            <div class="card-block">
                <h2>{{ __('site.account.address_section') }}</h2>
                <div class="frow">
                    <div class="field full"><label for="address_line">{{ __('site.account.address_line') }}</label><input type="text" name="address_line" id="address_line" value="{{ old('address_line', $addr['line'] ?? '') }}"></div>
                    <div class="field"><label for="city">{{ __('site.account.city') }}</label><input type="text" name="city" id="city" value="{{ old('city', $addr['city'] ?? '') }}"></div>
                    <div class="field"><label for="postal_code">{{ __('site.account.postal_code') }}</label><input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $addr['postal_code'] ?? '') }}"></div>
                    <div class="field"><label for="address_region">{{ __('site.account.address_region') }}</label><input type="text" name="address_region" id="address_region" value="{{ old('address_region', $addr['region'] ?? '') }}"></div>
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
                        <label class="check-row"><input type="checkbox" name="marketing_optin" value="1" @checked(old('marketing_optin', (bool) $customer->marketing_optin_at))> <span>{{ __('site.account.marketing_optin') }}</span></label>
                    </div>
                </div>
                <button type="submit" class="save-btn">{{ __('site.account.save_profile') }}</button>
            </div>
        </form>

        <form method="post" action="/account/password">
            @csrf
            <div class="card-block">
                <h2>{{ __('site.account.password_section') }}</h2>
                @if ($errors->password->any())
                    <div class="errors"><ul>@foreach ($errors->password->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif
                <div class="field"><label for="current_password">{{ __('site.account.current_password') }}</label><input type="password" name="current_password" id="current_password" required></div>
                <div class="frow">
                    <div class="field"><label for="password">{{ __('site.account.new_password') }}</label><input type="password" name="password" id="password" required></div>
                    <div class="field"><label for="password_confirmation">{{ __('site.account.confirm_password') }}</label><input type="password" name="password_confirmation" id="password_confirmation" required></div>
                </div>
                <button type="submit" class="save-btn">{{ __('site.account.change_password') }}</button>
            </div>
        </form>
    </div>
@endsection
