<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active theme
    |--------------------------------------------------------------------------
    |
    | Slug of the theme the public website renders with. Must match a directory
    | name under the themes path that contains a valid theme.json manifest.
    |
    */
    'active' => env('THEME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Fallback theme
    |--------------------------------------------------------------------------
    |
    | Views the active theme does not define resolve here before falling through
    | to the application's own resources/views directory.
    |
    */
    'fallback' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Themes path
    |--------------------------------------------------------------------------
    |
    | Each theme is a self-contained directory holding a theme.json manifest and
    | a views/ directory mirroring the application's public view structure.
    |
    */
    'path' => resource_path('themes'),

    /*
    |--------------------------------------------------------------------------
    | Settings cache
    |--------------------------------------------------------------------------
    |
    | Manifests are read from disk on boot. Cache them outside local development
    | so every public request does not hit the filesystem.
    |
    */
    'cache' => env('THEME_CACHE', true),
];
