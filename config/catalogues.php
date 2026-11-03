<?php

return [
    'cache_store' => env('CATALOGUE_CACHE_STORE', 'file'),
    'batch_size' => 10,
    'batch_delay_ms' => 500,
    'services' => [
        'prime' => [
            'label' => 'Prime Video', 'table' => 'amazon', 'column' => 'on_amazon', 'icon' => 'amazon.jpeg',
            'provider_names' => ['Amazon Prime Video', 'Amazon Prime Video with Ads'],
        ],
        'netflix' => [
            'label' => 'Netflix', 'table' => 'netflix', 'column' => 'on_netflix', 'icon' => 'netflix.jpg',
            'provider_names' => ['Netflix', 'Netflix basic with Ads', 'Netflix Standard with Ads'],
        ],
        'disney' => [
            'label' => 'Disney+', 'table' => 'disney_plus', 'column' => 'on_disney_plus', 'icon' => 'disney_plus.jpg',
            'provider_names' => ['Disney Plus', 'Disney+'],
        ],
        'hbo_max' => [
            'label' => 'HBO Max', 'table' => 'hbo_max', 'column' => 'on_hbo_max', 'icon' => 'hbo_max.png',
            'provider_names' => ['HBO Max', 'HBO Max with Ads', 'Max', 'Max with Ads'],
        ],
        'now' => [
            'label' => 'NOW TV', 'table' => 'nowtv', 'column' => 'on_nowtv', 'icon' => 'nowtv.jpg',
            'provider_names' => ['Now TV Cinema', 'NOW Cinema', 'Now TV', 'NOW'],
        ],
    ],
];
