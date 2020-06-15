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
    public function index(
        $genre = 'all', $time_period = 'all', $english_only = 0, $unwatched_only = 0, 
        $favourites_only = 0, $search_text = "", $netflix_only = 0, $amazon_only = 0, 
        $nowtv_only = 0, $unwatched_by_friends = '')
    {

        //Users
        $user = Auth::user();
        $user_id = $user->id;
        $UserObj = User::find($user_id);
        $user->friendsCount = $UserObj->friendsCount();

        if ($search_text == "null") $search_text = '';

        $movieController = new MovieController;
        $movies = $movieController->getMovies($genre, $time_period, $english_only, $unwatched_only, 
        $favourites_only, $search_text, $netflix_only, $amazon_only, $nowtv_only, $unwatched_by_friends);

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

        $filters = collect([
            "genre" => $selected_genre,
            "time_period" => $selected_time_period,
            "english_only" => $selected_english_only,
            "unwatched_only" => $selected_unwatched_only,
            "favourites_only" => $selected_favourites_only,
            "search_text" => $selected_search_text,
            "netflix_only" => $selected_netflix_only,
            "amazon_only" => $selected_amazon_only,
            "nowtv_only" => $selected_nowtv_only,
            "unwatched_by_friends" => $selected_unwatched_by_friends
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
                "selectedUnwatchedOnly" => $selected_unwatched_only,
                "selectedFavouritesOnly" => $selected_favourites_only,
                "selectedSearchText" => $selected_search_text,
                "selectedNetflixOnly" => $selected_netflix_only,
                "selectedAmazonOnly" => $selected_amazon_only,
                "selectedNowtvOnly" => $selected_nowtv_only,
                "selectedUnwatchedByFriends" => $selected_unwatched_by_friends,
                "infoMessages" => $info_messages
            ]
        );
    }

}
