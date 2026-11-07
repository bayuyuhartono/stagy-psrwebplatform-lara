<?php

return [
    'disk' => env('MEDIA_DISK', 'public'),
    'max_size' => env('MEDIA_MAX_SIZE', '50mb'),
    'allowed_types' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'mp4', 'webm'],

    /*
    |--------------------------------------------------------------------------
    | Responsive variants
    |--------------------------------------------------------------------------
    |
    | Downscaled copies generated on upload so public pages can serve a srcset.
    | Widths wider than the source are skipped rather than upscaled. GIF is
    | excluded deliberately — resizing would drop animation frames.
    |
    */
    'responsive' => [
        'enabled' => env('MEDIA_RESPONSIVE', true),
        'widths' => [320, 640, 1024, 1600],
        'quality' => 82,
    ],
];
