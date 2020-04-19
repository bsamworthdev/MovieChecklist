<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Movie;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

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

    static function updateSavedImages() {

        $movies =  Movie::all();
        foreach($movies as $movie){
            // $cached_image = Cache::get($movie->imdb_id);
            
            // if ($cached_image) {
            //     $movie->image_url = $cached_image;
            // } else {
                //$movie->image = base64_encode(file_get_contents($movie->image_url));
                // Cache::put($movie->imdb_id, $movie->image, 3600);
            // }
            if ($movie->image_url && !$movie->image_url_small){

                //Fetch image from url
                $url = $movie->image_url;
                $contents = file_get_contents($url);
                $name = substr($url, strrpos($url, '/') + 1);

                //Store full-sized image
                Storage::disk('public')->put($name, $contents);
                
                //Shrink image and re-save
                $small_image = Image::make( Storage::disk('public')->get($name) )->resize(229,287)->stream();
                Storage::disk('public')->put($name, $small_image);

                $movie->image_url_small = '/storage/'.$name;
                $movie->save();
            }
        }
    }
}
