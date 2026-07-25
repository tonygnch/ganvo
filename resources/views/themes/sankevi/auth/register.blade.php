{{--
 | Sankevi — open an account. The same counter sheet as sign-in, longer:
 | name, contact and the delivery address the yard truck will drive to.
--}}
@php
    $title = __('site.common.create_account');
@endphp
@extends('themes.sankevi.layout')

@section('content')
    <style>
        .auth { position: relative; display: grid; place-items: center; min-height: 80vh; padding: 60px 0; }
        .auth::before { content: ""; position: absolute; width: min(720px, 90%); aspect-ratio: 1; border-radius: 50%; background: radial-gradient(circle, color-mix(in srgb, var(--moss) 42%, transparent), transparent 68%); pointer-events: none; }
        .auth .card { position: relative; z-index: 2; width: 100%; background: var(--surface); border: 1px solid var(--line); padding: 46px 44px; }
        /* The seal hangs 26px above the card's top edge — but the card carries
           the planed-corner motif, and clip-path clips EVERY descendant no
           matter what overflow says. Inside the card, the overhanging half was
           simply cut away, which is what "half hidden" looked like. So the
           seal now lives on an unclipped wrapper, as a SIBLING of the card. */
        .auth .cardwrap { position: relative; z-index: 2; width: min(470px, 100%); }
        .auth .cardwrap .seal {
            position: absolute; top: -26px; right: 26px; z-index: 3;
            width: 52px; height: 52px;
            background-image: var(--seal); background-size: contain;
            background-repeat: no-repeat; background-position: center;
        }
        /* the card and the page behind it are both pale in Daylight, so the
           cream colourway would vanish there — swap to the walnut master */
        html[data-mode="light"] .auth .cardwrap .seal { background-image: var(--seal-day); }

        .auth .head { padding-bottom: 22px; margin-bottom: 26px; border-bottom: 1px solid var(--line2); }
        .auth .card .k { display: block; margin-bottom: 14px; }
        .auth .card h1 { font-family: var(--display); font-weight: 500; font-size: clamp(27px, 3.4vw, 37px); line-height: 1.02; letter-spacing: 0; margin-bottom: 10px; }
        .auth .card h1 em { font-style: normal; font-weight: 600; color: var(--accent); }
        .auth .card .lede { color: var(--muted); font-size: 14.5px; }

        .auth .errors { border: 1px solid #b4614a; border-left-width: 3px; background: color-mix(in srgb, #b4614a 12%, var(--surface)); padding: 13px 16px; margin-bottom: 22px; font-size: 13px; line-height: 1.55; }
        .auth .errors ul { list-style: none; }
        .auth .errors li { display: flex; gap: 9px; }
        .auth .errors li::before { content: "—"; color: #d08a70; }

        /* field chrome — class names match the shared _signup_fields partial */
        .auth .field { margin-bottom: 18px; }
        .auth .field-label { display: block; font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); margin-bottom: 9px; }
        .auth .field-input { width: 100%; background: var(--bg); border: 1px solid var(--line2); padding: 14px 15px; font-family: var(--body); font-size: 15px; color: var(--txt); transition: border-color .25s ease; }
        .auth .field-input::placeholder { color: var(--faint); }
        .auth .field-input:focus { outline: none; border-color: var(--accent); }
        .auth .field-hint { display: block; margin-top: 7px; font-size: 11px; letter-spacing: .04em; color: var(--faint); }

        .auth .shipping-group { border-top: 1px solid var(--line); padding-top: 20px; margin-top: 20px; }
        .auth .shipping-group .field-label { margin-bottom: 11px; }
        .auth .shipping-group .field-input { margin-bottom: 9px; }
        .auth .shipping-row { display: grid; grid-template-columns: 2fr 1fr; gap: 9px; }
        .auth .shipping-row .field-input { margin-bottom: 9px; }

        .auth .marketing-field { margin-top: 18px; }
        .auth .marketing-label { display: flex; align-items: flex-start; gap: .7rem; cursor: pointer; font-size: 13px; line-height: 1.55; color: var(--muted); }
        .auth .marketing-label input { margin-top: .22rem; width: 16px; height: 16px; flex-shrink: 0; accent-color: var(--accent); }

        .auth .card .btn.block { margin-top: 14px; }
        .auth .alt { margin-top: 26px; text-align: center; font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); }
        .auth .alt a { color: var(--accent); border-bottom: 1px solid transparent; }
        .auth .alt a:hover { border-color: currentColor; }

        @media (max-width: 560px) {
            .auth { padding: 40px 0; }
            .auth .card { padding: 34px 24px; }
        }
    </style>

    <main>
        <div class="wrap">
            <section class="auth">
                <div class="cardwrap reveal">
                    @if ($theme->on('brand_seal') && ($seal = $theme->image('seal_image')))
                        @php
                            // Same two-colourway pairing the layout uses for the nav
                            // mark: the shipped seal is cream, which disappears on the
                            // pale Daylight card. A merchant's own upload is shown in
                            // both modes — we cannot recolour someone else's artwork.
                            $sealDay = str_contains($seal, 'mark-cream.svg')
                                ? str_replace('mark-cream.svg', 'mark.svg', $seal)
                                : $seal;
                        @endphp
                        <span class="seal" aria-hidden="true" style="--seal: url('{{ $seal }}'); --seal-day: url('{{ $sealDay }}');"></span>
                    @endif
                <div class="card cut cut-lg">

                    @php
                        // Editorial accent: the last word of the (escaped) title
                        // goes italic in the lichen — the serif's own voice.
                        $regTitle = e(__('site.auth.register_title'));
                        $regTitle = preg_replace('/(\S+)\s*$/u', '<em>$1</em>', $regTitle, 1);
                    @endphp
                    <div class="head">
                        <span class="kicker k">{{ $tenant->name }}</span>
                        <h1>{!! $regTitle !!}</h1>
                        <p class="lede">{{ __('site.auth.register_lead', ['tenant' => $tenant->name]) }}</p>
                    </div>

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
                </div>
            </section>
        </div>
    </main>
@endsection
