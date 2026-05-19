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
     | Two image files are rendered side-by-side and CSS toggles their
     | visibility based on the [data-theme] attribute on <html>. Pure-CSS
     | switching (no JS) so the right image is in the DOM before paint,
     | avoiding a flash of the wrong logo on theme load.
     |
     | Falls back gracefully when files are missing on disk — only the
     | present version(s) render, or a text "Ganvo" wordmark if neither
     | exists.
     */
    $heights = ['sm' => 28, 'md' => 40, 'lg' => 64, 'xl' => 96];
    $h = $heights[$size] ?? $heights['md'];

    $lightSrc ??= '/images/brand/logo-full-black.png';
    $darkSrc  ??= '/images/brand/logo-full-white.png';

    // Closure so we can check both paths concisely. Remote URLs (starting
    // with //) and non-/-rooted strings are assumed present — we only check
    // local public files.
    $existsOnDisk = function (?string $src): bool {
        if (! is_string($src)) return false;
        if (! str_starts_with($src, '/') || str_starts_with($src, '//')) return true;
        return file_exists(public_path(ltrim($src, '/')));
    };

    $lightOk = $existsOnDisk($lightSrc);
    $darkOk  = $existsOnDisk($darkSrc);

    $imgStyle = "height: {$h}px; width: auto; display: inline-block; vertical-align: middle;";
@endphp

@if ($lightOk && $darkOk)
    {{-- Both present — pure-CSS theme switcher via .brand-lockup-img-* classes.
         The hide rule lives in the page's CSS (see coming-soon.blade.php). --}}
    <img
        src="{{ $lightSrc }}"
        alt="{{ $alt }}"
        class="brand-lockup-img brand-lockup-img-light"
        style="{{ $imgStyle }}"
        {{ $attributes }}
    >
    <img
        src="{{ $darkSrc }}"
        alt=""
        aria-hidden="true"
        class="brand-lockup-img brand-lockup-img-dark"
        style="{{ $imgStyle }}"
        {{ $attributes }}
    >
@elseif ($lightOk)
    <img src="{{ $lightSrc }}" alt="{{ $alt }}" style="{{ $imgStyle }}" {{ $attributes }}>
@elseif ($darkOk)
    <img src="{{ $darkSrc }}" alt="{{ $alt }}" style="{{ $imgStyle }}" {{ $attributes }}>
@else
    {{-- Neither image is on disk. Render a text-only fallback so the page
         doesn't break on a fresh clone that hasn't shipped the assets yet. --}}
    <span
        style="display: inline-flex; align-items: center; font-weight: 800; font-size: {{ (int) round($h * 0.75) }}px; letter-spacing: -0.025em; line-height: 1; color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;"
        {{ $attributes }}
    >Ganvo</span>
@endif
