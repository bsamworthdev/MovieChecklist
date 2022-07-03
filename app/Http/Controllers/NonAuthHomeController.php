<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Movie;
use App\User;
use App\MovieGenre;
use App\MovieTimePeriod;
use App\InfoMessage;
use App\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NonAuthHomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show(Request $request,
        $genre = 'all', $time_period = 'all', $english_only = 0, $unwatched_only = 0, 
        $favourites_only = 0, $search_text = "", $netflix_only = 0, $amazon_only = 0, $nowtv_only = 0)
    {

        //Users
        $session = $request->session();
        $session_id = $session->getId();
        
        if ($search_text == "null") $search_text = '';

        // $user->friendsCount = $UserObj->friendsCount();

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
            ->leftJoin('watch_list_non_auth', function ($join) use ($session_id) {
                $join->on('watch_list_non_auth.movie_id', '=', 'movies.id');
                $join->on('watch_list_non_auth.session_id', '=', DB::raw("'$session_id'"));
            })
            ->leftJoin('movie_user_non_auth', function ($join) use ($session_id) {
                $join->on('movie_user_non_auth.movie_id', '=', 'movies.id');
                $join->on('movie_user_non_auth.session_id', '=', DB::raw("'$session_id'"));
            })
            ->where(function ($q) use ($session_id) {
                return $q->where('movie_user_non_auth.session_id', '=', DB::raw("'$session_id'"))
                    ->orWhere('movie_user_non_auth.session_id', '=', NULL);
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
                return $q->where('movie_user_non_auth.favourite', '=', '1');
            })
            ->when($search_text != '', function ($q) use ($search_text) {
                return $q->where('movies.name', 'LIKE', '%'.$search_text.'%');
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
                return $q->where('movie_user_non_auth.session_id', '=', NULL);
            })
            ->orderBy('rank', 'ASC')
            ->take(100)
            ->get([
                'movies.*',
                DB::raw('CONCAT(\'https://moviechecklistcdn.s3.amazonaws.com\',movies.image_url_small) as image_url_small_extended'),
                DB::raw('IF(ISNULL(netflix.on_netflix), \'0\', netflix.on_netflix) as on_netflix'),
                DB::raw('IF(ISNULL(amazon.on_amazon), \'0\', amazon.on_amazon) as on_amazon'),
                DB::raw('IF(ISNULL(nowtv.on_nowtv), \'0\', nowtv.on_nowtv) as on_nowtv'),
                DB::raw('IF(ISNULL(movie_user_non_auth.session_id), \'0\', \'1\') as watched'),
                DB::raw('IF(ISNULL(movie_user_non_auth.favourite), \'0\', movie_user_non_auth.favourite) as favourite'),
                DB::raw('IF(ISNULL(watch_list_non_auth.movie_id), \'0\', \'1\') as on_watch_list')
            ]);

        $count = 1;
        foreach ($movies as $movie) {
            $movie->index = $count;
            $count++;
        }

        $movie_genres = MovieGenre::orderBy('label')->get();
        $selected_genre = $genre;

        $time_periods = MovieTimePeriod::all();
        $selected_time_period = $time_period;

        $selected_english_only = $english_only;
        $selected_unwatched_only = $unwatched_only;
        $selected_favourites_only = $favourites_only;
        $selected_search_text = $search_text;
        $selected_netflix_only = $netflix_only;
        $selected_amazon_only = $amazon_only;
        $selected_nowtv_only = $nowtv_only;


        // $friendsA = $UserObj->friendshipsA()->get();
        // $friendsB = $UserObj->friendshipsB()->get();
        // $friends =[];
        // foreach ($friendsA as $friend) {
        //     $friends[]=$friend->person_B_user_id;
        // }
        // foreach ($friendsB as $friend) {
        //     $friends[]=$friend->person_A_user_id;
        // }
        
        // foreach ($movies as &$movie) {
        //     $count = 0;

        //     $count += DB::table('movie_user')
        //         ->where ('movie_id', '=', $movie->id)
        //         ->whereIn('user_id', $friends)
        //         ->get()
        //         ->count();

        //     // $count += count(DB::select('select * from movie_user where movie_id=? and user_id=?',[$movie->id, $friend->person_A_user_id])) > 0;
            
        //     $movie->friendsWatched = $count;
        // }

        $watch_list = DB::table('watch_list_non_auth')
                ->where('session_id', $session_id)
                ->join('movies','movies.id','watch_list_non_auth.movie_id')
                ->leftJoin('netflix', function ($join) {
                    $join->on('netflix.movie_id', '=', 'movies.id');
                })
                ->leftJoin('amazon', function ($join) {
                    $join->on('amazon.movie_id', '=', 'movies.id');
                })
                ->leftJoin('nowtv', function ($join) {
                    $join->on('nowtv.movie_id', '=', 'movies.id');
                })
                ->orderBy('rank', 'ASC')
                ->get([
                    'movies.*',
                    DB::raw('IF(ISNULL(netflix.on_netflix), \'0\', netflix.on_netflix) as on_netflix'),
                    DB::raw('IF(ISNULL(amazon.on_amazon), \'0\', amazon.on_amazon) as on_amazon'),
                    DB::raw('IF(ISNULL(nowtv.on_nowtv), \'0\', nowtv.on_nowtv) as on_nowtv')
                ]);
        
        $info_messages = InfoMessage::where('start_date', '<', DB::raw('now()'))
            ->where('end_date', '>' , DB::raw('now()'))
            ->get();

        return view(
            'home-non-auth',
            [
                "watchList" => $watch_list,
                "movies" => $movies,
                "genres" => $movie_genres,
                "selectedGenre" => $selected_genre,
                "timePeriods" => $time_periods,
                "selectedTimePeriod" => $selected_time_period,
                "selectedEnglishOnly" => $selected_english_only,
                "selectedUnwatchedOnly" => $selected_unwatched_only,
                "selectedFavouritesOnly" => $selected_favourites_only,
                "selectedSearchText" => $selected_search_text,
                "selectedNetflixOnly" => $selected_netflix_only,
                "selectedAmazonOnly" => $selected_amazon_only,
                "selectedNowtvOnly" => $selected_nowtv_only,
                "infoMessages" => $info_messages
            ]
        );
    }

}
