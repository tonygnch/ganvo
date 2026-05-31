@php
    $title = __('site.common.sign_in');
@endphp
@extends('themes.default.layout')

@section('content')
    <style>
        .auth {
            max-width: 460px;
            margin: 0 auto;
            padding: 72px 1.75rem 96px;
        }
        .auth-head { text-align: center; margin-bottom: 36px; }
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
            font-size: clamp(34px, 5vw, 46px);
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

        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 600;
            margin-bottom: 8px;
        }
        .field input {
            width: 100%;
            border: 1px solid var(--line);
            background: #fff;
            padding: 14px 15px;
            font-family: var(--body);
            font-size: 14px;
            color: var(--ink);
            transition: border-color .15s ease;
        }
        .field input:focus { outline: none; border-color: var(--ink); }

        .auth-submit {
            width: 100%;
            background: var(--ink);
            color: var(--paper);
            border: 1px solid var(--ink);
            padding: 16px;
            margin-top: 8px;
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
                <h1>{{ __('site.auth.login_title') }}</h1>
                <p>{{ __('site.auth.login_lead', ['tenant' => $tenant->name]) }}</p>
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

                <form method="post" action="/account/login">
                    @csrf
                    <div class="field">
                        <label for="email">{{ __('site.auth.email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                    </div>
                    <div class="field">
                        <label for="password">{{ __('site.auth.password') }}</label>
                        <input type="password" name="password" id="password" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="auth-submit">{{ __('site.auth.sign_in_btn') }}</button>
                </form>
            </div>

            @if ($store->allow_registration)
                <div class="auth-foot">
                    {{ __('site.auth.new_here') }} <a href="/account/register">{{ __('site.auth.create_account_link') }}</a>
                </div>
            @endif
        </div>
    </main>
@endsection
