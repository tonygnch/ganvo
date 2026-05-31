@php $title = __('site.common.sign_in'); @endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .auth { max-width: 440px; margin: 0 auto; padding: 70px 36px 96px; }
        .auth-head { text-align: center; margin-bottom: 30px; }
        .auth-head .eyebrow { font-family: var(--mono); font-size: 11px; color: var(--accent); text-transform: uppercase; }
        .auth-head h1 { font-family: var(--archivo); font-weight: 800; font-size: clamp(30px,5vw,42px); letter-spacing: -.02em; margin-top: 12px; }
        .auth-head p { color: var(--muted); font-size: 14px; margin-top: 8px; }
        .auth-card { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 34px; }
        .errors { border: 1px solid #ff5c5c; background: rgba(255,92,92,.06); color: #ff8a8a; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; } .errors ul { padding-left: 18px; }
        .field { margin-bottom: 16px; }
        .field label { display: block; font-family: var(--mono); font-size: 11px; color: var(--muted); margin-bottom: 8px; }
        .field input { width: 100%; background: var(--bg); border: 1px solid var(--line); border-radius: 8px; padding: 14px; color: var(--txt); font-family: inherit; font-size: 14px; }
        .field input:focus { outline: none; border-color: var(--accent); }
        .auth-submit { width: 100%; background: var(--accent); color: #0a0b0e; border: 0; border-radius: 6px; padding: 15px; font-weight: 700; font-size: 14px; cursor: pointer; margin-top: 6px; }
        .auth-foot { text-align: center; margin-top: 24px; font-family: var(--mono); font-size: 13px; color: var(--muted); }
        .auth-foot a { color: var(--accent); }
    </style>

    <main>
        <div class="auth rv">
            <div class="auth-head">
                <div class="eyebrow">// {{ $tenant->name }}</div>
                <h1>{{ __('site.auth.login_title') }}</h1>
                <p>{{ __('site.auth.login_lead', ['tenant' => $tenant->name]) }}</p>
            </div>
            <div class="auth-card">
                @if ($errors->any())<div class="errors"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                <form method="post" action="/account/login">
                    @csrf
                    <div class="field"><label>{{ __('site.auth.email') }}</label><input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></div>
                    <div class="field"><label>{{ __('site.auth.password') }}</label><input type="password" name="password" required autocomplete="current-password"></div>
                    <button type="submit" class="auth-submit">{{ __('site.auth.sign_in_btn') }}</button>
                </form>
            </div>
            @if ($store->allow_registration)
                <div class="auth-foot">{{ __('site.auth.new_here') }} <a href="/account/register">{{ __('site.auth.create_account_link') }}</a></div>
            @endif
        </div>
    </main>
@endsection
