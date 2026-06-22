@php
    $title = __('site.common.create_account');
@endphp
@extends('themes.brick.layout')

@section('content')
    <style>
        .auth { max-width: 500px; margin: 0 auto; padding: 48px 1.5rem 80px; }
        .auth-head { margin-bottom: 26px; }
        .auth-head .eyebrow { display: inline-flex; background: var(--ink); color: var(--accent); font-family: var(--display); font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; padding: 5px 11px; margin-bottom: 16px; }
        .auth-head h1 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(32px, 5vw, 48px); line-height: .9; letter-spacing: -.02em; }
        .auth-head p { color: var(--muted); font-size: 14px; margin-top: 10px; font-weight: 600; }

        .auth-card { border: 2.5px solid var(--ink); box-shadow: var(--pop-lg); background: var(--paper); padding: 32px 30px; }
        .errors { border: 2.5px solid #b91c1c; background: #fff; color: #b91c1c; padding: 12px 16px; margin-bottom: 22px; font-size: 13px; font-weight: 600; }
        .errors ul { margin: 0; padding-left: 18px; }

        /* class names match the shared _signup_fields partial */
        .field { margin-bottom: 18px; }
        .field-label { display: block; font-family: var(--display); font-size: 11px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
        .field-input { width: 100%; border: 2.5px solid var(--ink); background: #fff; padding: 13px 14px; font-family: var(--body); font-size: 14px; color: var(--ink); }
        .field-input:focus { outline: none; box-shadow: var(--pop-sm); }
        .field-hint { display: block; margin-top: 6px; font-size: 12px; color: var(--muted); }

        .shipping-group { border-top: 2.5px solid var(--ink); padding-top: 20px; margin-top: 20px; }
        .shipping-group .field-label { margin-bottom: 10px; }
        .shipping-group .field-input { margin-bottom: 8px; }
        .shipping-row { display: grid; grid-template-columns: 2fr 1fr; gap: 8px; }
        .shipping-row .field-input { margin-bottom: 8px; }
        .marketing-field { margin-top: 16px; }
        .marketing-label { display: flex; align-items: flex-start; gap: .625rem; cursor: pointer; font-size: 13px; font-weight: 600; line-height: 1.5; }
        .marketing-label input { margin-top: .15rem; width: 18px; height: 18px; flex-shrink: 0; accent-color: var(--ink); }

        .auth-foot { text-align: center; margin-top: 24px; font-size: 13px; font-weight: 600; color: var(--muted); }
        .auth-foot a { font-family: var(--display); font-weight: 700; text-transform: uppercase; font-size: 12px; border-bottom: 2.5px solid var(--accent); }
        .auth-foot a:hover { background: var(--accent); }
    </style>

    <main>
        <div class="auth rv">
            <div class="auth-head">
                <span class="eyebrow">{{ $tenant->name }}</span>
                <h1>{{ __('site.auth.register_title') }}</h1>
                <p>{{ __('site.auth.register_lead', ['tenant' => $tenant->name]) }}</p>
            </div>

            <div class="auth-card">
                @if ($errors->any())
                    <div class="errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <form method="post" action="/account/register">
                    @csrf
                    <div class="field">
                        <label class="field-label" for="name">{{ __('site.auth.full_name') }}</label>
                        <input class="field-input" type="text" name="name" id="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                    </div>
                    <div class="field">
                        <label class="field-label" for="email">{{ __('site.auth.email') }}</label>
                        <input class="field-input" type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email">
                    </div>
                    <div class="field">
                        <label class="field-label" for="password">{{ __('site.auth.password') }}</label>
                        <input class="field-input" type="password" name="password" id="password" required autocomplete="new-password">
                        <span class="field-hint">{{ __('site.auth.password_hint') }}</span>
                    </div>
                    <div class="field">
                        <label class="field-label" for="password_confirmation">{{ __('site.auth.password_confirm') }}</label>
                        <input class="field-input" type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password">
                    </div>

                    @php
                        $csSignup = $store->signupFieldsConfig();
                        $reqMarker = fn (bool $req) => $req ? '' : ' <span style="color: var(--muted); font-weight: 400; text-transform: none; letter-spacing: 0;">(' . __('site.auth.optional') . ')</span>';
                    @endphp

                    @include('storefront.auth._signup_fields')

                    <button type="submit" class="btn accent block">{{ __('site.auth.create_account_btn') }}</button>
                </form>
            </div>

            <div class="auth-foot">
                {{ __('site.auth.have_account') }} <a href="/account/login">{{ __('site.auth.sign_in_link') }}</a>
            </div>
        </div>
    </main>
@endsection
