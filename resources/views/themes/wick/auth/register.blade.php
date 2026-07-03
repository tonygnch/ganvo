@php
    $title = __('site.common.create_account');
@endphp
@extends('themes.wick.layout')

@section('content')
    <style>
        .auth { display: grid; place-items: center; min-height: 82vh; padding: 50px 20px; position: relative; overflow: hidden; }
        .auth .leafdot { position: absolute; border-radius: 50%; }
        .auth .l1 { width: 120px; height: 120px; background: var(--jar2); top: 8%; left: 10%; opacity: .5; animation: floaty 7s ease-in-out infinite; }
        .auth .l2 { width: 90px; height: 90px; background: var(--jar); bottom: 12%; right: 14%; opacity: .5; animation: floaty 6s ease-in-out infinite .5s; }
        @keyframes floaty { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
        @media (prefers-reduced-motion: reduce) { .auth .leafdot { animation: none !important; } }

        .auth .card { position: relative; z-index: 2; background: var(--card); border: 1px solid var(--line); border-radius: 14px; width: min(440px, 100%); padding: 46px; text-align: center; box-shadow: 0 40px 80px -40px rgba(0, 0, 0, .65); }
        .auth .card .tape { width: 120px; height: 28px; }
        .auth .card .k { margin-bottom: 8px; }
        .auth .card h1 { font-family: var(--display); font-size: clamp(32px, 4vw, 46px); margin-bottom: 6px; font-weight: 800; letter-spacing: -.02em; }
        .auth .card h1 em { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .auth .card .lede { color: var(--muted); font-size: 14px; margin-bottom: 26px; }

        /* errors */
        .auth .errors { text-align: left; border: 1px solid #b91c1c; background: color-mix(in srgb, #b91c1c 6%, var(--card)); color: var(--ink); border-radius: 10px; padding: 12px 16px; margin-bottom: 22px; font-size: 13px; }
        .auth .errors ul { margin: 0; padding-left: 18px; }

        /* field chrome — class names match the shared _signup_fields partial */
        .auth .field { text-align: left; margin-bottom: 16px; }
        .auth .field-label { display: block; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
        .auth .field-input { width: 100%; border: 1px solid var(--line); border-radius: 10px; background: var(--bg); padding: 13px 15px; font-family: inherit; font-size: 15px; color: var(--ink); }
        .auth .field-input:focus { outline: none; border-color: var(--accent); }
        .auth .field-hint { display: block; margin-top: 6px; font-size: 12px; color: var(--muted); }

        /* shipping group from _signup_fields */
        .auth .shipping-group { border-top: 1px solid var(--line); padding-top: 18px; margin-top: 18px; }
        .auth .shipping-group .field-label { margin-bottom: 10px; }
        .auth .shipping-group .field-input { margin-bottom: 8px; }
        .auth .shipping-row { display: grid; grid-template-columns: 2fr 1fr; gap: 8px; }
        .auth .shipping-row .field-input { margin-bottom: 8px; }

        /* marketing opt-in */
        .auth .marketing-field { margin-top: 16px; }
        .auth .marketing-label { display: flex; align-items: flex-start; gap: .625rem; cursor: pointer; font-size: 13px; line-height: 1.5; color: var(--ink); }
        .auth .marketing-label input { margin-top: .2rem; width: 17px; height: 17px; flex-shrink: 0; accent-color: var(--accent); }

        .auth .card .btn.block { margin-top: 8px; }
        .auth .alt { margin-top: 20px; font-size: 13px; color: var(--muted); }
        .auth .alt a { color: var(--accent); font-weight: 600; }
        .auth .alt a:hover { text-decoration: underline; }
    </style>

    <main>
        <section class="auth">
            <div class="leafdot l1" aria-hidden="true"></div>
            <div class="leafdot l2" aria-hidden="true"></div>

            <div class="card reveal">
                <div class="tape" aria-hidden="true"></div>
                <div class="kicker k">{{ $tenant->name }}</div>
                @php
                    // Posy editorial accent: italicise the final word of the (escaped) title.
                    $regTitle = e(__('site.auth.register_title'));
                    $regTitle = preg_replace('/(\S+)\s*$/u', '<em>$1</em>', $regTitle, 1);
                @endphp
                <h1>{!! $regTitle !!}</h1>
                <p class="lede">{{ __('site.auth.register_lead', ['tenant' => $tenant->name]) }}</p>

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

                    <button type="submit" class="btn block">{{ __('site.auth.create_account_btn') }}</button>
                </form>

                <div class="alt">
                    {{ __('site.auth.have_account') }} <a href="/account/login">{{ __('site.auth.sign_in_link') }} →</a>
                </div>
            </div>
        </section>
    </main>
@endsection
