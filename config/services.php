<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    // Cloudflare Turnstile — bot protection on the marketing contact form.
    // The site key is public by design (it ships in the page HTML); the widget
    // only activates when TURNSTILE_SECRET is set, so keyless environments
    // keep the plain form (honeypot + rate limits still apply).
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY', '0x4AAAAAAD7g5L9AuqpaHNuo'),
        'secret' => env('TURNSTILE_SECRET'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        // Stripe Connect webhook signing secret — DIFFERENT from
        // Cashier's STRIPE_WEBHOOK_SECRET (which is for platform-level
        // subscription events). Stripe issues one per endpoint.
        'connect_webhook_secret' => env('STRIPE_CONNECT_WEBHOOK_SECRET'),
    ],

];
