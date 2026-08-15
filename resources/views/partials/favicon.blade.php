{{--
    THE TAB ICON BELONGS TO THE SHOP, NOT TO US.

    This used to emit /favicon.ico unconditionally, and that file is the Ganvo
    "G". So every client storefront — on the client's own domain, to the
    client's own customers — flew their supplier's logo in the browser tab.
    White-label means the platform's mark stops at the platform's own pages.

    Order of preference:
      1. the theme's `favicon` image slot, which is already "what the merchant
         uploaded, else what the theme ships"
      2. the merchant's uploaded logo, for a theme that declares no such slot
      3. ours — the marketing site, and any store with neither

    The dark-mode pair is emitted only when the theme offers one. `media` on an
    icon link is honoured by Chrome and Firefox; a browser that ignores it just
    takes the first, which is the light-ground mark and the safer default.
--}}
@php
    // Both are optional: this partial is included by the marketing layouts too,
    // where neither exists.
    $gvTheme = $theme ?? null;
    $gvStore = $store ?? null;

    $gvIcon = $gvTheme ? $gvTheme->image('favicon') : null;
    $gvIconDark = $gvTheme ? $gvTheme->image('favicon_dark') : null;

    if (! $gvIcon && $gvStore && $gvStore->logo_path) {
        $gvIcon = \Illuminate\Support\Facades\Storage::disk('public')->url($gvStore->logo_path);
    }
@endphp
@if ($gvIcon)
    @if ($gvIconDark)
        <link rel="icon" href="{{ $gvIcon }}" media="(prefers-color-scheme: light)">
        <link rel="icon" href="{{ $gvIconDark }}" media="(prefers-color-scheme: dark)">
    @else
        <link rel="icon" href="{{ $gvIcon }}">
    @endif
    <link rel="apple-touch-icon" href="{{ $gvIcon }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
@endif
