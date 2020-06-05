<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Movie;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Auth;

class MovieController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
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

            //$movies_id_db =  Movie::where('imdb_id', '=', $movie->imdb_id)->get();
            $movies_id_db =  DB::table('netflix')
                ->where('movie_id', '=', $movie->id)->get();
            if (count($movies_id_db) == 0) {
                DB::insert("INSERT INTO netflix (movie_id, on_netflix, created_at, updated_at) 
                VALUES ($movie->id, $exists, NOW(), NOW())");
            } else {
                DB::update("UPDATE netflix SET on_netflix=$exists, updated_at=NOW() 
                WHERE id=".$movies_id_db->first()->id);
            }
        }
    }
    function updateAmazonStatuses(){
        // $oneWeekAgo = \Carbon\Carbon::today()->subWeek();
        // $movies = Movie::where('updated_at', '<', $oneWeekAgo)->get();
        
        $url = 'https://www.finder.com/uk/amazon-prime-movies';

        $dom = new \DOMDocument;
        @$dom->loadHTMLFile($url);
        $dom->preserveWhiteSpace = false;
        
        $xpath = new \DOMXpath($dom);
        
        $nl_names = $xpath->query('//table[contains(@class, "custom-table")]//tr//td/text()');

        for($i = 0; $i < count($nl_names); $i++) {
            $movies = Movie::where('name', '=', $nl_names->item($i)->nodeValue)->get();

            if ( count($movies) > 0){
                DB::insert("INSERT INTO amazon (movie_id, on_amazon, created_at, updated_at) 
                    VALUES (".$movies->first()->id.", '1', NOW(), NOW())
                    ON DUPLICATE KEY UPDATE on_amazon='1', updated_at=NOW()");
            }
        }
        
        //foreach($movies as $movie) {

            // $url = 'https://www.justwatch.com/uk/search?providers=amp&content_type=movie&q='.$movie->name;
            //$url = 'https://www.justwatch.com/uk/search?providers=amp&content_type=movie&q=Jurassic%20Park';
            // $url = 'https://www.finder.com/uk/amazon-prime-movies';

            // $dom = new \DOMDocument;
            // @$dom->loadHTMLFile($url);
            // $dom->preserveWhiteSpace = false;
            
            // $xpath = new \DOMXpath($dom);

            //Create node lists
            // $nl_matches = $xpath->query('//*[contains(@class, "title-list-row__row")]');
            // $nl_matches_titles = $xpath->query('//*[contains(@class, "title-list-row__row")]//span[contains(@class, "title-list-row__row__title")]');
            // $nl_matches_years = $xpath->query('//*[contains(@class, "title-list-row__row")]//span[contains(@class, "title-list-row__row--muted")]');

            // $exists = 0;
            // for($i = 0; $i < count($nl_matches); $i++) {
            //     if (strtolower($nl_matches_titles->item($i)->nodeValue) == strtolower($movie->name)) {
            //         if ($nl_matches_years->item($i)->nodeValue == $movie->year) {
            //             $exists = 1;
            //             break;
            //         }
            //     }
            // }

            // DB::insert("INSERT INTO amazon (movie_id, on_amazon, created_at, updated_at) 
            //     VALUES ($movie->id, $exists, NOW(), NOW())
            //     ON DUPLICATE KEY UPDATE on_amazon=$exists, updated_at=NOW()");
        //}
    }

    // function updateAmazonStatuses(){
    //     // $oneWeekAgo = \Carbon\Carbon::today()->subWeek();
    //     // $movies = Movie::where('updated_at', '<', $oneWeekAgo)->get();
        
    //     $movies = Movie::all();
    //     foreach($movies as $movie_count => $movie) {

    //         if ($movie_count > 10) break;

    //         // $url = 'https://www.justwatch.com/uk/search?providers=amp&content_type=movie&q='.$movie->name;
    //         //$url = 'https://www.amazon.co.uk/s?k='.$movie->name.'&i=instant-video&ref=nb_sb_noss_1';
    //         $url = 'https://www.amazon.co.uk/s?k='.$movie->name.'&i=instant-video&bbn=3010085031&rh=p_85%3A3282143031&dc&qid=1588319554&rnid=3282142031&ref=sr_nr_p_85_1';
            
    //         // $url = 'https://www.finder.com/uk/amazon-prime-movies';

    //         $dom = new \DOMDocument;
    //         @$dom->loadHTMLFile($url);
    //         $dom->preserveWhiteSpace = false;
            
    //         $xpath = new \DOMXpath($dom);

    //         // sleep(4);

    //         //Create node lists
    //         $nl_matches = $xpath->query('//div[contains(@class, "s-main-slot")]/div');
    //         //$nl_matches_titles = $xpath->query('//div[contains(@class, "s-main-slot")]/div//h2/a/span');

    //         $exists = 0;
    //         //for($i = 0; $i < $checkLimit; $i++) {
    //         foreach ($nl_matches as $i => $nl_match) {

    //             if ($i > 1) break;

    //             $nl_matches_titles = $xpath->query(".//h2/a/span", $nl_match);
    //             if (count($nl_matches_titles) > 0 && strtolower($nl_matches_titles->item(0)->nodeValue) == strtolower($movie->name)) {

    //                 //$nl_matches_prime = $xpath->query('.//a/span[contains(@class, "s-play-button")]', $nl_match);
    //                 //if (count($nl_matches_prime) > 0){

    //                     $exists = 1;
    //                 //}
    //                 break;
    //             }
    //         }

    //         $movies_id_db =  DB::table('amazon')
    //             ->where('movie_id', '=', $movie->id)->get();
    //         if (count($movies_id_db) == 0) {
    //             DB::insert("INSERT INTO amazon (movie_id, on_amazon, created_at, updated_at) 
    //             VALUES ($movie->id, $exists, NOW(), NOW())");
    //         } else {
    //             DB::update("UPDATE amazon SET on_amazon=$exists, updated_at=NOW() 
    //             WHERE id=".$movies_id_db->first()->id);
    //         }
    //     }
    // }

    function setMovieStreamStatus (Request $request) {
        $user_id = Auth::user()->id;
        $movie_id = $request->movie_id;
        $platform = $request->platform;
        $status = $request->status;

        if ($platform == 'netflix'){
            $movies =  DB::table('netflix')->where('movie_id', '=', $movie_id)->get();
            if (count($movies) == 0) {
                DB::insert("INSERT INTO netflix (movie_id, on_netflix, created_at, updated_at) 
                VALUES ($movie_id, $status, NOW(), NOW())");
            } else {
                DB::update("UPDATE netflix SET on_netflix=$status, updated_at=NOW() 
                WHERE movie_id=".$movie_id);
            }
        } elseif ($platform == 'amazon'){
            $movies =  DB::table('amazon')->where('movie_id', '=', $movie_id)->get();
            if (count($movies) == 0) {
                DB::insert("INSERT INTO amazon (movie_id, on_amazon, created_at, updated_at) 
                VALUES ($movie_id, $status, NOW(), NOW())");
            } else {
                DB::update("UPDATE amazon SET on_amazon=?, updated_at=?
                WHERE movie_id=?", [$status, NOW(), $movie_id]);
            }
        } elseif ($platform == 'nowtv'){
            $movies =  DB::table('nowtv')->where('movie_id', '=', $movie_id)->get();
            if (count($movies) == 0) {
                DB::insert("INSERT INTO nowtv (movie_id, on_nowtv, created_at, updated_at) 
                VALUES ($movie_id, $status, NOW(), NOW())");
            } else {
                DB::update("UPDATE nowtv SET on_nowtv=?, updated_at=?
                WHERE movie_id=?", [$status, NOW(), $movie_id]);
            }
        }
    }

    function getMovies($genre, $time_period, $english_only, $unwatched_only, 
        $favourites_only, $netflix_only, $amazon_only, $nowtv_only, $search_text, $skip_count = 0){

        $user = Auth::user();
        $user_id = $user->id;

        //Movies
        $movies = DB::table('movies')
        ->leftJoin('netflix', function ($join) {
            $join->on('netflix.movie_id', '=', 'movies.id');
        })
        ->leftJoin('amazon', function ($join) {
            $join->on('amazon.movie_id', '=', 'movies.id');
        })
        ->leftJoin('nowtv', function ($join) {
            $join->on('nowtv.movie_id', '=', 'movies.id');
        })
        ->leftJoin('watch_list', function ($join) use ($user_id) {
            $join->on('watch_list.movie_id', '=', 'movies.id');
            $join->on('watch_list.user_id', '=', DB::raw("$user_id"));
        })
        ->leftJoin('movie_user', function ($join) use ($user_id) {
            $join->on('movie_user.movie_id', '=', 'movies.id');
            $join->on('movie_user.user_id', '=', DB::raw("$user_id"));
        })
        ->where(function ($q) use ($user_id) {
            return $q->where('movie_user.user_id', '=', $user_id)
                ->orWhere('movie_user.user_id', '=', NULL);
        })
        ->when($genre <> 'all', function ($q) use ($genre) {
            return $q->where('movies.genre', 'LIKE', '%' . $genre . '%');
        })
        ->when($time_period <> 'all', function ($q) use ($time_period) {
            $dates = Movie::parseTimePeriod($time_period);
            return $q->whereBetween('movies.year', [$dates['from'], $dates['to']]);
        })
        ->when($english_only == 1, function ($q) {
            return $q->where('movies.language', '=', 'english');
        })
        ->when($favourites_only == 1, function ($q) {
            return $q->where('movie_user.favourite', '=', '1');
        })
        ->when(($netflix_only == 1 && $amazon_only == 1 && $nowtv_only == 1), function ($q) {
            return $q->where(function ($q) {
                $q->where('netflix.on_netflix', '=', '1')
                    ->orWhere('amazon.on_amazon', '=', '1')
                    ->orWhere('nowtv.on_nowtv', '=', '1');
            });
        })
        ->when(($netflix_only == 1 && $amazon_only == 1 && $nowtv_only == 0), function ($q) {
            return $q->where(function ($q) {
                $q->where('netflix.on_netflix', '=', '1')
                    ->orWhere('amazon.on_amazon', '=', '1');
            });
        })
        ->when(($netflix_only == 1 && $amazon_only == 0 && $nowtv_only == 1), function ($q) {
            return $q->where(function ($q) {
                $q->where('netflix.on_netflix', '=', '1')
                    ->orWhere('nowtv.on_nowtv', '=', '1');
            });
        })
        ->when(($netflix_only == 0 && $amazon_only == 1 && $nowtv_only == 1), function ($q) {
            return $q->where(function ($q) {
                $q->where('amazon.on_amazon', '=', '1')
                    ->orWhere('nowtv.on_nowtv', '=', '1');
            });
        })
        ->when(($netflix_only == 1 && $amazon_only == 0 && $nowtv_only == 0), function ($q) {
            return $q->where('netflix.on_netflix', '=', '1');
        })
        ->when(($netflix_only == 0 && $amazon_only == 1 && $nowtv_only == 0), function ($q) {
            return $q->where('amazon.on_amazon', '=', '1');
        })
        ->when(($netflix_only == 0 && $amazon_only == 0 && $nowtv_only == 1), function ($q) {
            return $q->where('nowtv.on_nowtv', '=', '1');
        })

        ->when($unwatched_only == 1, function ($q) {
            return $q->where('movie_user.user_id', '=', NULL);
        })
        ->when($search_text != '', function ($q) use ($search_text) {
            return $q->where('movies.name', 'LIKE', '%'.trim($search_text).'%');
        })
        ->orderBy('rank', 'ASC')
        ->skip($skip_count)
        ->take(100)
        ->get([
            'movies.*',
            DB::raw('IF(ISNULL(netflix.on_netflix), \'0\', netflix.on_netflix) as on_netflix'),
            DB::raw('IF(ISNULL(amazon.on_amazon), \'0\', amazon.on_amazon) as on_amazon'),
            DB::raw('IF(ISNULL(nowtv.on_nowtv), \'0\', nowtv.on_nowtv) as on_nowtv'),
            DB::raw('IF(ISNULL(movie_user.user_id), \'0\', \'1\') as watched'),
            DB::raw('IF(ISNULL(movie_user.favourite), \'0\', movie_user.favourite) as favourite'),
            DB::raw('IF(ISNULL(watch_list.movie_id), \'0\', \'1\') as on_watch_list')
        ]);
        return $movies;
    }

    function getMoreMovies($skip_count = 0, $genre = 'all', $time_period = 'all', $english_only = 0, 
        $unwatched_only = 0, $favourites_only = 0, $netflix_only = 0, $amazon_only = 0, $nowtv_only = 0, 
        $search_text = '') {

        $movies = $this->getMovies($genre, $time_period, $english_only, $unwatched_only, 
        $favourites_only, $netflix_only, $amazon_only, $nowtv_only, $search_text, $skip_count);

        //Set movie index
        $count = $skip_count + 1;
        foreach ($movies as $movie) {
            $movie->index = $count;
            $count++;
        }

        //Set friends count
        $user = Auth::user();
        $user_id = $user->id;
        $UserObj = User::find($user_id);

        $friendsA = $UserObj->friendshipsA()->get();
        $friendsB = $UserObj->friendshipsB()->get();
        $friends = [];
        foreach ($friendsA as $friend) {
            $friends[] = $friend->person_B_user_id;
        }
        foreach ($friendsB as $friend) {
            $friends[] = $friend->person_A_user_id;
        }
        
        foreach ($movies as &$movie) {
            $count = 0;

            $count += DB::table('movie_user')
                ->where ('movie_id', '=', $movie->id)
                ->whereIn('user_id', $friends)
                ->get()
                ->count();

            // $count += count(DB::select('select * from movie_user where movie_id=? and user_id=?',[$movie->id, $friend->person_A_user_id])) > 0;
            
            $movie->friendsWatched = $count;
        }


        return $movies;
    }

}