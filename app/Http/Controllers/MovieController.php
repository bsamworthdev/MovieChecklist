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
use Goutte\Client;
use Illuminate\Support\Facades\Log;

class MovieController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    //
    function updateMovies(){
        set_time_limit(30000);
        for($page=0;$page<10;$page++){
            $url = 'https://www.imdb.com/search/title/?groups=top_1000&view=simple&sort=user_rating,desc&count=100&start='.(($page * 100) + 1);
            // $url = 'https://www.imdb.com/search/title/?count=100&groups=top_1000&sort=user_rating';
            $client = new Client();
            $crawler = $client->request('GET', $url);        
            $scraped_movies = $crawler->evaluate('//span[@class="lister-item-header"]');
            Log::Debug('starting...');
            foreach ($scraped_movies as $key => $movie) {
                $movie_imdb_id = explode("/", $crawler->evaluate('//span[@class="lister-item-header"]//span//a')->eq($key)->link()->getUri())[4];
                $movie_rank = $crawler->evaluate('//span[@class="lister-item-header"]//span[@class="lister-item-index unbold text-primary"]')->eq($key)->text();
                $movie_rating = $crawler->evaluate('//div[@class="lister-item-content"]//div[@class="col-imdb-rating"]//strong')->eq($key)->text();

                $existing_movies_count = Movie::where('imdb_id','=',$movie_imdb_id)->get()->count();
                if ($existing_movies_count == 0){
                    //Movie needs to be added
                    $movie_title = $crawler->evaluate('//span[@class="lister-item-header"]//span//a')->eq($key)->text();
                    $movie_rating = $crawler->evaluate('//div[@class="lister-item-content"]//div[@class="col-imdb-rating"]//strong')->eq($key)->text();
                    $movie_year = $crawler->evaluate('//span[@class="lister-item-header"]//span[@class="lister-item-year text-muted unbold"]')->eq($key)->text();
                    $movie_genre = '';
                    $movie_language = 'english';

                    $movie = new Movie;
                    $movie->imdb_id = $movie_imdb_id;
                    $movie->name = $movie_title;
                    $movie->rank = str_replace(',','', str_replace('.','', $movie_rank));
                    $movie->rating = $movie_rating;
                    $movie->year = str_replace(' ','',str_replace('(', '', str_replace(')', '', str_replace('I','', $movie_year))));
                    $movie->genre = $movie_genre;
                    $movie->language = $movie_language;
                    $movie->save();
                } else {
                    //Movie exists, so just update rank
                    Movie::where('imdb_id',$movie_imdb_id)
                        ->update([
                            'rank' => str_replace(',','', str_replace('.','', $movie_rank)), 
                            'rating' => $movie_rating 
                        ]);
                }
                Log::Debug('movie rank:'.$movie_rank);
                
            }
        }

        //Update votes
        $this->updateVotes();
    }

    function updateVotes(){
        for($page=0;$page<10;$page++){
            // $url = 'https://www.imdb.com/search/title/?groups=top_1000&view=simple&sort=user_rating,desc&count=100&start='.(($page * 100) + 1);
            $url = 'https://www.imdb.com/search/title/?count=100&groups=top_1000&sort=user_rating&start='.(($page * 100) + 1);
            $client = new Client();
            $crawler = $client->request('GET', $url);        
            $scraped_movies = $crawler->evaluate('//h3[@class="lister-item-header"]');
            Log::Debug('starting...page '.($page+1));
            foreach ($scraped_movies as $key => $movie) {
                $movie_imdb_id = explode("/", $crawler->evaluate('//h3[@class="lister-item-header"]//a')->eq($key)->link()->getUri())[4];
                $votes = $crawler->evaluate('//p[@class="sort-num_votes-visible"]')->eq($key)->text();
                $votes = str_replace(',','', explode(':', explode('|', $votes)[0])[1]);

                $existing_movies_count = Movie::where('imdb_id','=',$movie_imdb_id)->get()->count();
                if ($existing_movies_count > 0){
                    //Movie exists, so just update rank
                    Movie::where('imdb_id',$movie_imdb_id)
                        ->update([
                            'votes' => $votes
                        ]);
                }
                Log::Debug('movie votes:'.$votes);
                
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

    function updateMovieSynopsises(){
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
        } elseif ($platform == 'disney_plus'){
            $movies =  DB::table('disney_plus')->where('movie_id', '=', $movie_id)->get();
            if (count($movies) == 0) {
                DB::insert("INSERT INTO disney_plus (movie_id, on_disney_plus, created_at, updated_at) 
                VALUES ($movie_id, $status, NOW(), NOW())");
            } else {
                DB::update("UPDATE disney_plus SET on_disney_plus=?, updated_at=?
                WHERE movie_id=?", [$status, NOW(), $movie_id]);
            }
        }
    }

    function getMovies($genre, $time_period, $english_only, $popular_only, $unwatched_only, 
        $favourites_only, $search_text, $netflix_only, $amazon_only, $nowtv_only, 
        $disney_plus_only, $unwatched_by_friends, $skip_count = 0) {

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
        ->leftJoin('disney_plus', function ($join) {
            $join->on('disney_plus.movie_id', '=', 'movies.id');
        })
        ->leftJoin('watch_list', function ($join) use ($user_id) {
            $join->on('watch_list.movie_id', '=', 'movies.id');
            $join->on('watch_list.user_id', '=', DB::raw("$user_id"));
        })
        ->leftJoin('movie_user as mu', function ($join) use ($user_id) {
            $join->on('mu.movie_id', '=', 'movies.id');
            $join->on('mu.user_id', '=', DB::raw("$user_id"));
        })
        ->where(function ($q) use ($user_id) {
            return $q->where('mu.user_id', '=', $user_id)
                ->orWhere('mu.user_id', '=', NULL);
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
        ->when($popular_only == 1, function ($q) {
            return $q->where('movies.votes', '>', 250000);
        })
        ->when($favourites_only == 1, function ($q) {
            return $q->where('mu.favourite', '=', '1');
        })
        ->when($search_text != '', function ($q) use ($search_text) {
            return $q->where('movies.name', 'LIKE', '%'.trim($search_text).'%');
        })
        ->when(($netflix_only == 1), function ($q) {
            return $q->where('netflix.on_netflix', '=', '1');
        })
        ->when(($amazon_only == 1), function ($q) {
            return $q->where('amazon.on_amazon', '=', '1');
        })
        ->when(($nowtv_only == 1), function ($q) {
            return $q->where('nowtv.on_nowtv', '=', '1');
        })
        ->when(($disney_plus_only == 1), function ($q) {
            return $q->where('disney_plus.on_disney_plus', '=', '1');
        })
        ->when($unwatched_only == 1, function ($q) {
            return $q->where('mu.user_id', '=', NULL);
        })
        ->when($unwatched_by_friends !== '', function ($q) use ($unwatched_by_friends, $user_id) {
            $arr = explode('|', $unwatched_by_friends);
            return $q->whereNotIn('movies.id', function($q) use ($arr)
            {
                $q->from('movie_user as mu2')
                    ->selectRaw('distinct mu2.movie_id')
                    ->whereIn('mu2.user_id', $arr);
            });
        })
        ->orderBy('rank', 'ASC')
        ->skip($skip_count)
        ->take(100)
        ->get([
            'movies.*',
            DB::raw('CONCAT(\'https://moviechecklistcdn.s3.amazonaws.com\',movies.image_url_small) as image_url_small_extended'),
            DB::raw('IF(ISNULL(netflix.on_netflix), \'0\', netflix.on_netflix) as on_netflix'),
            DB::raw('IF(ISNULL(amazon.on_amazon), \'0\', amazon.on_amazon) as on_amazon'),
            DB::raw('IF(ISNULL(nowtv.on_nowtv), \'0\', nowtv.on_nowtv) as on_nowtv'),
            DB::raw('IF(ISNULL(disney_plus.on_disney_plus), \'0\', disney_plus.on_disney_plus) as on_disney_plus'),
            DB::raw('IF(ISNULL(mu.user_id), \'0\', \'1\') as watched'),
            DB::raw('IF(ISNULL(mu.favourite), \'0\', mu.favourite) as favourite'),
            DB::raw('IF(ISNULL(watch_list.movie_id), \'0\', \'1\') as on_watch_list')
        ]);

        
        return $movies;
    }

    // function getMoreMovies($skip_count = 0, $genre = 'all', $time_period = 'all', $english_only = 0, 
    //     $unwatched_only = 0, $favourites_only = 0, $netflix_only = 0, $amazon_only = 0, $nowtv_only = 0, 
    //     $search_text = '') {
    function getMoreMovies(Request $request) {
            
        $genre = $request->genre;
        $time_period = $request->time_period; 
        $english_only = $request->english_only;
        $popular_only = $request->popular_only;
        $unwatched_only = $request->unwatched_only;
        $favourites_only = $request->favourites_only;
        $search_text = $request->search_text;
        $netflix_only = $request->netflix_only;
        $amazon_only = $request->amazon_only;
        $nowtv_only = $request->nowtv_only;
        $disney_plus_only = $request->disney_plus_only;
        $skip_count = $request->skip_count;
        $unwatched_by_friends = $request->unwatched_by_friends;


        if ($search_text == "null") $search_text = '';

        $movies = $this->getMovies($genre, $time_period, $english_only, $popular_only, $unwatched_only, 
        $favourites_only, $search_text, $netflix_only, $amazon_only, $nowtv_only, $disney_plus_only,
        $unwatched_by_friends, $skip_count);

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