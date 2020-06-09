<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Movie;
use Faker\Generator as Faker;

$factory->define(Movie::class, function (Faker $faker) {
    return [
        'imdb_id' => 'tt0'.$faker->numberBetween(1,9999),
        'image_url' => 'https://'.$faker->word,
        'image_url_small' => 'https://'.$faker->word,
        'name' => $faker->name,
        'genre' => 'action',
        'rank' => 1000,
        'rating' => '6.'.$faker->numberBetween(0,9),
        'year' => $faker->numberBetween(1940,2020),
        'language' => 'english',
    ];
});
