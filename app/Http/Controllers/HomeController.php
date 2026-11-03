<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Movie;
use App\User;
use App\MovieGenre;
use App\MovieTimePeriod;
use App\InfoMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    // public function index(
    //     $genre = 'all', $time_period = 'all', $english_only = 0, $unwatched_only = 0, 
    //     $favourites_only = 0, $search_text = "", $netflix_only = 0, $amazon_only = 0, 
    //     $nowtv_only = 0, $unwatched_by_friends = '')
    // {
    public function index(Request $request)
        {

        $genre = 'all'; 
        $time_period = 'all';
        $english_only = 0;
        $popular_only = 0;
        $unwatched_only = 0;
        $favourites_only = 0;
        $search_text = "";
        $netflix_only = 0;
        $amazon_only = 0;
        $nowtv_only = 0;
        $disney_plus_only = 0;
        $hbo_max_only = 0;
        $apple_tv_only = 0;
        $paramount_plus_only = 0;
        $bbc_iplayer_only = 0;
        $itvx_only = 0;
        $channel4_only = 0;
        $unwatched_by_friends = '';
        
        // dd($request->fullUrl());
        $filters = $request->input('filters');
        if ($filters){
            $filters = explode(';', $filters);
            foreach ($filters as $filter){
                if (!str_contains($filter,':')) break;
                
                $val = explode(':', $filter)[1];
                switch (explode(':', $filter)[0]){
                    case 'genre':
                        $genre=$val;
                        break;
                    case 'time':
                        $time_period=$val;
                        break;
                    case 'english':
                        $english_only=$val;
                        break;
                    case 'popular':
                        $popular_only=$val;
                        break;
                    case 'unwatched':
                        $unwatched_only=$val;
                        break;
                    case 'favourites':
                        $favourites_only=$val;
                        break;
                    case 'search':
                        $search_text=$val;
                        break;
                    case 'netflix':
                        $netflix_only=$val;
                        break;
                    case 'amazon':
                        $amazon_only=$val;
                        break;
                    case 'nowtv':
                        $nowtv_only=$val;
                        break;
                    case 'disney_plus':
                        $disney_plus_only=$val;
                        break;
                    case 'hbo_max':
                        $hbo_max_only=$val;
                        break;
                    case 'apple_tv':
                        $apple_tv_only=$val;
                        break;
                    case 'paramount_plus':
                        $paramount_plus_only=$val;
                        break;
                    case 'bbc_iplayer':
                        $bbc_iplayer_only=$val;
                        break;
                    case 'itvx':
                        $itvx_only=$val;
                        break;
                    case 'channel4':
                        $channel4_only=$val;
                        break;
                    case 'friends':
                        $unwatched_by_friends=$val;
                        break;
                }
            }
        }

        //Users
        $user = Auth::user();
        $user_id = $user->id;
        $UserObj = User::find($user_id);
        $user->friendsCount = $UserObj->friendsCount();

        if ($search_text == "null") $search_text = '';

        $movieController = new MovieController;
        $movies = $movieController->getMovies($genre, $time_period, $english_only, $popular_only, $unwatched_only, 
        $favourites_only, $search_text, $netflix_only, $amazon_only, $nowtv_only, $disney_plus_only, $unwatched_by_friends, 0, $hbo_max_only, $apple_tv_only, $paramount_plus_only, $bbc_iplayer_only, $itvx_only, $channel4_only);

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
        $selected_popular_only = $popular_only;
        $selected_unwatched_only = $unwatched_only;
        $selected_favourites_only = $favourites_only;
        $selected_search_text = $search_text;
        $selected_netflix_only = $netflix_only;
        $selected_amazon_only = $amazon_only;
        $selected_nowtv_only = $nowtv_only;
        $selected_disney_plus_only = $disney_plus_only;
        $selected_hbo_max_only = $hbo_max_only;
        $selected_apple_tv_only = $apple_tv_only;
        $selected_paramount_plus_only = $paramount_plus_only;
        $selected_bbc_iplayer_only = $bbc_iplayer_only;
        $selected_itvx_only = $itvx_only;
        $selected_channel4_only = $channel4_only;
        $selected_unwatched_by_friends = $unwatched_by_friends;


        // $friendsA = $UserObj->friendshipsA()->get();
        // $friendsB = $UserObj->friendshipsB()->get();
        // $friend_ids =[];
        // foreach ($friendsA as $friend) {
        //     $friend_ids[]=$friend->person_B_user_id;
        // }
        // foreach ($friendsB as $friend) {
        //     $friend_ids[]=$friend->person_A_user_id;
        // }

        $friendsA = $UserObj->friendshipsA()
            ->join('users', 'users.id', '=', 'friendships.person_B_user_id');

        $friends = $UserObj->friendshipsB()
            ->join('users', 'users.id', '=', 'friendships.person_A_user_id')
            ->union($friendsA)
            ->orderBy('name')
            ->get('*');
        
        $friend_ids = [];
        foreach ($friends as $friend) {
            $friend_ids[]=$friend->id;
        }

        $friends = [];
        foreach ($friend_ids as $friend_id) {
            $friends[] = User::find($friend_id);
        }
        $user->friends = $friends;
        
        foreach ($movies as &$movie) {
            $count = 0;

            $count += DB::table('movie_user')
                ->where ('movie_id', '=', $movie->id)
                ->whereIn('user_id', $friend_ids)
                ->get()
                ->count();

            // $count += count(DB::select('select * from movie_user where movie_id=? and user_id=?',[$movie->id, $friend->person_A_user_id])) > 0;
            
            $movie->friendsWatched = $count;
        }

        $watch_list = DB::table('watch_list')
                ->where('user_id', $user_id)
                ->join('movies','movies.id','watch_list.movie_id')
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
                    $join->on('disney_plus.movie_id', '=', 'disney_plus.id');
                })
                ->leftJoin('hbo_max', function ($join) {
                    $join->on('hbo_max.movie_id', '=', 'movies.id');
                })
                ->leftJoin('apple_tv', function ($join) {
                    $join->on('apple_tv.movie_id', '=', 'movies.id');
                })
                ->leftJoin('paramount_plus', function ($join) {
                    $join->on('paramount_plus.movie_id', '=', 'movies.id');
                })
                ->leftJoin('bbc_iplayer', function ($join) {
                    $join->on('bbc_iplayer.movie_id', '=', 'movies.id');
                })
                ->leftJoin('itvx', function ($join) {
                    $join->on('itvx.movie_id', '=', 'movies.id');
                })
                ->leftJoin('channel4', function ($join) {
                    $join->on('channel4.movie_id', '=', 'movies.id');
                })
                ->orderBy('top250_rank', 'ASC')
                ->orderBy('rank', 'ASC')
                ->get([
                    'movies.*',
                    DB::raw('IF(ISNULL(netflix.on_netflix), \'0\', netflix.on_netflix) as on_netflix'),
                    DB::raw('IF(ISNULL(amazon.on_amazon), \'0\', amazon.on_amazon) as on_amazon'),
                    DB::raw('IF(ISNULL(nowtv.on_nowtv), \'0\', nowtv.on_nowtv) as on_nowtv'),
                    DB::raw('IF(ISNULL(disney_plus.on_disney_plus), \'0\', disney_plus.on_disney_plus) as on_disney_plus'),
                    DB::raw('IF(ISNULL(hbo_max.on_hbo_max), \'0\', hbo_max.on_hbo_max) as on_hbo_max'),
                    DB::raw('IF(ISNULL(apple_tv.on_apple_tv), \'0\', apple_tv.on_apple_tv) as on_apple_tv'),
                    DB::raw('IF(ISNULL(paramount_plus.on_paramount_plus), \'0\', paramount_plus.on_paramount_plus) as on_paramount_plus'),
                    DB::raw('IF(ISNULL(bbc_iplayer.on_bbc_iplayer), \'0\', bbc_iplayer.on_bbc_iplayer) as on_bbc_iplayer'),
                    DB::raw('IF(ISNULL(itvx.on_itvx), \'0\', itvx.on_itvx) as on_itvx'),
                    DB::raw('IF(ISNULL(channel4.on_channel4), \'0\', channel4.on_channel4) as on_channel4'),
                ]);
        
        $info_messages = InfoMessage::where('start_date', '<', DB::raw('now()'))
            ->where('end_date', '>' , DB::raw('now()'))
            ->get();

        $filters = collect([
            "genre" => $selected_genre,
            "time_period" => $selected_time_period,
            "english_only" => $selected_english_only,
            "popular_only" => $selected_popular_only,
            "unwatched_only" => $selected_unwatched_only,
            "favourites_only" => $selected_favourites_only,
            "search_text" => $selected_search_text,
            "netflix_only" => $selected_netflix_only,
            "amazon_only" => $selected_amazon_only,
            "nowtv_only" => $selected_nowtv_only,
            "disney_plus_only" => $selected_disney_plus_only,
            "unwatched_by_friends" => $selected_unwatched_by_friends,
            "hbo_max_only" => $selected_hbo_max_only,
            "apple_tv_only" => $selected_apple_tv_only,
            "paramount_plus_only" => $selected_paramount_plus_only,
            "bbc_iplayer_only" => $selected_bbc_iplayer_only,
            "itvx_only" => $selected_itvx_only,
            "channel4_only" => $selected_channel4_only,
        ]);

        return view(
            'home',
            [
                "user" => $user,
                "watchList" => $watch_list,
                "movies" => $movies,
                "genres" => $movie_genres,
                "selectedGenre" => $selected_genre,
                "timePeriods" => $time_periods,
                "filters" => $filters,
                "selectedTimePeriod" => $selected_time_period,
                "selectedEnglishOnly" => $selected_english_only,
                "selectedPopularOnly" => $selected_popular_only,
                "selectedUnwatchedOnly" => $selected_unwatched_only,
                "selectedFavouritesOnly" => $selected_favourites_only,
                "selectedSearchText" => $selected_search_text,
                "selectedNetflixOnly" => $selected_netflix_only,
                "selectedAmazonOnly" => $selected_amazon_only,
                "selectedNowtvOnly" => $selected_nowtv_only,
                "selectedDisneyPlusOnly" => $selected_disney_plus_only,
                "selectedHboMaxOnly" => $selected_hbo_max_only,
                "selectedAppleTvOnly" => $selected_apple_tv_only,
                "selectedParamountPlusOnly" => $selected_paramount_plus_only,
                "selectedBbcIplayerOnly" => $selected_bbc_iplayer_only,
                "selectedItvxOnly" => $selected_itvx_only,
                "selectedChannel4Only" => $selected_channel4_only,
                "selectedUnwatchedByFriends" => $selected_unwatched_by_friends,
                "infoMessages" => $info_messages
            ]
        );
    }

}
