@php
    $title = __('site.storefront.contact.title');

    // Blank heading/intro mean the merchant never overrode them — fall back to
    // the platform copy rather than rendering an empty page head.
    $heading = $contact['heading'] ?: __('site.storefront.contact.heading');
    $intro = $contact['intro'] ?: __('site.storefront.contact.intro');

    $hasAside = $contact['has_details'] || $contact['map_embed'];
    $twoCol = $contact['show_form'] && $hasAside;
    $sent = (bool) session('contact.sent');
@endphp
@extends("themes.{$theme}.layout")

@section('content')
    <style>
        .contact-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 3rem 1.5rem 4rem;
        }
        .contact-head { margin-bottom: 2rem; max-width: 60ch; }
        .contact-head h1 {
            margin: 0 0 .5rem;
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .contact-head p {
            margin: 0;
            color: var(--text-muted, #57534e);
            font-size: 0.9375rem;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 2rem;
            align-items: start;
        }
        .contact-grid.single {
            grid-template-columns: 1fr;
            max-width: 680px;
        }

        .card-block {
            background: var(--surface, white);
            border: 1px solid var(--border, #e7e5e4);
            border-radius: 1rem;
            padding: 1.75rem;
        }
        .card-block + .card-block { margin-top: 1.25rem; }
        .card-block h2 {
            margin: 0 0 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--text-muted, #57534e);
        }

        /* -------- Details -------- */
        .details-list { margin: 0; }
        .detail { padding: .75rem 0; border-bottom: 1px solid var(--border, #e7e5e4); }
        .detail:first-of-type { padding-top: 0; }
        .detail:last-of-type { border-bottom: 0; padding-bottom: 0; }
        .detail dt {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--text-soft, #a8a29e);
            margin-bottom: .25rem;
        }
        .detail dd {
            margin: 0;
            font-size: 0.9375rem;
            color: var(--text, #1c1917);
            white-space: pre-line;
        }
        .detail dd a { color: var(--text, #1c1917); text-decoration: none; }
        .detail dd a:hover { color: var(--primary); }

        /* The merchant pastes a raw <iframe> with fixed width/height
           attributes, so the wrapper — not the element — sets the size. */
        .map-frame {
            aspect-ratio: 4 / 3;
            border-radius: .75rem;
            overflow: hidden;
            border: 1px solid var(--border, #e7e5e4);
            background: var(--muted, #f5f5f4);
        }
        .map-frame iframe {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
        }

        /* -------- Form -------- */
        .field { margin-bottom: 1rem; }
        .fields-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        label.field-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text, #1c1917);
            margin-bottom: .375rem;
            letter-spacing: 0.01em;
        }
        /* the red is mixed toward the theme's own text colour so it stays legible
           on dark themes (tech, wick, Timber's night mode) as well as light ones */
        /* error ink derived from the active theme: 62% crimson blended with the
           theme's text colour keeps ~4.5:1 on both light and dark surfaces */
        main { --gv-error: color-mix(in srgb, #e5484d 62%, var(--text, var(--txt, #1c1c1c))); }
        label.field-label .req { color: var(--gv-error); }
        label.field-label small { color: var(--text-soft, #a8a29e); font-weight: 400; }
        .field-input {
            width: 100%;
            padding: .75rem .875rem;
            border: 1px solid var(--border, #e7e5e4);
            border-radius: .625rem;
            background: var(--surface, white);
            color: var(--text, #1c1917);
            font: inherit;
            font-size: 0.9375rem;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .field-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 18%, transparent);
        }
        .field-input::placeholder { color: var(--text-soft, #a8a29e); }
        textarea.field-input { min-height: 160px; resize: vertical; }
        .field-input[aria-invalid="true"] { border-color: var(--gv-error); }
        .field-error {
            margin: .375rem 0 0;
            font-size: 0.8125rem;
            color: var(--gv-error);
        }

        /* Honeypot: off-screen rather than display:none, because bots skip
           fields the browser would never paint. */
        .hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }

        .send-btn {
            background: var(--primary);
            color: white;
            border: 0;
            padding: 1rem 1.75rem;
            border-radius: .75rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color .2s ease, transform .12s ease, box-shadow .15s ease;
        }
        .send-btn:hover {
            background: var(--primary-strong, var(--primary));
            transform: translateY(-1px);
            box-shadow: 0 12px 24px -6px color-mix(in srgb, var(--primary) 50%, transparent);
        }
        .send-btn[disabled] { opacity: .6; cursor: default; transform: none; box-shadow: none; }

        /* -------- Sent -------- */
        .sent {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-radius: 1rem;
            padding: 1.75rem;
        }
        .sent h2 { margin: 0 0 .375rem; font-size: 1.125rem; font-weight: 700; }
        .sent p { margin: 0; font-size: 0.9375rem; }

        @media (max-width: 880px) {
            .contact-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .fields-row { grid-template-columns: 1fr; }
        }
    </style>

    <div class="contact-page">
        <div class="contact-head">
            <h1>{{ $heading }}</h1>
            <p>{{ $intro }}</p>
        </div>

        <div class="contact-grid {{ $twoCol ? '' : 'single' }}">
            @if ($contact['show_form'])
                <div>
                    @if ($sent)
                        <div class="sent" role="status">
                            <h2>{{ __('site.storefront.contact.sent_title') }}</h2>
                            <p>{{ __('site.storefront.contact.sent_body') }}</p>
                        </div>
                    @else
                        <form method="post" action="/contact" class="card-block" data-contact-form>
                            @csrf
                            <h2>{{ __('site.storefront.contact.form_h') }}</h2>

                            <div class="hp" aria-hidden="true">
                                <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                            </div>

                            <div class="fields-row">
                                <div class="field">
                                    <label class="field-label" for="contact-name">
                                        {{ __('site.storefront.contact.field_name') }} <span class="req" aria-hidden="true">*</span>
                                    </label>
                                    <input class="field-input" type="text" name="name" id="contact-name" maxlength="120" required
                                           autocomplete="name"
                                           value="{{ old('name', $customer?->name) }}"
                                           @error('name') aria-invalid="true" aria-describedby="contact-name-error" @enderror>
                                    @error('name')
                                        <p class="field-error" id="contact-name-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="field">
                                    <label class="field-label" for="contact-email">
                                        {{ __('site.storefront.contact.field_email') }} <span class="req" aria-hidden="true">*</span>
                                    </label>
                                    <input class="field-input" type="email" name="email" id="contact-email" maxlength="255" required
                                           autocomplete="email" placeholder="you@example.com"
                                           value="{{ old('email', $customer?->email) }}"
                                           @error('email') aria-invalid="true" aria-describedby="contact-email-error" @enderror>
                                    @error('email')
                                        <p class="field-error" id="contact-email-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="fields-row">
                                <div class="field">
                                    <label class="field-label" for="contact-phone">
                                        {{ __('site.storefront.contact.field_phone') }}
                                        <small>({{ __('site.common.optional') }})</small>
                                    </label>
                                    <input class="field-input" type="tel" name="phone" id="contact-phone" maxlength="40"
                                           autocomplete="tel"
                                           value="{{ old('phone', $customer?->phone) }}"
                                           @error('phone') aria-invalid="true" aria-describedby="contact-phone-error" @enderror>
                                    @error('phone')
                                        <p class="field-error" id="contact-phone-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="field">
                                    <label class="field-label" for="contact-subject">{{ __('site.storefront.contact.field_subject') }}</label>
                                    @php $selectedSubject = old('subject', 'general'); @endphp
                                    <select class="field-input" name="subject" id="contact-subject"
                                            @error('subject') aria-invalid="true" aria-describedby="contact-subject-error" @enderror>
                                        @foreach (\App\Models\StoreMessage::SUBJECTS as $subject)
                                            <option value="{{ $subject }}" @selected($selectedSubject === $subject)>
                                                {{ __('site.storefront.contact.subject_' . $subject) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('subject')
                                        <p class="field-error" id="contact-subject-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="field">
                                <label class="field-label" for="contact-message">
                                    {{ __('site.storefront.contact.field_message') }} <span class="req" aria-hidden="true">*</span>
                                </label>
                                <textarea class="field-input" name="message" id="contact-message" minlength="10" maxlength="4000" required
                                          @error('message') aria-invalid="true" aria-describedby="contact-message-error" @enderror>{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="field-error" id="contact-message-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="send-btn">{{ __('site.storefront.contact.send') }}</button>
                        </form>
                    @endif
                </div>
            @endif

            @if ($hasAside)
                <aside>
                    @if ($contact['has_details'])
                        <div class="card-block">
                            <h2>{{ __('site.storefront.contact.details_h') }}</h2>
                            <dl class="details-list">
                                @if ($contact['address'])
                                    <div class="detail">
                                        <dt>{{ __('site.storefront.contact.address_label') }}</dt>
                                        <dd>{{ $contact['address'] }}</dd>
                                    </div>
                                @endif
                                @if ($contact['phone'])
                                    <div class="detail">
                                        <dt>{{ __('site.storefront.contact.phone_label') }}</dt>
                                        {{-- Spaces and punctuation break tel: on some dialers. --}}
                                        <dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a></dd>
                                    </div>
                                @endif
                                @if ($contact['email'])
                                    <div class="detail">
                                        <dt>{{ __('site.storefront.contact.email_label') }}</dt>
                                        <dd><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></dd>
                                    </div>
                                @endif
                                @if ($contact['hours'])
                                    <div class="detail">
                                        <dt>{{ __('site.storefront.contact.hours_label') }}</dt>
                                        <dd>{{ $contact['hours'] }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif

                    @if ($contact['map_embed'])
                        <div class="card-block">
                            <h2>{{ __('site.storefront.contact.map_label') }}</h2>
                            {{-- Validated in Store::contactPage() to be a bare https iframe. --}}
                            <div class="map-frame">{!! $contact['map_embed'] !!}</div>
                        </div>
                    @endif
                </aside>
            @endif
        </div>
    </div>

    @if ($contact['show_form'] && ! $sent)
        <script>
            // Plain form post, so a second impatient click files the enquiry
            // twice. Deferred a tick so the submission is already on its way.
            document.querySelector('[data-contact-form]')?.addEventListener('submit', (e) => {
                const btn = e.currentTarget.querySelector('button[type="submit"]');
                setTimeout(() => {
                    btn.disabled = true;
                    btn.textContent = @json(__('site.storefront.contact.sending'));
                }, 0);
            });
        </script>
    @endif
@endsection
