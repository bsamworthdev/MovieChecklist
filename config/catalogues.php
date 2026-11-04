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
            'tv_provider_names' => ['Now TV', 'NOW', 'Now TV Entertainment', 'NOW Entertainment'],
        ],
        'apple_tv' => [
            'label' => 'Apple TV', 'table' => 'apple_tv', 'column' => 'on_apple_tv', 'icon' => 'apple_tv.png',
            'provider_names' => ['Apple TV', 'Apple TV Plus', 'Apple TV+'], 'offer_types' => ['flatrate'],
        ],
        'paramount_plus' => [
            'label' => 'Paramount+', 'table' => 'paramount_plus', 'column' => 'on_paramount_plus', 'icon' => 'paramount_plus.png',
            'provider_names' => ['Paramount Plus', 'Paramount+', 'Paramount Plus Premium', 'Paramount Plus Basic with Ads'], 'offer_types' => ['flatrate'],
        ],
        'bbc_iplayer' => [
            'label' => 'BBC iPlayer', 'table' => 'bbc_iplayer', 'column' => 'on_bbc_iplayer', 'icon' => 'bbc_iplayer.png',
            'provider_names' => ['BBC iPlayer'], 'offer_types' => ['flatrate', 'free', 'ads'],
        ],
        'itvx' => [
            'label' => 'ITVX', 'table' => 'itvx', 'column' => 'on_itvx', 'icon' => 'itvx.png',
            'provider_names' => ['ITVX', 'ITVX Premium'], 'offer_types' => ['flatrate', 'free', 'ads'],
        ],
        'channel4' => [
            'label' => 'Channel 4', 'table' => 'channel4', 'column' => 'on_channel4', 'icon' => 'channel4.png',
            'provider_names' => ['Channel 4', 'Channel 4 Plus'], 'offer_types' => ['flatrate', 'free', 'ads'],
        ],
    ],
];
