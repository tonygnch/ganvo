@props([
    'size' => 'md',           // sm | md | lg | xl — drives the rendered height in px
    'src'  => null,           // override the asset path; defaults below
    'alt'  => 'Ganvo',
])
@php
    /*
     | Brand lockup — uses the actual logo artwork file shipped in
     | public/images/brand/ rather than a recreated SVG. Default path
     | is logo-lockup.png; the merchant can override via the `src` prop
     | (e.g. for a light-on-dark version).
     |
     | Falls back to a text-only "Ganvo" wordmark when the file isn't
     | present on disk, so the layout doesn't break on a fresh clone
     | that hasn't shipped the binary asset yet.
     */
    $heights = ['sm' => 28, 'md' => 40, 'lg' => 64, 'xl' => 96];
    $h = $heights[$size] ?? $heights['md'];

    $src ??= '/images/brand/logo-lockup.png';

    // Resolve to a filesystem path for the existence check. Only run for
    // /-rooted paths (i.e. local public files) — leave remote URLs alone.
    $exists = true;
    if (is_string($src) && str_starts_with($src, '/') && ! str_starts_with($src, '//')) {
        $exists = file_exists(public_path(ltrim($src, '/')));
    }
@endphp

@if ($exists)
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        style="height: {{ $h }}px; width: auto; display: inline-block;"
        {{ $attributes }}
    >
@else
    {{-- File hasn't been shipped yet. Render a sensible text-only
         fallback so the page still reads as Ganvo. The merchant should
         drop the artwork at public{{ $src }} to replace this. --}}
    <span
        style="display: inline-flex; align-items: center; gap: 8px; font-weight: 800; font-size: {{ (int) round($h * 0.75) }}px; letter-spacing: -0.025em; line-height: 1; color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;"
        {{ $attributes }}
    >Ganvo</span>
@endif
