<?php

return [
    'central_domain' => env('CENTRAL_DOMAIN', 'ganvo.lvh.me'),

    /*
    |--------------------------------------------------------------------------
    | Studio owner address
    |--------------------------------------------------------------------------
    |
    | Where "Start a project" inquiries from the marketing homepage are emailed.
    | Falls back to the global mail from-address. In local dev the mailer is
    | `log`, so the notification is written to storage/logs — the inquiry is
    | always persisted regardless of whether mail delivery is configured.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Preview lock (HTTP Basic)
    |--------------------------------------------------------------------------
    |
    | Puts the whole platform behind an HTTP Basic prompt so work in progress
    | can sit on a public domain without being publicly readable — and, via an
    | X-Robots-Tag, without being indexed.
    |
    | The password is stored ONLY as a bcrypt hash. Generate it with
    | `php artisan ganvo:preview-password`, which prompts without echoing, so
    | the plaintext never reaches shell history or a file.
    |
    | The health check and both Stripe webhooks stay open — see PreviewGate.
    | Turn this OFF at launch.
    |
    */
    'preview' => [
        'enabled' => (bool) env('PREVIEW_LOCK', false),
        'user' => env('PREVIEW_USER', ''),
        'password_hash' => env('PREVIEW_PASSWORD_HASH', ''),
    ],

    'owner_email' => env('GANVO_OWNER_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@ganvo.bg')),

    /*
    |--------------------------------------------------------------------------
    | Coming-soon mode
    |--------------------------------------------------------------------------
    |
    | When enabled, the public-facing marketing homepage at `/` renders a
    | "coming soon" splash instead of the normal landing page.
    |
    | Intentionally scoped to the marketing homepage only — onboarding,
    | admin panels, and live tenant storefronts keep working so the
    | platform owner can still operate while the public sees the splash.
    |
    | bypass_token: append `?preview=<token>` to `/` to skip the splash
    | even when enabled. Useful for sharing a preview link with stakeholders
    | without logging into anything. Leave empty to disable preview bypass.
    |
    */
    'coming_soon' => [
        'enabled' => filter_var(env('COMING_SOON_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'bypass_token' => env('COMING_SOON_BYPASS_TOKEN'),
    ],
];
