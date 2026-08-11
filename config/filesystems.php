<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            /*
             | ROOT-RELATIVE, not APP_URL-based, because this is a multi-tenant
             | app and APP_URL names exactly ONE host.
             |
             | Every storefront runs on its own hostname — slug.ganvo.bg today,
             | the merchant's own domain tomorrow — but Storage::url() was
             | pinning every product photo, logo and gallery image to
             | https://ganvo.bg/storage/... So a store served from
             | sankevi.ganvo.bg was asking the CENTRAL domain for its pictures,
             | and on production that path is blocked there: every image on
             | every storefront 404'd, while the identical file served fine
             | from the store's own host.
             |
             | A leading slash resolves against whatever host the page is on,
             | which is right in all three cases — platform subdomain, custom
             | domain, and local dev. Nothing needs the absolute form: no mail
             | template uses this disk, and the og:image tags build their URL
             | with url() from public_path, not from here.
             |
             | Overridable for the day assets move to a CDN or S3.
             */
            'url' => env('FILESYSTEM_PUBLIC_URL', '/storage'),
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
