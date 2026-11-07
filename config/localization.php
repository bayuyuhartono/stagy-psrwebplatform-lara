<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default content locale
    |--------------------------------------------------------------------------
    |
    | The locale served at the root of the public website ("/harga"). Every
    | other supported locale is served under its own path prefix ("/en/...").
    |
    */
    'default' => env('APP_LOCALE', 'id'),

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | Keyed by locale code. `label` is the short code shown in the language
    | switcher and `name` is the human-readable name used in the CMS.
    |
    */
    'supported' => [
        'id' => ['label' => 'ID', 'name' => 'Bahasa Indonesia'],
        'en' => ['label' => 'EN', 'name' => 'English'],
    ],
];
