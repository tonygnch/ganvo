@php
    $title = __('site.account.settings_title');
    $addr = $customer->default_shipping_address ?? [];
    $firstName = trim(explode(' ', (string) $customer->name)[0] ?? '');
    $initial = strtoupper(mb_substr($firstName !== '' ? $firstName : $customer->email, 0, 1));
@endphp
@extends('themes.ember.layout')

@section('content')
    <style>
        .acct-wrap { padding: 6px 0 30px; }
        .account { display: grid; grid-template-columns: 240px 1fr; gap: 44px; padding: 20px 0 70px; align-items: start; }
        .acct-side { background: var(--card); border: 1.5px solid var(--ink); border-radius: 4px; padding: 24px; position: sticky; top: 96px; }
        .acct-side .who { text-align: center; padding-bottom: 18px; border-bottom: 1px solid var(--rule); margin-bottom: 12px; }
        .acct-side .who .av { width: 58px; height: 58px; border-radius: 50%; background: radial-gradient(circle at 35% 30%, #7a4327, #311c11); margin: 0 auto 12px; display: grid; place-items: center; font-family: var(--display); font-weight: 700; font-size: 22px; color: var(--soft); }
        .acct-side .who .hi { font-family: var(--display); font-weight: 600; font-size: 19px; }
        .acct-side .who .em { font-family: var(--mono); font-size: 11px; color: var(--muted); word-break: break-word; }
        .acct-side a, .acct-side button.link { display: block; width: 100%; text-align: left; padding: 12px 14px; border: none; background: none; border-radius: 2px; font-family: var(--mono); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; cursor: pointer; color: var(--ink); transition: .2s; }
        .acct-side a:hover, .acct-side button.link:hover { background: var(--bg); }
        .acct-side a.on { background: var(--ink); color: var(--bg); }

        .acct-main h2 { font-family: var(--display); font-weight: 700; font-size: clamp(26px, 3.2vw, 40px); margin-bottom: 20px; }
        .acct-main h2 em { font-style: italic; color: var(--accent); }

        .flash { background: color-mix(in srgb, var(--accent) 12%, var(--card)); border: 1.5px solid var(--accent); color: var(--ink); border-radius: 2px; padding: 13px 16px; margin-bottom: 20px; font-size: 13px; }
        .flash::before { content: "✦ "; color: var(--accent); }

        .panel { background: var(--card); border: 1.5px solid var(--ink); border-radius: 4px; padding: 24px; margin-bottom: 18px; }
        .panel h4 { font-family: var(--mono); font-size: 12px; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); margin-bottom: 18px; }
        .panel h4::before { content: "// "; color: var(--accent); }

        .errors { border: 1.5px solid #b91c1c; background: color-mix(in srgb, #b91c1c 7%, var(--card)); color: var(--ink); border-radius: 2px; padding: 12px 16px; margin-bottom: 18px; font-size: 13px; }
        .errors ul { margin: 0; padding-left: 18px; }
        .errors li { margin: 2px 0; }
        .errors li::marker { content: "✦ "; color: var(--accent); }

        .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
        .frow:last-child { margin-bottom: 0; }
        .field { display: flex; flex-direction: column; margin-bottom: 0; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-family: var(--mono); font-size: 11px; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
        .field label small { text-transform: none; letter-spacing: 0; font-size: 11px; }
        .field input, .field select { border: 1.5px solid var(--line); border-radius: 2px; background: var(--card); padding: 13px 15px; font-family: inherit; font-size: 14px; color: var(--ink); width: 100%; }
        .field input:focus, .field select:focus { outline: none; border-color: var(--ink); }

        .check-row { display: flex; align-items: flex-start; gap: .625rem; font-size: 13px; line-height: 1.5; cursor: pointer; }
        .check-row input { margin-top: .2rem; width: 17px; height: 17px; accent-color: var(--accent); flex-shrink: 0; }

        .panel .btn { margin-top: 18px; }

        @media (max-width: 1000px) {
            .account { grid-template-columns: 1fr; }
            .acct-side { position: static; }
        }
        @media (max-width: 540px) { .frow { grid-template-columns: 1fr; } }
    </style>

    <main>
        <div class="wrap acct-wrap">
            <div class="page-head" style="padding-bottom: 14px;">
                <div class="crumb"><a href="/account">{{ __('site.account.back_to_account') }}</a></div>
                <h1>{{ __('site.account.settings_title') }}</h1>
                <p>{{ __('site.account.settings_lead') }}</p>
            </div>

            <div class="account">
                <aside class="acct-side reveal">
                    <div class="who">
                        <div class="av">{{ $initial }}</div>
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
                        <div class="panel">
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

                        <div class="panel">
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
                        <div class="panel">
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
