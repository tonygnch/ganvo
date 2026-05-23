{{--
    Open Graph + Twitter Card meta tags for social link previews.

    Usage:
        @include('partials.social-meta', [
            'title'       => 'Page title',           // required
            'description' => 'One-line summary',     // required
            'image'       => '/images/brand/og-image.png',  // optional, defaults below
        ])

    Drop the share image at public/images/brand/og-image.png (1200×630, PNG).
    Falls back to the brand wordmark if og-image.png isn't on disk yet, so
    deploys without the file don't ship a broken og:image URL.
--}}
@php
    $ogTitle = $title ?? config('app.name', 'Ganvo');
    $ogDescription = $description ?? '';
    $ogImagePath = $image ?? '/images/brand/og-image.png';
    // Fall back to the brand wordmark if no dedicated OG image exists yet.
    if (! file_exists(public_path(ltrim($ogImagePath, '/')))) {
        $ogImagePath = '/images/brand/logo-full-black.png';
    }
    $ogImageUrl = url($ogImagePath);
    $ogUrl = url()->current();
@endphp

{{-- Open Graph (Facebook, LinkedIn, Slack, Discord, iMessage) --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="Ganvo">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $ogUrl }}">
<meta property="og:image" content="{{ $ogImageUrl }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
<meta name="twitter:image" content="{{ $ogImageUrl }}">
