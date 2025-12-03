<?php

return [
    'name' => 'Forums',

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for forum data
    |
    */
    'cache' => [
        'enabled' => env('FORUM_CACHE_ENABLED', true),
        'ttl' => env('FORUM_CACHE_TTL', 300), // 5 minutes
        'thread_list_ttl' => env('FORUM_THREAD_LIST_CACHE_TTL', 300),
        'statistics_ttl' => env('FORUM_STATISTICS_CACHE_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reply Configuration
    |--------------------------------------------------------------------------
    */
    'reply' => [
        'max_depth' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    */
    'search' => [
        'min_query_length' => 3,
    ],
];
