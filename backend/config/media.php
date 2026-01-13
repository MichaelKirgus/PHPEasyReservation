<?php

return [
    'allowed_extensions' => ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'],

    // Map logical categories to storage paths relative to the public disk
    'categories' => [
        'background' => [
            'path' => 'media/backgrounds',
        ],
        'top' => [
            'path' => 'media/tops',
        ],
        'favicon' => [
            'path' => 'media/favicons',
        ],
        'loading' => [
            'path' => 'media/loading',
        ],
    ],

    // Map setting names to categories for validation
    'setting_category_map' => [
        'reservation_page_background_image' => 'background',
        'reservation_top_image' => 'top',
        'reservation_page_favicon' => 'favicon',
        'reservation_loading_image' => 'loading',
    ],
];
