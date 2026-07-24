{{--
 | Timber — open an account. The trade-counter form: name, contact and
 | delivery written onto one ruled panel, the last word of the heading
 | inked in the treatment amber, ticks measuring off the head rule.
--}}
@php
    $title = __('site.common.create_account');
@endphp
@extends('themes.timber.layout')

@section('content')
    <style>
        /* ===== Register — the trade-account slip on the counter. ===== */
        .auth { display: grid; place-items: center; min-height: 82vh; padding: 50px 20px; position: relative; overflow: hidden; }

        /* end-grain accents — sawn-log rings drifting on an easy clock */
        .auth .ring.a1 { width: 170px; height: 170px; top: 8%; left: 8%; opacity: .55; animation: ringdrift 7s ease-in-out infinite; }
        .auth .ring.a2 { width: 96px; height: 96px; bottom: 12%; right: 12%; opacity: .4; animation: ringdrift 6s ease-in-out infinite .5s; }
        @keyframes ringdrift { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
        @media (prefers-reduced-motion: reduce) { .auth .ring { animation: none !important; } }

        /* the panel — planed surface, hard walnut shadow, square shoulders */
        .auth .card { position: relative; z-index: 2; background: var(--surface); border: 1px solid var(--line); border-radius: 10px; width: min(440px, 100%); padding: 42px 40px; text-align: center; box-shadow: 0 2px 0 0 var(--line); }
        /* grading stamp, inked over the top edge of the panel */
        .auth .card .gstamp { position: absolute; top: -13px; right: 20px; background: var(--surface); }

        /* head — ruled like a cutting list, ticks hanging off the rule */
        .auth .head { border-bottom: 2px solid var(--txt); padding-bottom: 16px; margin-bottom: 24px; }
        .auth .card .k { display: block; margin-bottom: 8px; }
        .auth .card h1 { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .01em; line-height: 1; font-size: clamp(34px, 4.4vw, 48px); margin-bottom: 8px; }
        .auth .card h1 em { font-style: normal; color: var(--accent-deep); }
        .auth .card .lede { color: var(--muted); font-size: 14px; }
        .auth .head + .rule-ticks { margin: -24px 0 20px; }

        /* rejected slip — the red-pencil note on the order */
        .auth .errors { text-align: left; border: 1px solid #b91c1c; background: color-mix(in srgb, #b91c1c 7%, var(--surface)); color: var(--txt); border-radius: 6px; padding: 12px 16px; margin-bottom: 22px; font-family: var(--mono); font-size: 12px; line-height: 1.55; box-shadow: 0 2px 0 0 color-mix(in srgb, #b91c1c 30%, var(--line)); }
        .auth .errors ul { margin: 0; padding-left: 0; list-style: none; }
        .auth .errors li { display: flex; gap: 8px; }
        .auth .errors li::before { content: "▮"; color: #b91c1c; }

        /* field chrome — class names match the shared _signup_fields partial */
        .auth .field { text-align: left; margin-bottom: 16px; }
        .auth .field-label { display: block; font-family: var(--mono); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
        .auth .field-input { width: 100%; border: 1px solid var(--line2); border-radius: 6px; background: var(--bg); padding: 13px 15px; font-family: var(--body); font-size: 15px; color: var(--txt); transition: border-color .2s ease; }
        .auth .field-input::placeholder { color: var(--faint); }
        .auth .field-input:focus { outline: none; border-color: var(--accent); }
        .auth .field-hint { display: block; margin-top: 6px; font-family: var(--mono); font-size: 11px; letter-spacing: .02em; color: var(--faint); }

        /* delivery group from _signup_fields — a ruled sub-block on the slip */
        .auth .shipping-group { border-top: 1px solid var(--line); padding-top: 18px; margin-top: 18px; }
        .auth .shipping-group .field-label { margin-bottom: 10px; }
        .auth .shipping-group .field-input { margin-bottom: 8px; }
        .auth .shipping-row { display: grid; grid-template-columns: 2fr 1fr; gap: 8px; }
        .auth .shipping-row .field-input { margin-bottom: 8px; }

        /* price-list opt-in */
        .auth .marketing-field { margin-top: 16px; }
        .auth .marketing-label { display: flex; align-items: flex-start; gap: .625rem; cursor: pointer; text-align: left; font-size: 13px; line-height: 1.5; color: var(--txt); }
        .auth .marketing-label input { margin-top: .2rem; width: 17px; height: 17px; flex-shrink: 0; accent-color: var(--accent); }

        .auth .card .btn.block { margin-top: 10px; }
        .auth .alt { margin-top: 22px; font-family: var(--mono); font-size: 12px; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
        .auth .alt a { color: var(--accent-deep); font-weight: 600; border-bottom: 1px solid transparent; }
        .auth .alt a:hover { border-color: currentColor; }

        @media (max-width: 540px) {
            .auth { padding: 34px 4px; }
            .auth .card { padding: 32px 24px; }
        }
    </style>

    <main>
        <section class="auth">
            @if ($theme->on('grain_rings'))
                <div class="ring a1" aria-hidden="true"></div>
                <div class="ring a2" aria-hidden="true"></div>
            @endif

            <div class="card reveal">
                @if ($theme->on('grade_stamp'))
                    <span class="stamp-tag gstamp" aria-hidden="true">{{ $theme->label('grade_stamp') }}</span>
                @endif

                @php
                    // Timber stencil accent: ink the final word of the (escaped) title
                    // in the treatment amber — em is upright here, never italic.
                    $regTitle = e(__('site.auth.register_title'));
                    $regTitle = preg_replace('/(\S+)\s*$/u', '<em>$1</em>', $regTitle, 1);
                @endphp
                <div class="head">
                    <div class="kicker k">{{ $tenant->name }}</div>
                    <h1>{!! $regTitle !!}</h1>
                    <p class="lede">{{ __('site.auth.register_lead', ['tenant' => $tenant->name]) }}</p>
                </div>
                @if ($theme->on('ruler'))<div class="rule-ticks" aria-hidden="true"></div>@endif

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
                        $reqMarker = fn (bool $req) => $req ? '' : ' <span style="color: var(--faint); font-weight: 400; text-transform: none; letter-spacing: 0;">(' . __('site.auth.optional') . ')</span>';
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
