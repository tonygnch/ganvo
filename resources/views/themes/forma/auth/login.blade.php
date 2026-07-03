@php
    $title = __('site.common.sign_in');
@endphp
@extends('themes.forma.layout')

@section('content')
    <style>
        .auth { display: grid; place-items: center; min-height: 82vh; padding: 50px 20px; position: relative; overflow: hidden; }
        .auth .spin { position: absolute; width: 300px; height: 300px; border: 1px dashed color-mix(in srgb, var(--accent) 30%, var(--line2)); border-radius: 50%; top: 50%; left: 50%; transform: translate(-50%, -50%); animation: spin 30s linear infinite; pointer-events: none; }
        .auth .spin::before { content: ""; position: absolute; top: -5px; left: 50%; width: 9px; height: 9px; border-radius: 50%; background: var(--accent); transform: translateX(-50%); }
        @keyframes spin { to { transform: translate(-50%, -50%) rotate(360deg); } }
        @media (prefers-reduced-motion: reduce) { .auth .spin { animation: none !important; } }

        .auth .card { position: relative; z-index: 2; background: var(--card); border: 1px solid var(--line); border-radius: 18px; width: min(440px, 100%); padding: 46px; text-align: left; box-shadow: 0 40px 80px -40px rgba(20, 22, 28, .35); }
        .auth .card .k { display: block; margin-bottom: 8px; }
        .auth .card h1 { font-family: var(--display); font-weight: 800; font-size: clamp(32px, 4vw, 46px); letter-spacing: -.02em; margin-bottom: 6px; }
        .auth .card h1 em { font-style: normal; color: var(--accent); }
        .auth .card .lede { color: var(--muted); font-size: 14px; margin-bottom: 26px; }

        .auth .errors { text-align: left; border: 1px solid #c0654a; background: color-mix(in srgb, #c0654a 7%, var(--card)); color: var(--ink); border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; font-size: 13px; }
        .auth .errors ul { margin: 0; padding-left: 18px; }

        .auth .field { text-align: left; margin-bottom: 16px; }
        .auth .field label { display: block; font-family: var(--mono); font-size: 11px; color: var(--muted); margin-bottom: 7px; }
        .auth .field input { width: 100%; border: 1px solid var(--line2); border-radius: 10px; background: var(--bg); padding: 13px 15px; font-family: inherit; font-size: 15px; color: var(--ink); }
        .auth .field input:focus { outline: none; border-color: var(--accent); }

        .auth .alt { margin-top: 20px; font-size: 13px; color: var(--muted); text-align: center; }
        .auth .alt a { color: var(--accent); font-weight: 600; }
    </style>

    <main>
        <div class="wrap">
            <section class="auth reveal">
                <div class="spin" aria-hidden="true"></div>

                <div class="card">
                    <span class="kicker k">// {{ $tenant->name }}</span>
                    <h1>{{ __('site.auth.login_title') }}</h1>
                    <p class="lede">{{ __('site.auth.login_lead', ['tenant' => $tenant->name]) }}</p>

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
                        <button type="submit" class="btn block">{{ __('site.auth.sign_in_btn') }}</button>
                    </form>
                </div>

                @if ($store->allow_registration)
                    <div class="alt">
                        {{ __('site.auth.new_here') }} <a href="/account/register">{{ __('site.auth.create_account_link') }} →</a>
                    </div>
                @endif
            </section>
        </div>
    </main>
@endsection
