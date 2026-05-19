<?php

return [
    'central_domain' => env('CENTRAL_DOMAIN', 'ganvo.lvh.me'),

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
