{{-- Sankevi — account settings. Same card in the margin as the ledger; on the
     right, the customer's own sheets: details, the address the truck drives to,
     and the password. --}}
@php
    $title = __('site.account.settings_title');
    $addr = $customer->default_shipping_address ?? [];
    $firstName = trim(explode(' ', (string) $customer->name)[0] ?? '');
    $initial = strtoupper(mb_substr($firstName !== '' ? $firstName : $customer->email, 0, 1));
@endphp
@extends('themes.sankevi.layout')

@section('content')
    <style>
        .account { display: grid; grid-template-columns: 236px 1fr; gap: 56px; padding: 34px 0 70px; align-items: start; }

        .acct-side { position: sticky; top: calc(var(--header-height) + 26px); background: var(--surface); border: 1px solid var(--line); padding: 28px 24px 22px; }
        .acct-side .who { text-align: center; padding-bottom: 20px; margin-bottom: 14px; border-bottom: 1px solid var(--line2); }
        .acct-side .who .av { width: 56px; height: 56px; margin: 0 auto 14px; display: grid; place-items: center; background: var(--accent); color: var(--on-accent); font-family: var(--display); font-weight: 500; font-size: 25px; }
        .acct-side .who .hi { font-family: var(--display); font-weight: 500; font-size: 22px; line-height: 1.1; }
        .acct-side .who .em { margin-top: 6px; font-size: 11.5px; letter-spacing: .04em; color: var(--muted); word-break: break-word; }
        .acct-side a, .acct-side button.link { display: block; width: 100%; text-align: left; padding: 11px 12px; border: 1px solid transparent; background: none; color: var(--muted); font-family: var(--body); font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; cursor: pointer; transition: color .25s ease, background-color .25s ease; }
        .acct-side a:hover, .acct-side button.link:hover { background: var(--surface2); color: var(--txt); }
        .acct-side a.on { color: var(--accent); border-color: var(--line2); }
        .acct-side form { margin: 0; }

        .acct-main h2 { font-family: var(--display); font-weight: 500; font-size: clamp(28px, 3.4vw, 42px); line-height: 1.04; letter-spacing: -.015em; padding-bottom: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--line); }

        .flash { background: color-mix(in srgb, var(--accent) 12%, var(--surface)); border: 1px solid var(--accent); padding: 14px 17px; margin-bottom: 22px; font-size: 13px; letter-spacing: .04em; }
        .errors { border: 1px solid #b4614a; border-left-width: 3px; background: color-mix(in srgb, #b4614a 12%, var(--surface)); padding: 13px 17px; margin-bottom: 20px; font-size: 13px; }
        .errors ul { list-style: none; }
        .errors li { display: flex; gap: 9px; }
        .errors li::before { content: "—"; color: #d08a70; }

        .panel { background: var(--surface); border: 1px solid var(--line); padding: 30px 30px 32px; margin-bottom: 22px; }
        .panel h4 { font-size: 10.5px; font-weight: 500; letter-spacing: .22em; text-transform: uppercase; color: var(--faint); padding-bottom: 16px; margin-bottom: 24px; border-bottom: 1px solid var(--line2); }

        .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .frow:last-of-type { margin-bottom: 0; }
        .field { display: flex; flex-direction: column; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); margin-bottom: 9px; }
        .field label small { text-transform: none; letter-spacing: 0; color: var(--faint); }
        .field input, .field select { width: 100%; background: var(--bg); border: 1px solid var(--line2); padding: 13px 15px; font-family: var(--body); font-size: 14.5px; color: var(--txt); transition: border-color .25s ease; }
        .field input:focus, .field select:focus { outline: none; border-color: var(--accent); }

        .check-row { display: flex; align-items: flex-start; gap: .7rem; font-size: 13.5px; line-height: 1.55; color: var(--muted); cursor: pointer; }
        .check-row input { margin-top: .22rem; width: 16px; height: 16px; flex-shrink: 0; accent-color: var(--accent); }

        .panel .btn { margin-top: 24px; }

        @media (max-width: 1000px) {
            .account { grid-template-columns: 1fr; gap: 30px; }
            .acct-side { position: static; }
        }
        @media (max-width: 560px) {
            .frow { grid-template-columns: 1fr; }
            .panel { padding: 24px 20px 26px; }
        }
    </style>

    <main>
        <div class="wrap">
            <div class="page-head">
                <div class="crumb"><a href="/account">{{ __('site.account.back_to_account') }}</a></div>
                <h1>{{ __('site.account.settings_title') }}</h1>
                <p>{{ __('site.account.settings_lead') }}</p>
            </div>

            <div class="account">
                <aside class="acct-side cut reveal">
                    <div class="who">
                        <div class="av cut cut-sm">{{ $initial }}</div>
                        <div class="hi">{{ $firstName !== '' ? $firstName : $customer->name }}</div>
                        <div class="em">{{ $customer->email }}</div>
                    </div>
                    <a href="/account">{{ __('site.account.recent_orders') }}</a>
                    <a href="/account/settings" class="on">{{ __('site.account.settings') }}</a>
                    <form method="post" action="/account/logout">
                        @csrf
                        <button type="submit" class="link">{{ __('site.account.sign_out') }}</button>
                    </form>
                </aside>

                <div class="acct-main">
                    <h2>{{ __('site.account.settings_title') }}</h2>

                    @if (session('account.flash'))
                        <div class="flash reveal">{{ session('account.flash') }}</div>
                    @endif

                    <form method="post" action="/account/settings" class="reveal">
                        @csrf
                        <div class="panel cut">
                            <h4>{{ __('site.account.profile_section') }}</h4>
                            @if ($errors->any() && ! session('account.password_open'))
                                <div class="errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                            @endif
                            <div class="frow">
                                <div class="field full">
                                    <label for="name">{{ __('site.account.name') }}</label>
                                    <input type="text" name="name" id="name" value="{{ old('name', $customer->name) }}" required>
                                </div>
                            </div>
                            <div class="frow">
                                <div class="field full">
                                    <label for="email">{{ __('site.account.email') }}</label>
                                    <input type="email" name="email" id="email" value="{{ old('email', $customer->email) }}" required>
                                </div>
                            </div>
                            <div class="frow">
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

                        <div class="panel cut">
                            <h4>{{ __('site.account.address_section') }}</h4>
                            <div class="frow">
                                <div class="field full">
                                    <label for="address_line">{{ __('site.account.address_line') }} <small>({{ __('site.account.optional') }})</small></label>
                                    <input type="text" name="address_line" id="address_line" value="{{ old('address_line', $addr['line'] ?? '') }}">
                                </div>
                            </div>
                            <div class="frow">
                                <div class="field">
                                    <label for="city">{{ __('site.account.city') }}</label>
                                    <input type="text" name="city" id="city" value="{{ old('city', $addr['city'] ?? '') }}">
                                </div>
                                <div class="field">
                                    <label for="postal_code">{{ __('site.account.postal_code') }}</label>
                                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $addr['postal_code'] ?? '') }}">
                                </div>
                            </div>
                            <div class="frow">
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
                            </div>
                            <div class="frow">
                                <div class="field full">
                                    <label class="check-row">
                                        <input type="checkbox" name="marketing_optin" value="1" @checked(old('marketing_optin', (bool) $customer->marketing_optin_at))>
                                        <span>{{ __('site.account.marketing_optin') }}</span>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn">{{ __('site.account.save_profile') }}</button>
                        </div>
                    </form>

                    <form method="post" action="/account/password" class="reveal">
                        @csrf
                        <div class="panel cut">
                            <h4>{{ __('site.account.password_section') }}</h4>
                            @if ($errors->password->any())
                                <div class="errors"><ul>@foreach ($errors->password->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                            @endif
                            <div class="frow">
                                <div class="field full">
                                    <label for="current_password">{{ __('site.account.current_password') }}</label>
                                    <input type="password" name="current_password" id="current_password" autocomplete="current-password" required>
                                </div>
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
                            <button type="submit" class="btn">{{ __('site.account.change_password') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
@endsection
