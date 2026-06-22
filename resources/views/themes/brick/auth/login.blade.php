@php
    $title = __('site.common.sign_in');
@endphp
@extends('themes.brick.layout')

@section('content')
    <style>
        .auth { max-width: 460px; margin: 0 auto; padding: 52px 1.5rem 80px; }
        .auth-head { margin-bottom: 28px; }
        .auth-head .eyebrow { display: inline-flex; background: var(--ink); color: var(--accent); font-family: var(--display); font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; padding: 5px 11px; margin-bottom: 16px; }
        .auth-head h1 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(34px, 5vw, 52px); line-height: .9; letter-spacing: -.02em; }
        .auth-head p { color: var(--muted); font-size: 14px; margin-top: 10px; font-weight: 600; }

        .auth-card { border: 2.5px solid var(--ink); box-shadow: var(--pop-lg); background: var(--paper); padding: 32px 30px; }
        .errors { border: 2.5px solid #b91c1c; background: #fff; color: #b91c1c; padding: 12px 16px; margin-bottom: 22px; font-size: 13px; font-weight: 600; }
        .errors ul { margin: 0; padding-left: 18px; }

        .field { margin-bottom: 18px; }
        .field label { display: block; font-family: var(--display); font-size: 11px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
        .field input { width: 100%; border: 2.5px solid var(--ink); background: #fff; padding: 13px 14px; font-family: var(--body); font-size: 14px; color: var(--ink); }
        .field input:focus { outline: none; box-shadow: var(--pop-sm); }

        .auth-foot { text-align: center; margin-top: 24px; font-size: 13px; font-weight: 600; color: var(--muted); }
        .auth-foot a { font-family: var(--display); font-weight: 700; text-transform: uppercase; font-size: 12px; border-bottom: 2.5px solid var(--accent); }
        .auth-foot a:hover { background: var(--accent); }
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
                    <div class="errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
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
                    <button type="submit" class="btn accent block">{{ __('site.auth.sign_in_btn') }}</button>
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
