{{-- Timber — the yard office. The counter's own card carries the address,
     the line and the opening hours (a dispatch note, ruled and stamped);
     the enquiry pad sits beside it like an order form waiting to be filled
     in over the counter. --}}
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
@extends('themes.timber.layout')

@section('content')
    <style>
        .ct-wrap { padding: 0 0 80px; }
        .contact { position: relative; display: grid; grid-template-columns: 340px 1fr; gap: 32px; align-items: start; padding-top: 30px; }
        .contact.solo { grid-template-columns: 1fr; max-width: 760px; }
        .contact .ring.c1 { width: 210px; height: 210px; right: -82px; top: -34px; opacity: .4; }
        .contact .ring.c2 { width: 96px; height: 96px; left: -62px; bottom: 60px; opacity: .3; }

        /* ===== Panels — planed board, hard walnut shadow, square shoulders. */
        .ct-panel { position: relative; overflow: hidden; background: var(--surface); border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 2px 0 0 var(--line); padding: 24px 26px 26px; margin-bottom: 20px; }
        .ct-panel:last-child { margin-bottom: 0; }
        .ct-panel > .rule-ticks { position: absolute; left: 0; right: 0; top: 0; }
        .ct-panel h3 { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .02em; font-size: 22px; line-height: 1.1; padding-bottom: 13px; margin-bottom: 16px; border-bottom: 2px solid var(--txt); }
        .ct-panel h3::before { content: "▮ "; color: var(--accent); }

        /* ===== Details — the spec lines off the gate sign. */
        .ct-row { padding: 13px 0; border-bottom: 1px solid var(--line); }
        .ct-row:first-of-type { padding-top: 0; }
        .ct-row:last-child { padding-bottom: 0; border-bottom: none; }
        .ct-row .k { display: block; font-family: var(--mono); font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--faint); margin-bottom: 6px; }
        .ct-row .v { display: block; font-size: 15px; line-height: 1.6; color: var(--txt); }
        /* Address and hours are typed the way they would be printed on the
           gate sign — keep the merchant's line breaks instead of collapsing. */
        .ct-row .v.lines { white-space: pre-line; }
        .ct-row a.v { border-bottom: 1px solid transparent; width: fit-content; transition: color .2s ease, border-color .2s ease; }
        .ct-row a.v:hover { color: var(--accent-deep); border-color: currentColor; }

        /* ===== Map — the merchant pastes their own <iframe>; the ratio box is
           ours, so a pasted width/height cannot blow the rail apart. */
        .ct-map { padding: 0; }
        .ct-map h3 { padding: 22px 26px 13px; margin-bottom: 0; }
        .ct-map .frame { position: relative; padding-top: 74%; }
        .ct-map .frame iframe { position: absolute; inset: 0; width: 100% !important; height: 100% !important; border: 0; display: block; }

        /* ===== Enquiry pad — the order form over the counter. */
        .ct-form .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .ct-form .frow + .frow { margin-top: 16px; }
        .field { display: flex; flex-direction: column; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-family: var(--mono); font-size: 11px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
        .field label small { text-transform: none; letter-spacing: .02em; font-weight: 400; color: var(--faint); }
        .field input, .field select, .field textarea { width: 100%; border: 1px solid var(--line2); background: var(--bg); border-radius: 5px; padding: 12px 14px; font-family: var(--body); font-size: 14px; color: var(--txt); transition: border-color .2s ease; }
        .field input::placeholder, .field textarea::placeholder { color: var(--faint); }
        .field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: var(--accent); }
        .field textarea { min-height: 160px; resize: vertical; }

        /* red-pencil note against the offending line */
        /* error ink blends with --txt so it survives Timber's Workshop-night mode */
        main { --gv-error: color-mix(in srgb, #e5484d 62%, var(--txt)); }
        .field.bad input, .field.bad select, .field.bad textarea { border-color: var(--gv-error); }
        .field .err { font-family: var(--mono); font-size: 11.5px; letter-spacing: .02em; line-height: 1.5; color: var(--gv-error); margin-top: 7px; }
        .field .err::before { content: "▮ "; }

        .ct-form .btn { margin-top: 22px; }

        /* Honeypot — parked off-canvas rather than display:none, which the
           better-behaved bots skip over. */
        .hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }

        /* ===== The receipt — inked amber, the one stamped note on the page. */
        .ct-sent { background: color-mix(in srgb, var(--accent) 12%, var(--surface)); border-color: var(--accent-deep); box-shadow: 0 2px 0 0 var(--accent-deep); display: flex; align-items: flex-start; gap: 16px; }
        .ct-sent .mark { width: 44px; height: 44px; flex-shrink: 0; display: grid; place-items: center; border: 1px solid var(--accent-deep); border-radius: 6px; background: var(--accent); color: var(--on-accent); font-size: 20px; box-shadow: 0 2px 0 0 var(--accent-deep); }
        .ct-sent h3 { border-bottom: none; padding-bottom: 0; margin-bottom: 5px; font-size: 21px; }
        .ct-sent h3::before { content: none; }
        .ct-sent p { color: var(--muted); font-size: 14.5px; }

        @media (max-width: 980px) {
            .contact { grid-template-columns: 1fr; }
            .contact .ring { display: none; }
        }
        @media (max-width: 540px) {
            .ct-form .frow { grid-template-columns: 1fr; }
            .ct-panel { padding: 20px 18px 22px; }
            .ct-map h3 { padding: 18px 18px 12px; }
        }
    </style>

    <main>
        <div class="wrap ct-wrap">
            <div class="page-head reveal">
                <div class="crumb"><a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a> / {{ __('site.storefront.contact.title') }}</div>
                <h1>{{ $heading }}</h1>
                @if ($intro !== '')<p>{{ $intro }}</p>@endif
            </div>

            <div class="contact {{ $hasSide && $contact['show_form'] ? '' : 'solo' }}">
                @if ($theme->on('grain_rings'))
                    <div class="ring c1" aria-hidden="true"></div>
                    <div class="ring c2" aria-hidden="true"></div>
                @endif

                @if ($hasSide)
                    <aside class="ct-side reveal">
                        @if ($contact['has_details'])
                            <div class="ct-panel">
                                @if ($theme->on('ruler'))<div class="rule-ticks" aria-hidden="true"></div>@endif
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
                            <div class="ct-panel ct-map">
                                <h3>{{ __('site.storefront.contact.map_label') }}</h3>
                                {{-- Store::contactPage() has already rejected anything that is
                                     not a bare https <iframe>, so this is safe to print raw. --}}
                                <div class="frame">{!! $contact['map_embed'] !!}</div>
                            </div>
                        @endif
                    </aside>
                @endif

                @if ($contact['show_form'])
                    <div class="ct-main reveal">
                        @if (session('contact.sent'))
                            <div class="ct-panel ct-sent" role="status">
                                <span class="mark" aria-hidden="true">✓</span>
                                <div>
                                    <h3>{{ __('site.storefront.contact.sent_title') }}</h3>
                                    <p>{{ __('site.storefront.contact.sent_body') }}</p>
                                </div>
                            </div>
                        @endif

                        <form method="post" action="/contact" class="ct-panel ct-form" data-ct-form>
                            @csrf
                            @if ($theme->on('ruler'))<div class="rule-ticks" aria-hidden="true"></div>@endif
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
