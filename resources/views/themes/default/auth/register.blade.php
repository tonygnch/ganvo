@php
    $title = __('site.common.create_account');
@endphp
@extends('themes.default.layout')

@section('content')
    <style>
        .auth {
            max-width: 500px;
            margin: 0 auto;
            padding: 64px 1.75rem 96px;
        }
        .auth-head { text-align: center; margin-bottom: 32px; }
        .auth-head .eyebrow {
            font-size: 11px;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--muted);
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 16px;
        }
        .auth-head .eyebrow::before,
        .auth-head .eyebrow::after {
            content: "";
            width: 24px;
            height: 1px;
            background: currentColor;
            opacity: .5;
        }
        .auth-head h1 {
            font-family: var(--display);
            font-size: clamp(32px, 5vw, 44px);
            font-weight: 500;
            line-height: 1.05;
        }
        .auth-head p {
            color: var(--muted);
            font-size: 14px;
            margin-top: 10px;
            line-height: 1.6;
        }

        .auth-card {
            border: 1px solid var(--line);
            background: var(--paper);
            padding: 36px 34px;
        }

        .errors {
            border: 1px solid #b91c1c;
            background: rgba(185, 28, 28, .04);
            color: #b91c1c;
            padding: 12px 16px;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .errors ul { margin: 0; padding-left: 18px; }
        .errors li { margin: 2px 0; }

        /* Field styling — class names match the shared _signup_fields partial
           so the merchant-toggled extra fields inherit the Atelier look. */
        .field { margin-bottom: 18px; }
        .field-label {
            display: block;
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 600;
            margin-bottom: 8px;
        }
        .field-input {
            width: 100%;
            border: 1px solid var(--line);
            background: #fff;
            padding: 14px 15px;
            font-family: var(--body);
            font-size: 14px;
            color: var(--ink);
            transition: border-color .15s ease;
        }
        .field-input:focus { outline: none; border-color: var(--ink); }
        .field-hint {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: var(--muted);
            letter-spacing: .01em;
        }

        /* shared signup-fields hooks */
        .shipping-group { border-top: 1px solid var(--line); padding-top: 22px; margin-top: 22px; }
        .shipping-group .field-label { margin-bottom: 10px; }
        .shipping-group .field-input { margin-bottom: 8px; }
        .shipping-row { display: grid; grid-template-columns: 2fr 1fr; gap: 8px; }
        .shipping-row .field-input { margin-bottom: 8px; }
        .marketing-field { margin-top: 16px; }
        .marketing-label { display: flex; align-items: flex-start; gap: .625rem; cursor: pointer; font-size: 13px; line-height: 1.5; color: var(--ink-soft, #4f4a40); }
        .marketing-label input { margin-top: .15rem; flex-shrink: 0; accent-color: var(--accent); }

        .auth-submit {
            width: 100%;
            background: var(--ink);
            color: var(--paper);
            border: 1px solid var(--ink);
            padding: 16px;
            margin-top: 12px;
            font-size: 12px;
            letter-spacing: .18em;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--body);
            transition: background-color .2s ease, border-color .2s ease;
        }
        .auth-submit:hover { background: var(--accent); border-color: var(--accent); }

        .auth-foot {
            text-align: center;
            margin-top: 28px;
            font-size: 13px;
            color: var(--muted);
            letter-spacing: .02em;
        }
        .auth-foot a {
            color: var(--ink);
            font-weight: 600;
            border-bottom: 1px solid currentColor;
            padding-bottom: 1px;
            transition: color .15s ease;
        }
        .auth-foot a:hover { color: var(--accent); }
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
                    <div class="errors">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
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
                        // The shared _signup_fields partial expects these two
                        // (set by the generic register page; we mirror them here).
                        $csSignup = $store->signupFieldsConfig();
                        $reqMarker = fn (bool $req) => $req ? '' : ' <span style="color: var(--muted); font-weight: 400; text-transform: none; letter-spacing: 0;">(' . __('site.auth.optional') . ')</span>';
                    @endphp

                    {{-- Merchant-toggleable extra fields (phone, birthday, shipping
                         address, marketing opt-in). Shared partial; inherits the
                         Atelier field styling above. --}}
                    @include('storefront.auth._signup_fields')

                    <button type="submit" class="auth-submit">{{ __('site.auth.create_account_btn') }}</button>
                </form>
            </div>

            <div class="auth-foot">
                {{ __('site.auth.have_account') }} <a href="/account/login">{{ __('site.auth.sign_in_link') }}</a>
            </div>
        </div>
    </main>
@endsection
