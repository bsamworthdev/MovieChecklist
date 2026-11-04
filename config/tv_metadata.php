<?php

return [
    // Metadata sources only: IMDb sometimes lists seasons/dubs as separate titles.
    // These do not map streaming availability or replace the stored IMDb identity.
    'sources' => [
        'tt14986406' => 30984, // Thousand-Year Blood War: Bleach.
        'tt0214341' => 12971, // Dragon Ball Z (alternate IMDb release).
        'tt0096548' => 7246,  // Blackadder Goes Forth: Blackadder season 4.
        'tt38268282' => 45790, // Steel Ball Run: JoJo's Bizarre Adventure.
        'tt0088484' => 7246,  // Blackadder II: Blackadder season 2.
        'tt0092324' => 7246,  // Blackadder the Third: Blackadder season 3.
        'tt0280249' => 12609, // Dragon Ball (alternate IMDb release).
        'tt4093826' => 1920,  // Twin Peaks: The Return: Twin Peaks season 3.
    ],
    'corrections' => [
        // TMDB's IMDb lookup currently returns the German repackage "Mein Name ist Hase".
        // Original 1960 show: https://interviews.televisionacademy.com/shows/bugs-bunny-show-the
        'tt0053488' => ['language' => 'en', 'genre' => 'Animation, Comedy'],
    ],
];
