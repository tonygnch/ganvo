{{-- Sankevi — the yard gate. The details sheet carries the address, the line
     and the opening hours; the enquiry pad sits beside it, the way an order
     form waits on the counter. --}}
@php
    $title = __('site.storefront.contact.title');
    // Blank heading/intro mean the merchant never touched them — fall back to
    // the platform copy rather than printing an empty rule.
    $heading = $contact['heading'] !== '' ? $contact['heading'] : __('site.storefront.contact.heading');
    $intro = $contact['intro'] !== '' ? $contact['intro'] : __('site.storefront.contact.intro');
    $hasSide = $contact['has_details'] || $contact['map_embed'];
    $subjects = [
        'general' => __('site.storefront.contact.subject_general'),
        'quote' => __('site.storefront.contact.subject_quote'),
        'order' => __('site.storefront.contact.subject_order'),
        'delivery' => __('site.storefront.contact.subject_delivery'),
        'returns' => __('site.storefront.contact.subject_returns'),
    ];
    // Nullable server-side, but a blank first option would need copy we do not
    // have — "general enquiry" is the honest default for an unpicked select.
    $selectedSubject = old('subject', 'general');
@endphp
@extends('themes.sankevi.layout')

@section('content')
    <style>
        .ct-wrap { padding: 0 0 90px; }
        .contact { display: grid; grid-template-columns: 330px 1fr; gap: 34px; align-items: start; padding-top: 40px; }
        .contact.solo { grid-template-columns: 1fr; max-width: 780px; }

        .ct-panel { position: relative; background: var(--surface); border: 1px solid var(--line); padding: 28px 30px 30px; margin-bottom: 22px; }
        .ct-panel:last-child { margin-bottom: 0; }
        .ct-panel h3 { font-family: var(--display); font-weight: 500; font-size: 23px; line-height: 1.1; padding-bottom: 15px; margin-bottom: 18px; border-bottom: 1px solid var(--line2); }

        .ct-row { padding: 14px 0; border-bottom: 1px solid var(--line); }
        .ct-row:first-of-type { padding-top: 0; }
        .ct-row:last-child { padding-bottom: 0; border-bottom: none; }
        .ct-row .k { display: block; margin-bottom: 7px; font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }
        .ct-row .v { display: block; font-size: 15px; line-height: 1.65; color: var(--txt); }
        /* Address and hours are typed the way they'd be printed on the gate
           sign — keep the merchant's line breaks instead of collapsing them. */
        .ct-row .v.lines { white-space: pre-line; }
        .ct-row a.v { width: fit-content; border-bottom: 1px solid transparent; transition: color .25s ease, border-color .25s ease; }
        .ct-row a.v:hover { color: var(--accent); border-color: currentColor; }

        /* the merchant pastes their own <iframe>; the ratio box is ours, so a
           pasted width/height cannot blow the rail apart */
        .ct-map { padding: 0; }
        .ct-map h3 { padding: 26px 30px 15px; margin-bottom: 0; }
        .ct-map .frame { position: relative; padding-top: 76%; }
        .ct-map .frame iframe { position: absolute; inset: 0; width: 100% !important; height: 100% !important; border: 0; display: block; }

        .ct-form .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .ct-form .frow + .frow { margin-top: 18px; }
        .field { display: flex; flex-direction: column; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); margin-bottom: 9px; }
        .field label small { text-transform: none; letter-spacing: .02em; color: var(--faint); }
        .field input, .field select, .field textarea { width: 100%; background: var(--bg); border: 1px solid var(--line2); padding: 13px 15px; font-family: var(--body); font-size: 14.5px; color: var(--txt); transition: border-color .25s ease; }
        .field input::placeholder, .field textarea::placeholder { color: var(--faint); }
        .field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: var(--accent); }
        .field textarea { min-height: 170px; resize: vertical; }

        /* the pencil note against the offending line — mixed with --txt so it
           survives the daylight mode too */
        main { --gv-error: color-mix(in srgb, #e5484d 58%, var(--txt)); }
        .field.bad input, .field.bad select, .field.bad textarea { border-color: var(--gv-error); }
        .field .err { margin-top: 8px; font-size: 12px; line-height: 1.5; color: var(--gv-error); }
        .field .err::before { content: "— "; }

        .ct-form .btn { margin-top: 26px; }

        /* Honeypot — parked off-canvas rather than display:none, which the
           better-behaved bots skip over. */
        .hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }

        .ct-sent { display: flex; align-items: flex-start; gap: 18px; border-color: var(--accent); background: color-mix(in srgb, var(--accent) 10%, var(--surface)); }
        .ct-sent .mark { flex-shrink: 0; width: 42px; height: 42px; display: grid; place-items: center; background: var(--accent); color: var(--on-accent); font-size: 18px; }
        .ct-sent h3 { border-bottom: none; padding-bottom: 0; margin-bottom: 6px; font-size: 21px; }
        .ct-sent p { color: var(--muted); font-size: 14.5px; }

        @media (max-width: 980px) { .contact { grid-template-columns: 1fr; } }
        @media (max-width: 560px) {
            .ct-form .frow { grid-template-columns: 1fr; }
            .ct-panel { padding: 22px 20px 24px; }
            .ct-map h3 { padding: 20px 20px 14px; }
        }
    </style>

    <main>
        <div class="wrap ct-wrap">
            <div class="page-head reveal">
                @if ($theme->on('gutter_index'))
                    <span class="gx" aria-hidden="true" style="top: 60px;"><b>{{ $theme->label('gutter_index') }}</b> {{ __('site.storefront.contact.title') }}</span>
                @endif
                <div class="crumb"><a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a> / {{ __('site.storefront.contact.title') }}</div>
                <h1>{{ $heading }}</h1>
                @if ($intro !== '')<p>{{ $intro }}</p>@endif
            </div>

            <div class="contact {{ $hasSide && $contact['show_form'] ? '' : 'solo' }}">
                @if ($hasSide)
                    <aside class="ct-side reveal">
                        @if ($contact['has_details'])
                            <div class="ct-panel cut">
                                <h3>{{ __('site.storefront.contact.details_h') }}</h3>
                                @if ($contact['address'])
                                    <div class="ct-row">
                                        <span class="k">{{ __('site.storefront.contact.address_label') }}</span>
                                        <span class="v lines">{{ $contact['address'] }}</span>
                                    </div>
                                @endif
                                @if ($contact['phone'])
                                    <div class="ct-row">
                                        <span class="k">{{ __('site.storefront.contact.phone_label') }}</span>
                                        <a class="v" href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a>
                                    </div>
                                @endif
                                @if ($contact['email'])
                                    <div class="ct-row">
                                        <span class="k">{{ __('site.storefront.contact.email_label') }}</span>
                                        <a class="v" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                                    </div>
                                @endif
                                @if ($contact['hours'])
                                    <div class="ct-row">
                                        <span class="k">{{ __('site.storefront.contact.hours_label') }}</span>
                                        <span class="v lines">{{ $contact['hours'] }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($contact['map_embed'])
                            <div class="ct-panel ct-map cut">
                                <h3>{{ __('site.storefront.contact.map_label') }}</h3>
                                {{-- Store::contactPage() has already rejected anything that is
                                     not a bare https <iframe>, so this is safe to print raw. --}}
                                <div class="frame">{!! $contact['map_embed'] !!}</div>
                            </div>
                        @endif
                    </aside>
                @endif

                @if ($contact['show_form'])
                    <div class="ct-main reveal s1">
                        @if (session('contact.sent'))
                            <div class="ct-panel ct-sent cut" role="status">
                                <span class="mark" aria-hidden="true">✓</span>
                                <div>
                                    <h3>{{ __('site.storefront.contact.sent_title') }}</h3>
                                    <p>{{ __('site.storefront.contact.sent_body') }}</p>
                                </div>
                            </div>
                        @endif

                        <form method="post" action="/contact" class="ct-panel ct-form cut" data-ct-form>
                            @csrf
                            <h3>{{ __('site.storefront.contact.form_h') }}</h3>

                            <div class="hp" aria-hidden="true">
                                <label for="ct-website">Website</label>
                                <input type="text" name="website" id="ct-website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="frow">
                                <div class="field {{ $errors->has('name') ? 'bad' : '' }}">
                                    <label for="ct-name">{{ __('site.storefront.contact.field_name') }}</label>
                                    <input type="text" name="name" id="ct-name" value="{{ old('name', $customer?->name) }}" maxlength="120" autocomplete="name" required>
                                    @error('name')<span class="err">{{ $message }}</span>@enderror
                                </div>
                                <div class="field {{ $errors->has('email') ? 'bad' : '' }}">
                                    <label for="ct-email">{{ __('site.storefront.contact.field_email') }}</label>
                                    <input type="email" name="email" id="ct-email" value="{{ old('email', $customer?->email) }}" maxlength="255" autocomplete="email" required>
                                    @error('email')<span class="err">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="frow">
                                <div class="field {{ $errors->has('phone') ? 'bad' : '' }}">
                                    <label for="ct-phone">{{ __('site.storefront.contact.field_phone') }} <small>({{ __('site.common.optional') }})</small></label>
                                    <input type="tel" name="phone" id="ct-phone" value="{{ old('phone', $customer?->phone) }}" maxlength="40" autocomplete="tel">
                                    @error('phone')<span class="err">{{ $message }}</span>@enderror
                                </div>
                                <div class="field {{ $errors->has('subject') ? 'bad' : '' }}">
                                    <label for="ct-subject">{{ __('site.storefront.contact.field_subject') }}</label>
                                    <select name="subject" id="ct-subject">
                                        @foreach ($subjects as $key => $label)
                                            <option value="{{ $key }}" @selected($selectedSubject === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('subject')<span class="err">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="frow">
                                <div class="field full {{ $errors->has('message') ? 'bad' : '' }}">
                                    <label for="ct-message">{{ __('site.storefront.contact.field_message') }}</label>
                                    <textarea name="message" id="ct-message" minlength="10" maxlength="4000" required>{{ old('message') }}</textarea>
                                    @error('message')<span class="err">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <button type="submit" class="btn" data-sending="{{ __('site.storefront.contact.sending') }}">{{ __('site.storefront.contact.send') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </main>

    <script>
        // The POST is a full page load, so the button has to say something
        // between the click and the new document — and say it once, so an
        // impatient second click cannot post the enquiry twice.
        (function () {
            var form = document.querySelector('form[data-ct-form]');
            if (! form) return;
            form.addEventListener('submit', function () {
                var btn = form.querySelector('[data-sending]');
                if (! btn || btn.getAttribute('aria-busy')) return;
                btn.setAttribute('aria-busy', 'true');
                btn.textContent = btn.getAttribute('data-sending');
            });
        })();
    </script>
@endsection
