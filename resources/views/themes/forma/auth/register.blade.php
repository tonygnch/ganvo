@php
    $title = __('site.common.create_account');
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
        .auth .card .k { margin-bottom: 8px; }
        .auth .card h1 { font-family: var(--display); font-weight: 800; font-size: clamp(32px, 4vw, 46px); letter-spacing: -.02em; margin-bottom: 6px; }
        .auth .card h1 em { font-style: normal; color: var(--accent); }
        .auth .card .lede { color: var(--muted); font-size: 14px; margin-bottom: 26px; }

        /* errors */
        .auth .errors { text-align: left; border: 1px solid #c0654a; background: color-mix(in srgb, #c0654a 6%, var(--card)); color: var(--ink); border-radius: 10px; padding: 12px 16px; margin-bottom: 22px; font-size: 13px; }
        .auth .errors ul { margin: 0; padding-left: 18px; }

        /* field chrome — class names match the shared _signup_fields partial */
        .auth .field { text-align: left; margin-bottom: 16px; }
        .auth .field-label { display: block; font-family: var(--mono); font-size: 11px; color: var(--muted); margin-bottom: 7px; }
        .auth .field-input { width: 100%; border: 1px solid var(--line2); border-radius: 10px; background: var(--bg); padding: 13px 15px; font-family: inherit; font-size: 15px; color: var(--ink); }
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
        .auth .alt { margin-top: 20px; font-size: 13px; color: var(--muted); text-align: center; }
        .auth .alt a { color: var(--accent); font-weight: 600; }
        .auth .alt a:hover { text-decoration: underline; }
    </style>

    <main>
        <section class="auth">
            <div class="spin" aria-hidden="true"></div>

            <div class="card reveal">
                <div class="kicker k">// {{ $tenant->name }}</div>
                @php
                    // Forma accent: wrap the final word of the (escaped) title for the cobalt highlight.
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
