@props([
    'size'     => 'md',  // sm | md | lg | xl — drives rendered height in px
    'lightSrc' => null,  // image shown on light theme (dark ink on light bg)
    'darkSrc'  => null,  // image shown on dark theme (light ink on dark bg)
    'alt'      => 'Ganvo',
])
@php
    /*
     | Theme-aware Ganvo brand lockup.
     |
     | When both light and dark variants are on disk, BOTH <img>s are
     | rendered and stacked into the same cell via CSS Grid
     | (`grid-template-areas: 'stack'`). Opacity — driven by a
     | [data-theme] selector on :root — controls which one is visible,
     | with a 300ms ease transition that lines up with the body's
     | background/color transition so the lockup crossfades together
     | with the rest of the page.
     |
     | Falls back to whichever single variant is present, and to a text
     | "Ganvo" wordmark when neither file is on disk.
     */
    $heights = ['sm' => 28, 'md' => 40, 'lg' => 64, 'xl' => 96];
    $h = $heights[$size] ?? $heights['md'];

    $lightSrc ??= '/images/brand/logo-full-black.png';
    $darkSrc  ??= '/images/brand/logo-full-white.png';

    $existsOnDisk = function (?string $src): bool {
        if (! is_string($src)) return false;
        // Remote URLs are assumed present — only local /-rooted public files are checked.
        if (! str_starts_with($src, '/') || str_starts_with($src, '//')) return true;
        return file_exists(public_path(ltrim($src, '/')));
    };
    $lightOk = $existsOnDisk($lightSrc);
    $darkOk  = $existsOnDisk($darkSrc);

    // Inline styles for the stacked imgs. Note we DON'T set `display:
    // inline-block` here — that would override the visibility toggle the
    // page-level CSS rule applies. The Grid layout on the wrapper handles
    // positioning, and `opacity` (not `display`) drives the swap so we get
    // a smooth crossfade instead of a pop.
    $imgStyle = "grid-area: stack; height: {$h}px; width: auto; transition: opacity .3s ease;";
@endphp

@if ($lightOk && $darkOk)
    {{-- Both variants present — stack them and crossfade between them. --}}
    <span
        class="brand-lockup-stack"
        style="display: inline-grid; grid-template-areas: 'stack'; line-height: 0; vertical-align: middle;"
        {{ $attributes }}
    >
        <img
            src="{{ $lightSrc }}"
            alt="{{ $alt }}"
            class="brand-lockup-img brand-lockup-img-light"
            style="{{ $imgStyle }}"
        >
        <img
            src="{{ $darkSrc }}"
            alt=""
            aria-hidden="true"
            class="brand-lockup-img brand-lockup-img-dark"
            style="{{ $imgStyle }}"
        >
    </span>
@elseif ($lightOk)
    <img src="{{ $lightSrc }}" alt="{{ $alt }}"
         style="height: {{ $h }}px; width: auto; display: inline-block; vertical-align: middle;"
         {{ $attributes }}>
@elseif ($darkOk)
    <img src="{{ $darkSrc }}" alt="{{ $alt }}"
         style="height: {{ $h }}px; width: auto; display: inline-block; vertical-align: middle;"
         {{ $attributes }}>
@else
    {{-- Neither image is on disk — text fallback so the page doesn't break
         on a fresh clone that hasn't shipped the assets yet. --}}
    <span
        style="display: inline-flex; align-items: center; font-weight: 800; font-size: {{ (int) round($h * 0.75) }}px; letter-spacing: -0.025em; line-height: 1; color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;"
        {{ $attributes }}
    >Ganvo</span>
@endif
