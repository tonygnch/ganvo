@php $title = __('site.onboarding.customize.title'); @endphp
@extends('onboarding.layout')

@section('content')
    <style>
        .cz-layout {
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            gap: 1.75rem;
            max-width: 1100px;
            width: 100%;
        }
        .cz-card {
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 1.25rem;
            box-shadow: 0 30px 60px -30px rgba(0,0,0,.08);
            padding: 2.25rem;
            align-self: start;
        }
        .cz-color-row {
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: .75rem;
            align-items: center;
        }
        .cz-color-row input[type="color"] {
            width: 56px;
            height: 44px;
            border: 1px solid var(--hair);
            border-radius: .5rem;
            background: var(--surface);
            cursor: pointer;
            padding: 4px;
        }
        .cz-color-row input.input.hex {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .cz-logo-current {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: .75rem;
            padding: .75rem;
            border: 1px dashed var(--hair);
            border-radius: .625rem;
            background: var(--muted);
        }
        .cz-logo-current img {
            max-height: 48px;
            max-width: 120px;
            border-radius: .375rem;
        }
        .cz-logo-current .help { margin: 0; }
        .cz-preview {
            position: sticky;
            top: 1rem;
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 1.25rem;
            box-shadow: 0 30px 60px -30px rgba(0,0,0,.08);
            overflow: hidden;
            padding: 1rem;
        }
        .cz-preview-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .75rem;
        }
        .cz-preview-label .lead-small {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-muted);
        }
        .cz-preview-label .theme-pill {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--text);
            padding: .25rem .625rem;
            border-radius: 9999px;
            background: var(--muted);
        }
        .cz-iframe-wrap {
            width: 100%;
            aspect-ratio: 4 / 3;
            background: var(--muted);
            border-radius: .75rem;
            overflow: hidden;
            position: relative;
        }
        .cz-iframe-wrap iframe {
            width: 1200px;
            height: 900px;
            border: 0;
            transform: scale(0.42);
            transform-origin: 0 0;
            pointer-events: none;
        }

        @media (max-width: 980px) {
            .cz-layout { grid-template-columns: 1fr; }
            .cz-preview { position: static; }
        }
    </style>

    <div class="cz-layout">
        <div class="cz-card">
            <p class="panel-eyebrow">{{ __('site.onboarding.customize.eyebrow') }}</p>
            <h1>{{ __('site.onboarding.customize.title') }}</h1>
            <p class="lead">{{ __('site.onboarding.customize.lead') }}</p>

            @if ($errors->any())
                <div class="errors">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="/onboarding/customize" enctype="multipart/form-data" id="cz-form">
                @csrf

                <div class="field">
                    <label class="lbl" for="primary_color">{{ __('site.onboarding.customize.primary') }}</label>
                    <div class="cz-color-row">
                        <input type="color" name="primary_color" id="primary_color"
                               value="{{ old('primary_color', $store->primary_color ?: '#10B981') }}"
                               oninput="document.getElementById('primary_color_hex').value = this.value.toUpperCase(); cz.update()">
                        <input class="input hex" type="text" id="primary_color_hex"
                               value="{{ strtoupper(old('primary_color', $store->primary_color ?: '#10B981')) }}"
                               maxlength="7"
                               oninput="cz.syncFromHex('primary_color', this.value)">
                    </div>
                    <p class="help">{{ __('site.onboarding.customize.primary_help') }}</p>
                </div>

                <div class="field">
                    <label class="lbl" for="secondary_color">{{ __('site.onboarding.customize.secondary') }}</label>
                    <div class="cz-color-row">
                        <input type="color" name="secondary_color" id="secondary_color"
                               value="{{ old('secondary_color', $store->secondary_color ?: '#1F2937') }}"
                               oninput="document.getElementById('secondary_color_hex').value = this.value.toUpperCase(); cz.update()">
                        <input class="input hex" type="text" id="secondary_color_hex"
                               value="{{ strtoupper(old('secondary_color', $store->secondary_color ?: '#1F2937')) }}"
                               maxlength="7"
                               oninput="cz.syncFromHex('secondary_color', this.value)">
                    </div>
                    <p class="help">{{ __('site.onboarding.customize.secondary_help') }}</p>
                </div>

                <div class="field">
                    <label class="lbl" for="font_family">{{ __('site.onboarding.customize.font') }}</label>
                    <select class="input" name="font_family" id="font_family" onchange="cz.update()">
                        @foreach ($fonts as $key => $label)
                            <option value="{{ $key }}" @if(old('font_family', $store->font_family) === $key) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="lbl" for="logo">{{ __('site.onboarding.customize.logo') }}</label>
                    @if ($store->logo_path)
                        <div class="cz-logo-current">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($store->logo_path) }}" alt="">
                            <p class="help">{{ __('site.onboarding.customize.logo_current') }}</p>
                        </div>
                    @endif
                    <input class="input" type="file" name="logo" id="logo" accept="image/*">
                    <p class="help">{{ __('site.onboarding.customize.logo_help') }}</p>
                </div>

                <div class="actions">
                    <a href="/onboarding/theme" class="btn btn-ghost">← {{ __('site.onboarding.customize.back') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('site.onboarding.customize.cta') }} →</button>
                </div>
            </form>
        </div>

        <div class="cz-preview">
            <div class="cz-preview-label">
                <span class="lead-small">{{ __('site.onboarding.customize.preview') }}</span>
                <span class="theme-pill">{{ ucfirst($store->theme) }}</span>
            </div>
            <div class="cz-iframe-wrap">
                <iframe id="cz-preview-frame"
                        src="/onboarding/theme/preview/{{ $store->theme }}"
                        loading="lazy"
                        title="Live preview"></iframe>
            </div>
        </div>
    </div>

    <script>
        const cz = {
            theme: @json($store->theme),
            timer: null,
            debounce(fn, ms) {
                clearTimeout(this.timer);
                this.timer = setTimeout(fn, ms);
            },
            update() {
                // Debounce so dragging the color picker doesn't hammer the iframe.
                this.debounce(() => {
                    const p = document.getElementById('primary_color').value.replace('#', '');
                    const s = document.getElementById('secondary_color').value.replace('#', '');
                    const f = document.getElementById('font_family').value;
                    const url = `/onboarding/theme/preview/${this.theme}?primary=${p}&secondary=${s}&font=${encodeURIComponent(f)}`;
                    document.getElementById('cz-preview-frame').src = url;
                }, 250);
            },
            syncFromHex(targetId, hex) {
                hex = hex.trim();
                if (!hex.startsWith('#')) hex = '#' + hex;
                if (/^#[0-9a-fA-F]{6}$/.test(hex)) {
                    document.getElementById(targetId).value = hex;
                    this.update();
                }
            },
        };
    </script>
@endsection
