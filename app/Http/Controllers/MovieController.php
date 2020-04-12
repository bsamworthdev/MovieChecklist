<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Movie;
use Illuminate\Support\Str;

class MovieController extends Controller
{
    //
    function updateMovies(){

        $movies =  Movie::all();
        foreach($movies as $movie) {
            $url = 'https://v2.sg.media-imdb.com/suggests/'.Str::lower($movie->name[0]).'/'.urlencode($movie->name).'.json';

            $api_response = file_get_contents($url);

            if ($api_response){
                $image_url = explode('"i":["', $api_response)[1];
                $image_url = explode('"', $image_url)[0];
                $movie->image_url = $image_url;
                $movie->save();
            }
        }

        //

       // return $response;
    }
}
