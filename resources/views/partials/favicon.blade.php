{{--
    Browser tab icon. Browsers auto-detect /favicon.ico at the document
    root, but declaring it explicitly here gives us:
      - cache-busting via asset() (versioned URL when an asset hash is in
        play), so users don't get stuck with the old icon;
      - fewer surprise 404s in dev tools when a browser tries multiple
        paths before settling;
      - one place to add per-format <link>s later (SVG / apple-touch /
        manifest) without touching every layout.

    Drop the source file at public/favicon.ico. To swap formats later
    (e.g. add an SVG), append the new <link> here only.
--}}
<link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
