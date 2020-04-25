<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Movie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class MovieController extends Controller
{
    //
    function updateMovies(){

        for($page=0;$page<10;$page++){
            $url = 'https://www.imdb.com/search/title/?groups=top_1000&view=simple&sort=user_rating,desc&count=100&start='.(($page * 100) + 1).'&ref_=adv_nxt';

            $dom = new \DOMDocument;
            @$dom->loadHTMLFile($url);
            $dom->preserveWhiteSpace = false;
            
            $xpath = new \DOMXpath($dom);

            //Create node lists
            $nl_name = $xpath->query('//div[contains(@class, "lister-item-content")]//span[contains(@class, "lister-item-header")]//a');
            $nl_rank = $xpath->query('//div[contains(@class, "lister-item-content")]//*[contains(@class, "text-primary")]');
            $nl_rating = $xpath->query('//div[contains(@class, "lister-item-content")]//*[contains(@class, "col-imdb-rating")]//strong');
            // $nl_image_url = $xpath->query('//div[contains(@class, "lister-item-image")]//img/@src');
            $nl_imdb_id = $xpath->query('//div[contains(@class, "lister-item-image")]//img/@data-tconst');
            $nl_year = $xpath->query('//div[contains(@class, "lister-item-content")]//*[contains(@class, "lister-item-year")]');
            $nl_genre = $xpath->query('//div[contains(@class, "lister-item-content")]//*[contains(@class, "genre")]');

            $nl_rating = $xpath->query('//div[contains(@class, "lister-item-content")]//*[contains(@class, "col-imdb-rating")]//strong');
            for($i=0;$i<count($nl_name);$i++){ 
                
                $imdb_id = $nl_imdb_id->item($i)->nodeValue;
                $existing_movies_count = Movie::where('imdb_id','=',$imdb_id)->get()->count();
                if ($existing_movies_count == 0){
                    $movie = new Movie;
                    $movie->name = $nl_name->item($i)->nodeValue;
                    $movie->rank = $nl_rank->item($i)->nodeValue;
                    $movie->rating = $nl_rating->item($i)->nodeValue;
                    // $movie->image_url = $nl_image_url->item($i)->nodeValue;
                    $movie->imdb_id = $imdb_id;
                    $movie->year = str_replace(')','',str_replace('(','',str_replace('I','',$nl_year->item($i)->nodeValue)));
                    $movie->genre = $nl_genre->item($i)->nodeValue;
                    $movie->save();
                }
            }
        }

        return 'updated';
    }

    function updateMovieImages(){
        $movies = Movie::whereNull('image_url')->get();
        foreach($movies as $movie) {
            if (!$movie->image_url){
                $url = 'https://v2.sg.media-imdb.com/suggests/'.Str::lower($movie->name[0]).'/'.urlencode($movie->name).'.json';

                $api_response = file_get_contents($url);

                if ($api_response){
                    $image_url = explode('"i":["', $api_response)[1];
                    $image_url = explode('"', $image_url)[0];
                    $movie->image_url = $image_url;
                    $movie->save();
                }
            }
        }
    }

    function updateSavedMovieImages() {
        $movies = Movie::whereNull('image_url_small')->get();;
        foreach($movies as $movie){
            if ($movie->image_url && !$movie->image_url_small){

                //Fetch image from url
                $url = $movie->image_url;
                $contents = file_get_contents($url);
                $name = substr($url, strrpos($url, '/') + 1);

                //Store full-sized image
                Storage::disk('public')->put($name, $contents);
                
                //Shrink image and re-save
                $small_image = Image::make(Storage::disk('public')->get($name) )->resize(229,287)->stream();
                Storage::disk('public')->put($name, $small_image);

                $movie->image_url_small = '/storage/'.$name;
                $movie->save();
            }
        }
    }

    function updateNetflixStatuses(){
        // $oneWeekAgo = \Carbon\Carbon::today()->subWeek();
        // $movies = Movie::where('updated_at', '<', $oneWeekAgo)->get();
        $movies = Movie::all();
        foreach($movies as $movie) {

            $url = 'https://uk.newonnetflix.info/catalogue/search/'.$movie->name.'#results';

            $dom = new \DOMDocument;
            @$dom->loadHTMLFile($url);
            $dom->preserveWhiteSpace = false;
            
            $xpath = new \DOMXpath($dom);

            //Create node lists
            $nl_matches = $xpath->query('//article[contains(@class, "oldpost")]//a[contains(@class, "infopop")]//img/@alt');

            $exists = 0;
            for($i = 0; $i < count($nl_matches); $i++) {
                if (strtolower($nl_matches->item($i)->nodeValue) == strtolower($movie->name)) {
                    $exists = 1;
                    break;
                }
            }
            DB::insert("INSERT INTO netflix (movie_id, on_netflix, created_at, updated_at) 
                VALUES ($movie->id, $exists, NOW(), NOW())
                ON DUPLICATE KEY UPDATE on_netflix=$exists, updated_at=NOW()");
        }
    }
}
