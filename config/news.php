<?php 
return [
    'days_to_keep' => env('NEWS_DAYS_TO_KEEP', 30),
    'fetch_interval' => env('NEWS_FETCH_INTERVAL', 30), // minutes
    'articles_per_fetch' => env('NEWS_ARTICLES_PER_FETCH', 50),
    'enable_live_search' => env('NEWS_ENABLE_LIVE_SEARCH', true),
    'cache_ttl' => env('NEWS_CACHE_TTL', 600), // seconds
    
    'rate_limits' => [
        'newsapi' => 500, // requests per day
        'guardian' => 5000,
        'nyt' => 500,
    ],
];