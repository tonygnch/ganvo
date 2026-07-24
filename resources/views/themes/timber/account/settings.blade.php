{{-- Timber — the account spec sheet. Same counter card as the ledger, with
     the customer's own cutting list on the right: details, delivery address
     for the yard truck, and the password panel. --}}
@php
    $title = __('site.account.settings_title');
    $addr = $customer->default_shipping_address ?? [];
    $firstName = trim(explode(' ', (string) $customer->name)[0] ?? '');
    $initial = strtoupper(mb_substr($firstName !== '' ? $firstName : $customer->email, 0, 1));
@endphp
@extends('themes.timber.layout')

@section('content')
    <style>
        .acct-wrap { padding: 6px 0 30px; }
        .account { display: grid; grid-template-columns: 250px 1fr; gap: 50px; padding: 20px 0 70px; align-items: start; }

        /* ===== COUNTER CARD — identical board to the ledger page. */
        .acct-side { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 24px 22px; position: sticky; top: calc(var(--header-height) + 24px); box-shadow: 0 2px 0 0 var(--line); }
        .acct-side .rule-ticks { margin: -6px -6px 18px; }
        .acct-side.no-rule .rule-ticks { display: none; }
        .acct-side .who { text-align: center; padding-bottom: 18px; border-bottom: 2px solid var(--txt); margin-bottom: 12px; }
        .acct-side .who .av { width: 58px; height: 58px; border-radius: 6px; margin: 0 auto 12px; display: grid; place-items: center; font-family: var(--display); font-weight: 700; font-size: 26px; color: var(--on-accent); background: linear-gradient(94deg, color-mix(in srgb, var(--accent) 70%, #b09a72), var(--accent)); border: 1px solid var(--accent-deep); box-shadow: 0 2px 0 0 var(--accent-deep); }
        .acct-side .who .hi { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .02em; font-size: 21px; line-height: 1.1; }
        .acct-side .who .em { font-family: var(--mono); font-size: 11px; letter-spacing: .02em; color: var(--muted); word-break: break-word; margin-top: 5px; }
        .acct-side a, .acct-side button.link { display: block; width: 100%; text-align: left; padding: 11px 13px; border: 1px solid transparent; border-radius: 6px; font-family: var(--mono); font-size: 11.5px; letter-spacing: .06em; text-transform: uppercase; cursor: pointer; color: var(--muted); background: none; transition: background-color .2s ease, color .2s ease, border-color .2s ease; }
        .acct-side a:hover, .acct-side button.link:hover { background: var(--surface2); color: var(--txt); }
        .acct-side a.on { background: var(--accent); border-color: var(--accent-deep); color: var(--on-accent); }
        .acct-side form { margin: 0; }

        .acct-main h2 { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .01em; font-size: clamp(28px, 3.4vw, 42px); line-height: 1; border-bottom: 2px solid var(--txt); padding-bottom: 14px; margin-bottom: 22px; }
        .acct-main h2 em { font-style: normal; color: var(--accent-deep); }

        /* ===== NOTICES — the chalked note on the counter. */
        .flash { background: color-mix(in srgb, var(--accent) 12%, var(--surface)); border: 1px solid var(--accent-deep); color: var(--txt); border-radius: 8px; padding: 13px 16px; margin-bottom: 20px; font-family: var(--mono); font-size: 12px; letter-spacing: .02em; box-shadow: 0 2px 0 0 var(--line); }
        .flash::before { content: "▮ "; color: var(--accent); }

        .errors { border: 1px solid #b91c1c; background: color-mix(in srgb, #b91c1c 6%, var(--surface)); color: var(--txt); border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; font-family: var(--mono); font-size: 12px; letter-spacing: .02em; }
        .errors ul { margin: 0; padding-left: 18px; }
        .errors li { margin: 3px 0; }
        .errors li::marker { content: "▮ "; color: #b91c1c; }

        /* ===== PANELS — each block a section of the cutting list, ruled at
           the top like a spec sheet header. */
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 26px; margin-bottom: 20px; box-shadow: 0 2px 0 0 var(--line); }
        .panel h4 { font-family: var(--mono); font-weight: 600; font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--faint); border-bottom: 2px solid var(--txt); padding-bottom: 12px; margin-bottom: 20px; }

        .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
        .frow:last-child { margin-bottom: 0; }
        .field { display: flex; flex-direction: column; margin-bottom: 0; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-family: var(--mono); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
        .field label small { text-transform: none; letter-spacing: 0; font-size: 11px; color: var(--faint); }
        .field input, .field select { border: 1px solid var(--line2); border-radius: 6px; background: var(--surface); padding: 12px 14px; font-family: var(--mono); font-size: 13px; color: var(--txt); width: 100%; }
        .field input:focus, .field select:focus { outline: none; border-color: var(--accent); }

        .check-row { display: flex; align-items: flex-start; gap: .625rem; font-size: 13px; line-height: 1.5; cursor: pointer; color: var(--muted); }
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
                <aside class="acct-side reveal {{ $theme->on('ruler') ? '' : 'no-rule' }}">
                    <div class="rule-ticks" aria-hidden="true"></div>
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
